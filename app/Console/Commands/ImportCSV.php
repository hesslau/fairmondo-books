***REMOVED***

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
    protected $signature = 'import:csv {table} {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports data from file into table (assumes colon delimitor and header row). Usage: import:csv {table} {path}';

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

        // assume that first row is header row
        $header = $csv->fetchOne();
        $progress = ConsoleOutput::progress(count($csv->fetchAll()) - 1);
        $csv->setOffset(1);

        $dbInsert = $csv->each(function($row) use ($header,$progress) {

            $aCombined = array_combine($header,$row);
            $progress->advance();
            return DB::table($this->table)->insert($aCombined);
        });

        $progress->finish();
        return true;
    }
}
