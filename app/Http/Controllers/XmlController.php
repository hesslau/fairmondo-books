***REMOVED***

namespace App\Http\Controllers;

use App\Exceptions\MissingDataException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Exception;
use App\Http\Controllers\LibriProductFactory;
use App\Facades\ConsoleOutput;
use Psy\Exception\ParseErrorException;


class XmlController extends Controller
{
    public function loadXmlFile($file) {
    }

    public function loadAll($directory) {
        $files = scandir($directory);

        // filter zip files
        $filtered = array_filter($files,function($filename) {
            if(strtolower(substr($filename,-3)) == 'xml') return true;
            else return false;
        });

        foreach ($filtered as $file) {
            try {
                $this->loadXmlFile($directory.'/'.$file);
            } catch (Exception $e) {

                // some error. delete all extracted files
                for($i=0;$filtered[$i]!=$file;$i++) {
                    unlink($directory.'/'.$filtered[$i]);
                }

                // throw original exception
                throw $e;
            }
        }

        return true;
    }
}
