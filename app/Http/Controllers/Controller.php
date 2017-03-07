<?php

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
    App\Models\Update;

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

    private function __construct() {}

    public static function get() {
        static $controller = null;
        if(is_null($controller)) {
            $controller = new Controller();
        }
        return $controller;
    }

    public function makeTestrun($isTestrun) {
        $this->isTestrun = $isTestrun;
        $this->testFiles = ['testing/updates/test/GTUPD00014945.zip'];
    }

    public function index() {
        return "todo: make welcome page"; // todo make welcome page
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
        $products = [];

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
}
