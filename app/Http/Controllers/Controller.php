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

    public function makeTestrun($isTestrun) {
        $this->isTestrun = $isTestrun;
        $this->testFiles = ['testing/updates/test/GTUPD00014945.zip'***REMOVED***
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

    public function showLibriProducts() {
        $products = LibriProduct::all();
        $failedConditions = $products->map(function($product) {
            return FairmondoProductBuilder::checkConditions($product);
        });

        return view("products",["products" => $products,
                               "failedConditions" => $failedConditions]);
    }

    public static function importONIXMessage($pathToFile) {

        if(!file_exists($pathToFile)) throw new FileNotFoundException;

        // get controller instance
        $controller = Controller::get();

        // get products from ONIXMessage
        $products = $controller->parseONIXMessage($pathToFile);

        // tell the user what's happening
        ConsoleOutput::info('Importing: ');
        $progress = ConsoleOutput::progress(count($products));

        // import each product to database
        foreach ($products as $product) {
            $controller->importLibriProduct($product);
            ConsoleOutput::advance($progress);
        }
        ConsoleOutput::finish($progress);

        return $products;
    }

    public static function importLibriProduct(LibriProduct $product) {
        LibriProduct::destroy($product->ProductReference);
        return $product->save();
    }

    private function parseONIXMessage($file) {
        // get number of items in file to make a progress bar
        $numberOfItems = substr_count(file_get_contents($file),'<product>');

        ConsoleOutput::info('Parsing:');
        $progress = ConsoleOutput::progress($numberOfItems);
        $products = [***REMOVED***

        $productHandler = function($product) use ($progress,&$products) {

            ConsoleOutput::advance($progress);

            try {
                // create Object from ONIX message
                $products[] = LibriProductFactory::create($product);
            } catch (MissingDataException $e) {
                ConsoleOutput::error($e->getMessage());
                Log::warning($e->getMessage());
            }
        };

        try {
            $parser = new Parser();                         // create instance of PINOpar Parser
            $parser->setProductHandler($productHandler);    // define a product handler which will be called for each <product> tag
            $parser->useFile($file);
            $parser->parse();
            ConsoleOutput::finish($progress);
        } catch(Exception $e) {                             // todo: make parser continue parsing after caught exception
            ConsoleOutput::error($e->getMessage());
            Log::error($e->getMessage(),[$e]);
        }

        return $products;
    }

    public function showExport() {
        $exports = Export::all();

        $messages = $exports->each(function($export) {

            if($export->inProgress()) {
                printf("In progress: Export No. %s started %s.",
                    $export->id,
                    $export->created_at->diffForHumans());

            }
            else if($export->isEmpty()) {
                printf("Export No. %s contains no products. (from %s)",
                    $export->id,
                    $export->created_at->formatLocalized('%A %d %B %Y')
                );
            }
            else if($export->hasFailed()) {
                printf("Export No. %s failed (started on %s)",
                    $export->id,
                    $export->created_at);
            }
            else {
                printf("<a href='/export/%s'>Export No. %s</a> (from %s, Duration: %s, Products: %s)",
                    $export->export_file,
                    $export->id,
                    $export->created_at->formatLocalized('%A %d %B %Y'),
                    $export->getDuration(),
                    $export->number_of_products);
            }

            print "<br>";
        });
        return "";
    }

    public function startExport() {
        return \Artisan::call('fairmondobooks:export', []);
    }
}

