***REMOVED***

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
        $resource = $zip->open($source);
        if($resource) {
            $zip->extractTo($target);
            $zip->close();

            return true;
        }
        else {
            throw new Exception("Could not extract file '$source' to '$target'.");
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
            $filenames[] = $zipArchive->statIndex($i)['name'***REMOVED***
        }
        return $filenames;
    }

}
