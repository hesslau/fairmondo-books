***REMOVED***

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\FtpController;
use App\Http\Controllers\ZipController;
use App\Http\Controllers\XmlController;
use App\LibriProduct;
use App\Http\Controllers\FairmondoProductFactory;
use Illuminate\Support\Facades\Log;

class importUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $ftpController = new FtpController();
        $zipController = new ZipController();
        $xmlController = new XmlController();

        $workDir = storage_path('app/updates').'/'.time();
        mkdir($workDir);

        print("Data will be saved to $workDir\n");

        $ftpController->downloadLatestUpdate($workDir);
        $zipController->extractAll($workDir);
        $xmlController->loadAll($workDir);

        var_dump(count(LibriProduct::all()));

        $libriProducts = LibriProduct::all();
        foreach ($libriProducts as $libriProduct) {
            // convert data into FairmdonoProduct
            $fairmondoProduct = FairmondoProductFactory::create($libriProduct);

            if($fairmondoProduct) {
                $fairmondoProduct->save();
                print "created fairmondoproduct for ".$libriProduct->RecordReference."<br>\n";
            }
            else {
                // creating fairmondo product failed
                Log::warning("Creating Fairmondoproduct from ".$libriProduct->RecordReference." failed.");
            }

        }

    }
}
