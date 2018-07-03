<?php
/**
 * Created by IntelliJ IDEA.
 * User: hesslau
 * Date: 7/2/18
 * Time: 5:14 PM
 */

namespace App\Services;

use App\Facades\ConsoleOutput;
use App\Managers\DownloadManager;
use App\Factories\LibriProductFactory;


class ImportService
{
    public static function importAnnotationsFromStorage($directory) {

        $downloadManager = new DownloadManager(
            new \App\Factories\AnnotationFactory()
        );

        $downloadManager->chunksize = 5;
        $exitCode = $downloadManager->startPulling('storage', compact('directory'));

        if($exitCode == $downloadManager::FINISHED) exit(0);
        else exit(2);
    }

    /**
     * This function will import the "Urladung" (initial load) from libri, which contains the complete catalogue
     * as XML/ONIX files. It downloads the file (filepath needs to be passed) from the storage server (ftp.storage)
     * and import its contents as if it was an update.
     *
     * @param $pathToZipArchive
     * @throws \Exception
     */
    public static function importUpdatesFromStorage($pathToZipArchive) {

        $libriProductDownloadManager = new DownloadManager(
            new LibriProductFactory(),
            function($filepath) use ($pathToZipArchive) {
                return ($filepath == $pathToZipArchive);        // select only the zip archive
            });

        $message = $libriProductDownloadManager->startPulling('storage');
        if($message != DownloadManager::FINISHED) {
            ConsoleOutput::error("Initial import didn't complete!");
            exit(2);
        }
        else exit(0);
    }

}