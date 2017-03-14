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
            return (substr(basename($filepath),0,6) == "GKTEXT");
        }
    );

    $downloadManager->chunksize = 5;
    $downloadManager->startPulling([]);
});

Artisan::command('fairmondobooks:export {--since} {--test}', function($since, $test) {
    App\Http\Controllers\ExportController::makeDelta($since, $test);
});