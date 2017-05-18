***REMOVED***

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use App\Factories\LibriProductFactory;
use App\Models\LibriProduct;
use Carbon\Carbon;
use PONIpar\Parser;
use App\Exceptions\MissingDataException;

class LibriProductTest extends TestCase
{

    const VALID_TESTFILE = 'testing/VALID_TESTFILE.XML';
    const TEST_PRODUCTREFERENCE = '1111111111111';

    /*public function testCreateLibriProduct()
    {
        $expectedData = [
            'ProductReference' => '1111111111111',
            'ProductReferenceType' => '15',
            'ProductForm' => 'BA',
            'DistinctiveTitle' => 'Das kunstseidene Mädchen',
            'Author' => 'Irmgard Keun',
            'CoverLink' => null,
            'ProductLanguage' => 'ger',
            'PublisherName' => 'Klett Ernst /Schulbuch',
            'NumberOfPages' => 176,
            'PublicationDate' => 200701,
            'VLBSchemeOld' => 1850,
            'ProductWeight' => 170,
            'ProductWidth' => 123,
            'ProductThickness' => 14,
            'OrderTime' => 5,
            'QuantityOnHand' => 51,
            'Blurb' => 'Test-Content',
            'AudienceCodeValue' => 10,
            'AvailabilityStatus' => 20,
            'PriceAmount' => 6.95,
            'TaxRateCode1'  => "R",
            'DiscountPercent'   => 15,
            'PriceTypeCode'     => 04

    ***REMOVED***;

        list($record) = $this->createLibriProductFromFile(self::VALID_TESTFILE);

        foreach($expectedData as $key => $value) {
            $this->assertEquals($value, $record->$key, "testing $key");
        }
    }*/

    private function getTestProduct() {
        return LibriProduct::find(self::TEST_PRODUCTREFERENCE);
    }

    private function removeTestProduct() {
        $product = $this->getTestProduct();
        if(!is_null($product)) $product->delete();
    }

    private function createLibriProductFromFile($file) {
        $libriProducts = LibriProductFactory::makeFromFile(storage_path($file));
        return $libriProducts;
    }

    public function testAvailabilityStatus() {
        $product = LibriProductFactory::makeFakeProduct(['AvailabilityStatus' => 23]);
        $this->assertEquals(23,$product->AvailabilityStatus,"Availability status doesn't match.");
    }


    public function testNoQuantityOnHand() {
        $product = LibriProductFactory::makeFakeProduct(['QuantityOnHand' => 0]);
        $this->assertEquals(0,$product->QuantityOnHand,"QuantityOnHand doesn't match.");
    }

    public function testMissingProductReference() {
        $products = $this->createLibriProductFromFile("testing/MISSINGPRODUCTREFERENCE.XML");
        $this->assertEquals(1,count($products));
    }

    public function testSaveProduct() {
        $products = $this->createLibriProductFromFile(self::VALID_TESTFILE);
        LibriProductFactory::store($products);

        // cleanup
        foreach ($products as $product) {
            $product->delete();
        }
    }

    /*
     * Test
     */
    public function testDateOfData() {
        $this->removeTestProduct();

        $yesterday = Carbon::yesterday();
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        // try to store product created today
        $product = LibriProductFactory::makeFakeProduct([ 'DateOfData' => $today ]);
        LibriProductFactory::store([$product]);
        $this->assertEquals($today, $this->getTestProduct()->DateOfData);

        // try to update with update from yesterday
        $product = LibriProductFactory::makeFakeProduct([ 'DateOfData' => $yesterday ]);
        LibriProductFactory::store([$product]);
        $this->assertNotEquals($yesterday, $this->getTestProduct()->DateOfData);

        // update with update from tomorrow
        $product = LibriProductFactory::makeFakeProduct([ 'DateOfData' => $tomorrow ]);
        LibriProductFactory::store([$product]);
        $this->assertEquals($tomorrow,$this->getTestProduct()->DateOfData);

        // cleanup
        $this->removeTestProduct();
    }

}
