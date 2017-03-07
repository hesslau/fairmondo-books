***REMOVED***

namespace App\Console\Commands;

use App;
use App\Http\Controllers as Controllers;
use DateTime;
use Illuminate\Console\Command;

class Pull extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pull {--test} {--start=} {--end=} {--reverse}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulls updates from Libri FTP-Server.';

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
        $controller = \App\Http\Controllers\Controller::get();
        $controller->makeTestrun($this->option('test'));

        try {
            $startTime = new DateTime($this->option('start'));
            $endTime = new DateTime($this->option('end'));
            $reverse = $this->option('reverse');
        } catch(\Exception $e) {
            echo $e->getMessage();
            exit(1);
        }

        $libriProductDownloadManager = new App\Managers\DownloadManager(
            new App\FtpSettings(config('ftp.updates')),
            new App\Factories\LibriProductFactory());
        return $libriProductDownloadManager->startPulling(compact('startTime','endTime','reverse'));
    }

}
