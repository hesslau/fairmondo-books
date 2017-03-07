<?php
/**
 * Created by PhpStorm.
 * User: hesslau
 * Date: 1/23/17
 * Time: 6:00 PM
 */

namespace App\Providers;
use Illuminate\Support\ServiceProvider;

class ConsoleOutputServiceProvider extends ServiceProvider {
    public function register() {
        $this->app->bind('consoleOutput',function() {
            return new \Symfony\Component\Console\Output\ConsoleOutput();
        });
    }
}