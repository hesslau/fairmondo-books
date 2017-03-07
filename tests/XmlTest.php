***REMOVED***

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\XmlController;

class XmlTest extends TestCase
{
    public function testLoadXmlFile()
    {

        $testfile = config('zip.unzipDirectory').'/TESTFILE.XML';
        $xmlController = new XmlController();
        $xmlController->loadXmlFile($testfile);
        $this->assertTrue(true);
    }
}
