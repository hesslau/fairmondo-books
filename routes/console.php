<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Log;
use App\Services\ExportService;

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
    $products = \App\Factories\LibriProductFactory::makeFromFile($file);

    $totalConditionsFailed = ['passed'=>0];

    foreach ($products as $product) {
        $failedConditions = \App\Factories\FairmondoProductBuilder::checkConditions($product);
        if(!$failedConditions) $totalConditionsFailed['passed']++;
        foreach ($failedConditions as $failedCondition) {
            if(!key_exists($failedCondition,$totalConditionsFailed)) $totalConditionsFailed[$failedCondition] = 0;
            $totalConditionsFailed[$failedCondition]++;
        }
    }
    dd(array_slice($products,0,5),$totalConditionsFailed);
});

Artisan::command('pull:annotations {--test}', function($test){
    $downloadManager = new App\Managers\DownloadManager(
        new \App\FtpSettings(config("ftp.annotations")),
        new \App\Factories\AnnotationFactory(),
        function($filepath) {
            $annotationTypes = ["GKTEXT","GCBILD"];
            return in_array(substr(basename($filepath),0,6),$annotationTypes);
        }
    );

    $downloadManager->chunksize = 5;
    $exitCode = $downloadManager->startPulling(compact('test'));

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
                    if(App\Models\FairmondoProduct::find($gtin)) {
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
    $importManager = new \App\Managers\ImportManager(new App\Factories\LibriProductFactory());
    $importManager->importFile($filename);
});

Artisan::command('fairmondobooks:export {--since} {--test} {--skip=0}', function($since, $test, $skip) {
<<<<<<< HEAD
    ExportService::makeDelta($since, intval($skip), $test);
=======
    App\Http\Controllers\ExportController::makeDelta($since, intval($skip), $test);
>>>>>>> d16bcd92b713fb8df7184f6854318e6415ed325d
});

Artisan::command('fairmondobooks:reexport {--file=0} {--gtin=0}', function($file,$gtin) {
    $gtins = ($file) ? explode("\n",file_get_contents($file)) : [$gtin];
    $gtins = array_map("trim", $gtins);
<<<<<<< HEAD
    return ExportService::exportProducts($gtins);
=======
    return App\Http\Controllers\ExportController::exportProducts($gtins);
>>>>>>> d16bcd92b713fb8df7184f6854318e6415ed325d
});

Artisan::command('fairmondobooks:test:file {filename}', function($filename) {
    if(!file_exists($filename)) throw new Exception("File doesn't exist.");
    $libriProduct = App\Factories\LibriProductFactory::makeFromFile($filename);
    $fairmondoProduct = \App\Factories\FairmondoProductBuilder::create($libriProduct[0]);
    echo strlen($fairmondoProduct->title);
    var_dump($fairmondoProduct);
});

Artisan::command('fairmondobooks:initialImport', function() {


    function c($gtin) {
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
            "custom_seller_identifier"=> "",
            "action"=> "create"
        ];

        //if(App\Models\FairmondoProduct::find($gtin)) return;
        $fairProduct = new App\Models\FairmondoProduct();
        foreach ($attributes as $attribute => $value) {
            $fairProduct->$attribute = $value;
        }
        $fairProduct->gtin = $gtin;

        try {
            $fairProduct->save();
        }
        catch (\Illuminate\Database\QueryException $e) {
            //\App\Facades\ConsoleOutput::error($e->getMessage());
            Log::error("duplicate entry $gtin");
        }
    }


    function importFile($file) {
        $handle = fopen($file, "r");
        ob_start();
        $progress = \App\Facades\ConsoleOutput::progress();
        while(($gtin=fgets($handle)) !== false) {
            c(trim($gtin));
            \App\Facades\ConsoleOutput::advance($progress);
        }
        ob_end_clean();
        \App\Facades\ConsoleOutput::finish($progress);
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