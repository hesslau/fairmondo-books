***REMOVED***
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
use Illuminate\Support\Facades\Log;

// Exceptions
use App\Exceptions\UnpackingFailedException;
use App\Exceptions\RemovingFileFailedException;
use App\Exceptions\DownloadFailedException;
use ErrorException;

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

    const MAX_DOWNLOAD_ATTEMPTS = 3;
    const FINISHED = 'Synchronization finished.';

    public $chunksize = 3;

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
            $download = Download::find($filepath);
            return !($download && $download->success);
        });

        if(key_exists('reverse',$options) && $options['reverse']) {
            $availableFiles = array_reverse($availableFiles);
        }

        // iterate through filelist and download updates
        $index = 0;
        foreach($availableFiles as $filepath) {

            // to avoid memory allocation failures, break the download after
            // a few files and let the parent function call this again
            $index++; if($index > $this->chunksize) return false;

            try {
                ConsoleOutput::section("Downloading $filepath ($index of ".count($availableFiles).")");
                $localFile = $this->download($filepath);    // zipFile

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
            } catch(DownloadFailedException $e) {
                ConsoleOutput::error($e->getMessage());
                Log::error($e->getMessage());
                continue;
            }

        }
        return true;
    }

    public function haltPulling(){
        // TODO: implement haltPulling();
    }

    private function download($file) {
        // create FtpController instance if it doesn't exist yet
        if(!isset($this->ftpController)) $this->ftpController = new FtpController($this->ftpSettings);

        // lookup file in downloads table
        $download = Download::find($file);

        // if file wasn't found, create a new Download object
        if(is_null($download)) {
            $download = new Download();
            $download->remote_filepath = $file;
            $download->save();

            // make sure we're dealing with the object from the database
            $download = Download::find($file);
        }

        // set a limit to maximum download attempts
        if($download->attempts >= self::MAX_DOWNLOAD_ATTEMPTS)
            throw new DownloadFailedException("Reached maximum number of attempts (".self::MAX_DOWNLOAD_ATTEMPTS.") to download $file.");

        // download the file
        try {
            $download->attempts++;
            $download->save();
            $local_filepath = $this->ftpController->downloadFile($file);
        } catch (ErrorException $e) {
            // todo inspect this error
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