<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use Symfony\Component\Console\Helper\ProgressBar; //starthere
use DateTime;

class ConsoleOutput extends Facade {
    protected static function getFacadeAccessor()
    {
        return "consoleOutput";
    }

    /**
     * Write a string as information output.
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    public static function info($string, $verbosity = null)
    {
        self::line($string, 'info', $verbosity);
    }

    /**
     * Write a string as standard output.
     *
     * @param  string  $string
     * @param  string  $style
     * @param  null|int|string  $verbosity
     * @return void
     */
    public static function line($string, $style = null, $verbosity = null)
    {
        $styled = $style ? "<$style>$string</$style>" : $string;

        self::writeln($styled);
    }

    /**
     * Write a string as error output.
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    public static function error($string, $verbosity = null)
    {
        self::line($string, 'error', $verbosity);
    }

    public static function section($string, $verbosity = null) {
        $now = new DateTime();
        $datetime = $now->format('Y-m-d H:i:s');
        $section = "[$datetime] $string ========";
        self::info($section, $verbosity);
    }

    public static function progress($max = null) {
        return new ProgressBar(self::getFacadeRoot(), $max);
    }

    public static function advance(ProgressBar $progress) {
        $progress->advance();
    }

    public static function finish(ProgressBar $progress) {
        $progress->finish();
        self::line('');
    }
}