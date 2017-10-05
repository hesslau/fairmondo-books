***REMOVED***

namespace App\Http\Controllers;

use App\Facades\ConsoleOutput;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Routing\Controller as BaseController;
use DateTime;

// Controllers
use App\Factories\LibriProductFactory;
use App\Factories\FairmondoProductBuilder;

// Models
use App\Models\LibriProduct,
    App\Models\FairmondoProduct,
    App\Models\Update,
    App\Models\Export;

// Exceptions
use App\Exceptions\MissingDataException,
    Exception;

// External Libraries
use PONIpar\Parser,
    League\Csv\Writer,
    Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    private $isTestrun = false;

    public function __construct() {}

    public static function get() {
        static $controller = null;
        if(is_null($controller)) {
            $controller = new Controller();
        }
        return $controller;
    }

    public function index() {
        $stats = [
            'numberOfLibriProducts' => \App\Models\LibriProduct::count(),
            'numberOfFairmondoProducts' => \App\Models\FairmondoProduct::count(),
            'numberOfAnnotations'       => \App\Models\Annotation::count(),
            'numberOfDownloads'     => \App\Models\Download::count(),
            'latestDownload'          => \App\Models\Download::all()->last()

    ***REMOVED***;
        dd($stats); // todo make welcome page
        return var_export($stats);
    }

    public function showExport() {
        $workdir = "/home/hesslau/Serving/Fairmondo/FairmondoBooks";
        $latestExport = Export::latest()->take(1)->get();

        print "Latest Export was completed on {$latestExport[0]->updated_at}.";
        print "<br><a href='/export/start'>Start Export</a>";

        if(file_exists("$workdir/export.lock")) {
            echo "Export in progress.";
            return file_get_contents("$workdir/$exportLog");
        }
    }

    public function startExport() {
        return \Artisan::call('fairmondobooks:export', []);
    }

    public function showLibriProducts() {
        $products = LibriProduct::all();
        $failedConditions = $products->map(function($product) {
            return FairmondoProductBuilder::checkConditions($product);
        });

        return view("products",["products" => $products,
                               "failedConditions" => $failedConditions]);
    }
}
