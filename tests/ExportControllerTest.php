***REMOVED***

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\ExportController;
use FairmondoProductTest;

class ExportControllerTest extends TestCase
{
    public function testExport()
    {
        // filepath to export file
        $target = storage_path('app/export/TEXT.csv');

        // delete if exported file already exists
        if(file_exists($target)) unlink($target);

        // create sample data in database
        FairmondoProductTest::createFairmondoProduct()->save();

        // export all data in database
        ExportController::exportFairmondoProducts($target);

        // asser that export file has been written
        $this->assertTrue(file_exists($target));
    }
}
