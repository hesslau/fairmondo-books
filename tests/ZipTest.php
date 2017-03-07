***REMOVED***

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\ZipController;

class ZipTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExtractFile()
    {
        $zipController = new ZipController();

        $source = config('ftp.downloadDirectory') .'/testfile.zip';
        $target = config('zip.unzipDirectory');
        $targetFile = $target.'/TESTFILE.XML';

        // delete target testfile if it exists
        if(file_exists($targetFile)) unlink($targetFile);

        $this->assertTrue($zipController->extract($source,$target));
        $this->assertTrue(file_exists($targetFile));
    }
}
