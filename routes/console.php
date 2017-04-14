<?php

use Illuminate\Foundation\Inspiring;
use App\DatabaseSupervisor;

ini_set('memory_limit','256M');
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
    $controller = App\Http\Controllers\Controller::get();
    $controller->importONIXMessage($file);
});

Artisan::command('pull:annotations', function(){
    $downloadManager = new App\Managers\DownloadManager(
        new \App\FtpSettings(config("ftp.annotations")),
        new \App\Factories\AnnotationFactory(),
        function($filepath) {
            $annotationTypes = ["GKTEXT","GCBILD"];
            return in_array(substr(basename($filepath),0,6),$annotationTypes);
        }
    );

    $downloadManager->chunksize = 5;
    $exitCode = $downloadManager->startPulling([]);

    if($exitCode == $downloadManager::FINISHED) exit(0);
    else exit(2);
});

Artisan::command('fairmondobooks:export {--since} {--test}', function($since, $test) {
    App\Http\Controllers\ExportController::makeDelta($since, $test);
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

        if(App\Models\FairmondoProduct::find($gtin)) return;
        $fairProduct = new App\Models\FairmondoProduct();
        foreach ($attributes as $attribute => $value) {
            $fairProduct->$attribute = $value;
        }
        $fairProduct->gtin = $gtin;
        $fairProduct->save();
    }

    $handle = fopen("../books-export-henrik.csv", "r");
    ob_start();
    while(($gtin=fgets($handle)) !== false) {
        c(trim($gtin));
    }
    ob_end_clean();
    fclose($handle);
    echo "All Done!";
    Illuminate\Support\Facades\Log::info("Done with initial import!");
});