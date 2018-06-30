<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;
use App\Facades\ConsoleOutput;

class ImportCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv {table} {path} {--truncate} {--offset=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports data from file into table (assumes colon delimitor and header row). Usage: import:csv {table} {path} {--trunace} {--offset=1}';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->table = $this->argument('table');
        $csv = Reader::createFromPath($this->argument('path'));
        $csv->setDelimiter(';');
        $offset = $this->option('offset');

        if($this->option('truncate')) {
            DB::table($this->table)->truncate();
        }

        // assume that first row is header row
        $header = $csv->fetchOne();
        $progress = ConsoleOutput::progress($this->getLineCount($this->argument('path')) - $offset);
        $csv->setOffset($offset);

        $dbInsert = $csv->each(function($row) use ($header,$progress) {

            $aCombined = array_combine($header,$row);
            $progress->advance();

            try {
                return DB::table($this->table)->insert($aCombined);
            } catch(\PDOException $e) {
                $arrayContent = var_export($aCombined,true);
                ConsoleOutput::error("\nInserting row: $arrayContent failed.");
                ConsoleOutput::error("Error: {$e->getMessage()}.");
                return true;
            }
        });

        $progress->finish();
        return true;
    }

    private function getLineCount($filename) {
        $count = 0;
        $fp = fopen( $filename, 'r');

        while( !feof( $fp)) {
            fgets($fp);
            $count++;
        }

        fclose( $fp);
        return $count;
    }
}
