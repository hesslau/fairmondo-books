<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Log;
use App\Services\ExportService;
use App\Services\ImportService;
use App\Managers\DownloadManager;
use App\Managers\ImportManager;
use App\Factories\AnnotationFactory;
use App\Factories\LibriProductFactory;
use App\Factories\FairmondoProductBuilder;
use App\Facades\ConsoleOutput;
use App\Models\FairmondoProduct;

ini_set('memory_limit','512M');
/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('import:onix {file}',function($file) {
    $products = LibriProductFactory::makeFromFile($file);

    $totalConditionsFailed = ['passed'=>0];

    foreach ($products as $product) {
        $failedConditions = FairmondoProductBuilder::checkConditions($product);
        if(!$failedConditions) $totalConditionsFailed['passed']++;
        foreach ($failedConditions as $failedCondition) {
            if(!key_exists($failedCondition,$totalConditionsFailed)) $totalConditionsFailed[$failedCondition] = 0;
            $totalConditionsFailed[$failedCondition]++;
        }
    }
    dd(array_slice($products,0,5),$totalConditionsFailed);
});

Artisan::command('pull:annotations:text {--test}', function($test){

    // get GKTEXT annotations
    $downloadManager = new DownloadManager(
        new AnnotationFactory(),
        function($filepath) {
            $annotationTypes = ["GKTEXT"];
            return in_array(substr(basename($filepath),0,6),$annotationTypes);
        }
    );

    $downloadManager->chunksize = 5;
    $exitCode = $downloadManager->startPulling('annotations', compact('test'));

    if($exitCode == $downloadManager::FINISHED) exit(0);
    else exit(2);
});

// export each article and it's categories to a file in format
// $custom_seller_identifier|$cateogories (e.g. "A100|33,19")
Artisan::command("export:articlecategories", function() {
   $myfile = fopen("cid-categories.txt", "w");
   FairmondoProduct::chunk(100,function($col) use($myfile) {
       foreach($col as $res) {
           fwrite($myfile,$res->custom_seller_identifier."|".$res->categories."\n");
       }
   });
   fclose($myfile);
});

// get CBILD, ABILD and RUECK annotations
Artisan::command('pull:annotations:media {--initial}', function($initial) {
    $downloadManager = new DownloadManager(
        new AnnotationFactory(),
        function($filepath) { return str_contains(basename($filepath),"lib_gesamt_"); });

    $source = ($initial) ? "media_initial" : "media_updates";
    $exitCode = $downloadManager->startPulling($source);
    if($exitCode == $downloadManager::FINISHED) exit(0);
    else exit(2);
});

Artisan::command('media:cleanup', function() {
    $mediaDir = storage_path('app/media/');

    if ($handle = opendir($mediaDir)) {

        /* This is the correct way to loop over the directory. */
        while (false !== ($subdir = readdir($handle))) {
            if($subdir == '.'
                || $subdir == '..'
                || !is_dir($mediaDir.$subdir)) continue;

            $files = scanDir($mediaDir.$subdir);

            foreach($files as $file) {
                if($file == '.' || $file == '..') continue;

                preg_match('/EAN_([0-9]{10,13}).(jpg|JPG)/',$file,$matches);

                if(count($matches) > 0) {
                    $gtin = $matches[1];
                    if(FairmondoProduct::find($gtin)) {
                        continue;
                    } else {
                        unlink($mediaDir.$subdir.'/'.$file);
                    }
                }

            }

        }
    }
});

/*
 * Command to import files.
 * @todo support annotation files
 */
Artisan::command('fairmondobooks:import:file {filename}', function($filename) {
    $importManager = new ImportManager(new LibriProductFactory());
    $importManager->importFile($filename);
});

Artisan::command('fairmondobooks:import:cbild {filepath} {--rename}', function($filepath) {
    $factory = new AnnotationFactory();
    $factory::store($factory::makeFromFile($filepath, $this->option('rename')));
});

Artisan::command('fairmondobooks:export {--since} {--test} {--skip=0}', function($since, $test, $skip) {
    ExportService::makeDelta($since, intval($skip), $test);
});

Artisan::command('fairmondobooks:reexport {--file=0} {--custom_seller_identifier=0}', function($file,$custom_seller_identifier) {
    $custom_seller_identifiers = ($file) ? explode("\n",file_get_contents($file)) : [$custom_seller_identifier];
    $custom_seller_identifiers = array_map("trim", $custom_seller_identifiers);
    return ExportService::exportProducts($custom_seller_identifiers);
});

Artisan::command('fairmondobooks:test:file {filename}', function($filename) {
    if(!file_exists($filename)) throw new Exception("File doesn't exist.");
    $libriProduct = LibriProductFactory::makeFromFile($filename);
    $fairmondoProduct = FairmondoProductBuilder::create($libriProduct[0]);
    echo strlen($fairmondoProduct->title);
    var_dump($fairmondoProduct);
});

Artisan::command('fairmondobooks:initialImport', function() {


    function c($custom_seller_identifier) {
        $attributes = [
            "title" => "TEST",
            "categories"=> "0,0",
            "condition"=> "",
            "content"=> "",
            "quantity"=> 51,
            "price_cents"=> 695,
            "vat"=> 7,
            "external_title_image_url"=> "",
            "transport_type1"=> 1,
            "transport_type1_provider"=> "",
            "transport_type1_price_cents"=> 300,
            "transport_type1_number"=> 9,
            "transport_details"=>"",
            "transport_time"=> "",
            "unified_transport"=> 1,
            "payment_bank_transfer"=> 1,
            "payment_paypal"=> 1,
            "payment_invoice"=> 0,
            "payment_voucher"=> 1,
            "payment_details"=> "",
            "custom_seller_identifier"=> "A1",
            "action"=> "create"
        ];

        //if(App\Models\FairmondoProduct::find($gtin)) return;
        $fairProduct = new FairmondoProduct();
        foreach ($attributes as $attribute => $value) {
            $fairProduct->$attribute = $value;
        }
        $fairProduct->custom_seller_identifier = $custom_seller_identifier;

        try {
            $fairProduct->save();
        }
        catch (\Illuminate\Database\QueryException $e) {
            //\App\Facades\ConsoleOutput::error($e->getMessage());
            Log::error("duplicate entry $custom_seller_identifier");
        }
    }


    function importFile($file) {
        $handle = fopen($file, "r");
        ob_start();
        $progress = ConsoleOutput::progress();
        while(($custom_seller_identifier=fgets($handle)) !== false) {
            c(trim($custom_seller_identifier));
            ConsoleOutput::advance($progress);
        }
        ob_end_clean();
        ConsoleOutput::finish($progress);
        fclose($handle);
        Illuminate\Support\Facades\Log::info("Done with initial import!");
    }

    $files = explode(' ','books-export-henrik.csv');
    foreach ($files as $file) {
        echo "importing $file";
        importFile("../$file");
    }
    echo "All Done!";
});

Artisan::command('fairmondobooks:import_from_storage {--annotation} {directory}', function($directory) {
    if($this->option('annotation')) {
        ImportService::importAnnotationsFromStorage($directory);
    } else {
        ImportService::importUpdatesFromStorage($directory);
    }
});