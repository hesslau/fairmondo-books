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
            // todo: send notification to developer
            exit(1);
        }

        $libriProductDownloadManager = new App\Managers\DownloadManager(
            new App\FtpSettings(config('ftp.updates')),
            new App\Factories\LibriProductFactory());

        $message = $libriProductDownloadManager->startPulling(compact('startTime','endTime','reverse'));

        /* DownloadManager will return a message when all files have been downloaded.
         * If not, we're sending exit status 2 to tell the bash script that there is more to download.
         * This is part of the workaround to free allocated memory.
         * The Bash command would look like this:

           > php artisan pull --reverse; while [ $? -eq 2 ***REMOVED*** do php artisan pull --reverse; done;
         */
        if($message != App\Managers\DownloadManager::FINISHED) exit(2);
        else exit(0);
    }

}
