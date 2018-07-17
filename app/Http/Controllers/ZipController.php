<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use League\Flysystem\Exception;
use App\Facades\ConsoleOutput;

class ZipController extends Controller
{
    /**
     * Extracts source to target
     *
     * @param $source
     * @param $target
     * @return bool
     */
    public static function extract($source, $target) {
        $zip = new \ZipArchive();
        $zipOpen = $zip->open($source);
        if(is_bool($zipOpen) && $zipOpen == true) {
            $zip->extractTo($target);
            $zip->close();

            return true;
        }
        else {

            $zipFileFunctionsErrors = array(
                'ZIPARCHIVE::ER_MULTIDISK' => 'Multi-disk zip archives not supported.',
                'ZIPARCHIVE::ER_RENAME' => 'Renaming temporary file failed.',
                'ZIPARCHIVE::ER_CLOSE' => 'Closing zip archive failed',
                'ZIPARCHIVE::ER_SEEK' => 'Seek error',
                'ZIPARCHIVE::ER_READ' => 'Read error',
                'ZIPARCHIVE::ER_WRITE' => 'Write error',
                'ZIPARCHIVE::ER_CRC' => 'CRC error',
                'ZIPARCHIVE::ER_ZIPCLOSED' => 'Containing zip archive was closed',
                'ZIPARCHIVE::ER_NOENT' => 'No such file.',
                'ZIPARCHIVE::ER_EXISTS' => 'File already exists',
                'ZIPARCHIVE::ER_OPEN' => 'Cannot open file',
                'ZIPARCHIVE::ER_TMPOPEN' => 'Failure to create temporary file.',
                'ZIPARCHIVE::ER_ZLIB' => 'Zlib error',
                'ZIPARCHIVE::ER_MEMORY' => 'Memory allocation failure',
                'ZIPARCHIVE::ER_CHANGED' => 'Entry has been changed',
                'ZIPARCHIVE::ER_COMPNOTSUPP' => 'Compression method not supported.',
                'ZIPARCHIVE::ER_EOF' => 'Premature EOF',
                'ZIPARCHIVE::ER_INVAL' => 'Invalid argument',
                'ZIPARCHIVE::ER_NOZIP' => 'Not a zip archive',
                'ZIPARCHIVE::ER_INTERNAL' => 'Internal error',
                'ZIPARCHIVE::ER_INCONS' => 'Zip archive inconsistent',
                'ZIPARCHIVE::ER_REMOVE' => 'Cannot remove file',
                'ZIPARCHIVE::ER_DELETED' => 'Entry has been deleted',
            );

            $zipFileFunctionsErrors = array_values($zipFileFunctionsErrors);
            throw new Exception("Could not extract file '$source' to '$target'. ErrorNo: $resource (".$zipFileFunctionsErrors[$resource].")");
        }
    }

    public static function makeArchive($filename,$files) {
        $zip = new \ZipArchive();
        if ($zip->open($filename, \ZipArchive::CREATE) === TRUE) {
            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
            return true;
        } else {
            throw new Exception("Could not create archive at $filename");
            return false;
        }
    }

    public static function extractAll($directory) {
        $files = scandir($directory);


        // filter zip files
        $filtered = array_filter($files,function($filename) {
            if(strtolower(substr($filename,-3)) == 'zip') return true;
            else return false;
        });

        $progress = ConsoleOutput::progress(count($filtered));

        foreach ($filtered as $file) {
            try {
                self::extract($directory.'/'.$file,$directory);
            } catch (Exception $e) {

                // some error. delete all extracted files
                for($i=0;$filtered[$i]!=$file;$i++) {
                    unlink($directory.'/'.$filtered[$i]);
                }

                // throw original exception
                throw $e;
            }
            ConsoleOutput::advance($progress);
        }
        ConsoleOutput::finish($progress);
        return true;
    }

    public static function listContents($source) {
        $zipArchive = new \ZipArchive();
        $zipArchive->open($source);

        $filenames = array();
        for($i=0; $i<$zipArchive->numFiles; $i++) {
            $filenames[] = $zipArchive->statIndex($i)['name'];
        }
        return $filenames;
    }

}
