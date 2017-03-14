<?php
declare(strict_types=1);

namespace App\Managers;

use App\Http\Controllers\FtpController,
    App\Http\Controllers\ZipController;

// Models
use App\Models\Download;

// Helpers
use App\Factories\IFactory,
    App\FtpSettings;

// Facades
use App\Facades\ConsoleOutput;

// Exceptions
use App\Exceptions\UnpackingFailedException;
use App\Exceptions\RemovingFileFailedException;
use App\Exceptions\DownloadFailedException;

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
     * @var FtpSettings
     */
    private $ftpSettings;

    /**
     * @var IFactory
     */
    private $factory;

    const FINISHED = 'Synchronization finished.';

    public $chunksize = 1;

    public function __construct(FtpSettings $ftpSettings, IFactory $factory, callable $fileFilter = null)
    {
        $this->ftpSettings = $ftpSettings;
        $this->factory = $factory;
        $this->fileFilter = $fileFilter;
    }

    /**
     * Synchronizes the remote Files with Local Database
     */
    public function startPulling(array $options = []) {

        // get filelist
        $this->ftpController = new FtpController($this->ftpSettings);
        $availableFiles = $this->ftpController->getFileList();

        if(is_callable($this->fileFilter)) {
            $availableFiles = array_filter($availableFiles,$this->fileFilter);
        }

        // filter out all files that have been downloaded and imported already
        $availableFiles = array_filter($availableFiles,function($filepath) {
            return !(Download::find($filepath));
        });

        if(key_exists('reverse',$options) && $options['reverse']) {
            $availableFiles = array_reverse($availableFiles);
        }

        // iterate through filelist and download updates
        $index = 0;
        foreach($availableFiles as $filepath) {

            // to avoid memory allocation failures, break the download after
            // a few files and let the parent function call this again
            $index++; if($index > self::$chunksize) return false;

            /*$datestring = sprintf("%s %s %s",$file['month'],$file['day'],$file['time']);
            $date = DateTime::createFromFormat("M d H:i", $datestring);

            if(isset($options['startTime']) && $date < $options['startTime']) continue;
            if(isset($options['endTime']) && $date > $options['endTime']) continue;*/

            $download = new Download();
            $download->remote_filepath = $filepath;

            ConsoleOutput::section("Downloading $filepath ($index of ".count($availableFiles).")");
            $localFile = $this->download($filepath);

            ConsoleOutput::section("Extracting $filepath");
            $unpackedFiles = $this->unpack($localFile);

            ConsoleOutput::section("Parsing $filepath");
            $items = $this->parse($unpackedFiles);

            ConsoleOutput::section("Saving to Database");
            $this->import($items);      // TODO: catch exceptions

            // cleaning up
            $this->remove($localFile);

            // tell system that this file has been downloaded
            $download->save();
        }
        return true;
    }

    public function haltPulling(){
        // TODO: implement haltPulling();
    }

    private function download($file) {
        // create FtpController instance if it doesn't exist yet
        if(!isset($this->ftpController)) $this->ftpController = new FtpController($this->ftpSettings);

        // download the file
        try {
            $local_filepath = $this->ftpController->downloadFile($file);
        } catch (ErrorException $e) {
            // sometimes ftp_get() fails with an ErrorException for no apparent reason
            // let's just try again
            ConsoleOutput::error('ftp_get() failed with ErrorException: '.$e->getMessage());
            ConsoleOutput::info('Retrying to download in 5 seconds...');
            sleep(5);
            $this->ftpController->reconnect();
            $local_filepath = $this->ftpController->downloadFile($file);
        }

        // if it didn't work after reconnecting, the download failed
        if(!$local_filepath or !file_exists($local_filepath)) throw new DownloadFailedException("Downloaded file '$file' not found at '$local_filepath'.");

        return $local_filepath;
    }

    private function unpack($archive) {
        $packedFiles = ZipController::listContents($archive);
        ZipController::extract($archive,dirname($archive));

        // check if all files from archive exist on disk
        // and return an array ofdelta paths to the unpacked files
        $unpackedFiles = array_map(function($file) use ($archive) {
            $unpackedFile = dirname($archive).DIRECTORY_SEPARATOR.$file;
            if(!file_exists($unpackedFile)) throw new UnpackingFailedException("Unpacked file '$file' from archive '$archive' doesn't exist.");
            return $unpackedFile;
        }, $packedFiles);

        return $unpackedFiles;
    }

    private function parse(array $files) {

        // holds all objects
        $items = array();

        foreach ($files as $file) {

            // make models from file and merge with previously created objects
            $items = array_merge($items,$this->factory->makeFromFile($file));

            // delete parsed file
            @unlink($file);
        }

        return $items;
    }

    private function import($items) {
        $this->factory->store($items);
    }

    private function remove($file) {
        unlink($file);
        if(file_exists($file)) throw new RemovingFileFailedException("Removing '$file' failed.");
    }
}