<?php
declare(strict_types=1);

namespace App\Managers;

use App\Services\FtpService,
    App\Http\Controllers\ZipController;

// Models
use App\Models\Download;

// Helpers
use App\Factories\IFactory,
    App\FtpSettings;

// Facades
use App\Facades\ConsoleOutput;
use Illuminate\Support\Facades\Log;

// Exceptions
use App\Exceptions\UnpackingFailedException;
use App\Exceptions\RemovingFileFailedException;
use App\Exceptions\DownloadFailedException;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\Storage;

/**
 * Class DownloadManager
 *
 * Manages the Downloads of a certain Model.
 *
 * @package App\Mangers
 *
 */
class DownloadManager
{
    /**
     * @var IFactory
     */
    private $factory;

    const MAX_DOWNLOAD_ATTEMPTS = 3;
    const FINISHED = 'Synchronization finished.';

    public $chunksize = 50;

    public function __construct(IFactory $factory, callable $fileFilter = null)
    {
        $this->factory = $factory;
        $this->fileFilter = $fileFilter;
    }

    private function cleanupFailedDownloads() {
        // remove all the failed download attempts from previous updates
        // todo: maybe restrict these only to download of current download type (annotation vs. update)
        $fewHoursAgo = (new \DateTime('now'))->modify('-20 hours')->format('Y-m-d H:m:s');
        Download::where('success',0)->where('attempts', '>=', self::MAX_DOWNLOAD_ATTEMPTS)
            ->where('updated_at','<',$fewHoursAgo)->delete();
    }

    /**
     * Pulls remote files and processes them.
     * @param string $source
     * @param array $options
     * @return string
     */
    public function startPulling($source = 'updates', array $options = []) {

        // let's remove all previously failed downloads
        self::cleanupFailedDownloads();

        // get the storage
        $storage = Storage::disk($source);

        $availableFiles = self::getListOfAvailableFiles($storage, $options);
        return self::processFiles($availableFiles,$storage);
    }

    private function getListOfAvailableFiles($storage, $options) {

        $directory = isset($options['directory']) ? $options['directory'] : '';

        // get list of files
        $availableFiles = $storage->files($directory);

        // filter list of files if filter function is available
        if(is_callable($this->fileFilter)) {
            $availableFiles = array_filter($availableFiles,$this->fileFilter);
        }

        // filter out all files that have been downloaded and imported already
        $availableFiles = array_filter($availableFiles,function($filepath) {
            $download = Download::find($filepath);
            return !($download && $download->success);
        });

        if(key_exists('reverse',$options) && $options['reverse']) {
            $availableFiles = array_reverse($availableFiles);
        }

        if(key_exists('test',$options) && $options['test']) {
            $availableFiles = array_slice($availableFiles,0,1);
        }

        return $availableFiles;
    }

    private function processFiles($availableFiles, $storage) {
        // iterate through filelist and download updates
        $index = 0;
        foreach($availableFiles as $filepath) {

            // to avoid memory allocation failures, break the download after
            // a few files and let the parent function call this again
            $index++; if($index > $this->chunksize) return false;

            try {
                ConsoleOutput::section("Downloading $filepath ($index of ".count($availableFiles).")");
                $localFile = $this->download($filepath,$storage);    // zipFile

                ConsoleOutput::section("Extracting $filepath");
                $unpackedFiles = $this->unpack($localFile);

                ConsoleOutput::section("Parsing $filepath");
                $items = $this->parse($unpackedFiles);

                ConsoleOutput::section("Saving to Database");
                $this->import($items);      // TODO: catch exceptions

                // Download has been successfully imported
                $download = Download::find($filepath);
                $download->success = true;
                $download->save();

                // cleaning up
                $this->remove($localFile);

                // Write log Message
                Log::info("Imported ".count($items)." products from file $filepath.");
            } catch(Exception $e) {
                ConsoleOutput::error($e->getMessage());
                Log::error($e->getMessage());
                continue;
            }

        }

        return self::FINISHED;
    }

    public function haltPulling(){
        // TODO: implement haltPulling();
    }

    private function download($remote_filepath,$storage) {
        // lookup file in downloads table
        $download = Download::firstOrNew(['remote_filepath' => $remote_filepath]);

        // build local filepath
        $local_filepath = storage_path('app/download/').basename($remote_filepath);

        // set a limit to maximum download attempts
        if($download->attempts >= self::MAX_DOWNLOAD_ATTEMPTS)
            throw new DownloadFailedException(
                "Reached maximum number of attempts (".self::MAX_DOWNLOAD_ATTEMPTS.") to download $remote_filepath.");

        while(!file_exists($local_filepath) && $download->attempts < self::MAX_DOWNLOAD_ATTEMPTS) {

            try {
                $download->attempts++;
                $download->save();
                file_put_contents($local_filepath, $storage->get($remote_filepath));   // stores the file to local disk
            } catch (ErrorException $e) {
                // todo inspect this error
                // sometimes ftp_get() fails with an ErrorException for no apparent reason
                // let's just try again
                ConsoleOutput::error('ftp_get() failed with ErrorException: '.$e->getMessage());
                ConsoleOutput::info('Retrying to download in 5 seconds...');
                sleep(5);
            }
        }

        // if it didn't work after reconnecting, the download failed
        if(!file_exists($local_filepath))
            throw new DownloadFailedException("Downloaded file '$remote_filepath' not found at '$local_filepath'.");

        // return path to downloaded file
        return $local_filepath;
    }

    private function unpack($archive) {
        $packedFiles = ZipController::listContents($archive);
        ZipController::extract($archive,dirname($archive));

        // check if all files from archive exist on disk
        // and return an array ofdelta paths to the unpacked files
        $unpackedFiles = array_map(function($file) use ($archive) {
            $unpackedFile = dirname($archive).DIRECTORY_SEPARATOR.$file;
            if(!file_exists($unpackedFile)) throw new UnpackingFailedException("Unpacked file '$file' from archive '$archive' doesn't exist at '$unpackedFile'.");
            return $unpackedFile;
        }, $packedFiles);

        return $unpackedFiles;
    }

    private function parse(array $files) {

        // holds all objects
        $items = array();

        foreach ($files as $file) {
            try {

                // make models from file and merge with previously created objects
                $items = array_merge($items,$this->factory->makeFromFile($file));
                // delete parsed file
                @unlink($file);

            } catch (ErrorException $e) {
                $errorMsg = sprintf("%s (%s:%s)",$e->getMessage(),$e->getFile(),$e->getLine());
                ConsoleOutput::error("Skipping $file: $errorMsg");
                Log::error("File $file was skipped. Error: $errorMsg");
            }
        }

        return $items;
    }

    private function import($items) {
        $this->factory->store($items);
    }

    private function remove($file) {
        @unlink($file);
        if(file_exists($file)) throw new RemovingFileFailedException("Removing '$file' failed.");
    }
}