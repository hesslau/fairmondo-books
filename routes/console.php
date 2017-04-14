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
        $libriProduct = App\Factories\LibriProductFactory::makeFakeProduct(['ProductReference'=>$gtin]);
        $farProduct = App\Factories\FairmondoProductBuilder::create($libriProduct);
        if(is_null($farProduct)) Illuminate\Support\Facades\Log::error("Could not create FairmondoProduct for $gtin.");
        else $farProduct->save();
    }

    $handle = fopen("../books-export-henrik.csv", "r");
    ob_start();
    while(($gtin=fgets($handle)) !== false) {
        c($gtin);
    }
    ob_end_clean();
    fclose($handle);
    echo "All Done!";
    Illuminate\Support\Facades\Log::info("Done with initial import!");
});