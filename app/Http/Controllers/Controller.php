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

        ];
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

