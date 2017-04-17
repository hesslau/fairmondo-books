***REMOVED***

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Factories\LibriProductFactory;
use App\Factories\FairmondoProductBuilder;
use App\Factories\AnnotationFactory;
use App\Http\Controllers\Controller;
use App\Models\LibriProduct;
use App\Models\FairmondoProduct;
use App\Models\Annotation;
use PONIpar\Parser;
use League\Csv\Reader;
use App\Facades\ConsoleOutput;


class FairmondoProductTest extends TestCase
{
    public static function createFairmondoProduct($filepath,$debug=false) {
        list($libriProduct) = LibriProductFactory::makeFromFile(storage_path($filepath));
        if($debug) dd($libriProduct);
        return FairmondoProductBuilder::create($libriProduct);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCreateFairmondoProduct()
    {
        $fairmondoProduct = self::createFairmondoProduct('testing/VALID_TESTFILE.XML');

        // assert that a valid FairmondoProduct has been created
        $this->assertTrue($fairmondoProduct != false,"Test didn't produce valid Product");

        $expectedData = [
            //'title' => 'Keun, Irmgard: Das kunstseidene Mädchen (, EAN 3123511413000)',
            //'categories' => '',
            //'quantity' => 51,
            //'price_cents' => '',
            //'vat' => '',
            //'external_title_image_url' => '',
            //'gtin' => '',
            //'custom_seller_identifier' => '',
            //'action' => '',

            // default values
            'condition'                 => config('fairmondoproduct.default.condition'),
            'transport_type1'           => config('fairmondoproduct.default.transport_type1'),
            'transport_type1_provider'  => config('fairmondoproduct.default.transport_type1_provider'),
            'transport_type1_cents'     => config('fairmondoproduct.default.transport_type1_cents'),
            'transport_type1_number'    => config('fairmondoproduct.default.transport_type1_number'),
            'transport_details'         => config('fairmondoproduct.default.transport_details'),
            'unified_transport'         => config('fairmondoproduct.default.unified_transport'),
            'payment_bank_transfer'     => config('fairmondoproduct.default.payment_bank_transfer'),
            'payment_paypal'            => config('fairmondoproduct.default.payment_paypal'),
            'payment_invoice'           => config('fairmondoproduct.default.payment_invoice'),
            'payment_voucher'           => config('fairmondoproduct.default.payment_voucher'),
            'payment_details'           => config('fairmondoproduct.default.payment_details')

    ***REMOVED***;

        foreach($expectedData as $key => $value) {
            $this->assertEquals($value, $fairmondoProduct->$key, "testing $key");
        }
    }

    public function testInvalidAudienceCode() {
        $libriProduct = LibriProductFactory::makeFakeProduct(['AudienceCodeValue' => 18]);
        $fairmondoProduct = FairmondoProductBuilder::create($libriProduct);
        $this->assertNull($fairmondoProduct,"Product with invalid audience code was created.");
    }

    public function testInvalidProductForm() {
        $libriProduct = LibriProductFactory::makeFakeProduct(['ProductForm' => 'XX']);
        $fairmondoProduct = FairmondoProductBuilder::create($libriProduct);
        $this->assertNull($fairmondoProduct,"Product with invalid product form was created.");

        $libriProduct = LibriProductFactory::makeFakeProduct(['ProductForm' => 'AA']);
        $fairmondoProduct = FairmondoProductBuilder::create($libriProduct);
        $this->assertNull($fairmondoProduct);
    }

    public function testActionTypes() {
        // the following ONIXMessages each contain a single product with the same ISBN13 and will replace another
        $testProductReference = '1111111111111';

        // make sure all there are no test cases in the database
        FairmondoProduct::destroy($testProductReference);

        // create product for FairmondoDatabase
        $product = self::createFairmondoProduct("testing/VALID_TESTFILE.XML");

        // assert that the product was correctly imported
        $this->assertEquals($testProductReference, $product->gtin, "Product wasn't imported.");

        // assert that the action is set to 'create'
        $this->assertEquals(FairmondoProductBuilder::ACTION_CREATE,$product->action);

        // save fairmondo product to database
        $product->save();

        // import the same test case again (overwrites the last import in Libri database)
        $updatedProduct = self::createFairmondoProduct("testing/VALID_TESTFILE.XML");

        // assert that action is 'update'
        $this->assertEquals(FairmondoProductBuilder::ACTION_UPDATE,$updatedProduct->action);

        // import same product with delete notification
        $deletedProduct = self::createFairmondoProduct("testing/ActionTypes/TEST_CASE-deletion.xml");
        $this->assertEquals(FairmondoProductBuilder::ACTION_DELETE,$deletedProduct->action);

        // remove test case
        FairmondoProduct::destroy($testProductReference);
    }

    public function testCategories() {

        // test if pr.VLBSchemeOld 18500 maps to FairmondoCategoryID 310
        $libriProduct = LibriProductFactory::makeFromFile(storage_path("testing/Categories/VLBSCHEME_18500.XML"));
        $fairmondoProduct = FairmondoProductBuilder::create($libriProduct[0]);
        $this->assertContains("310",$fairmondoProduct->categories);

        // test if product is detected as an Audiobook (FairmondoCategoryID 117)
        $fairmondoProduct = self::createFairmondoProduct("testing/Categories/AUDIOBOOK.XML");
        $this->assertContains("117",$fairmondoProduct->categories);

        // test if product has category correspondending to ProductForm
        $libriProduct = LibriProductFactory::makeFromFile(storage_path("testing/Categories/PRODUCTFORM_AC.XML"));
        $fairmondoProduct = FairmondoProductBuilder::create($libriProduct[0]);
        $this->assertContains("27",$fairmondoProduct->categories);

    }

    public function testBlurb() {
        $annotations = AnnotationFactory::makeFromFile(storage_path('testing/Annotations/EN_1111111111111_35337_KTEXT.HTM'));
        AnnotationFactory::store($annotations);

        $product = self::createFairmondoProduct('testing/VALID_TESTFILE.XML');
        $this->assertContains($annotations[0]->AnnotationContent, $product->content);

        // cleanup
        Annotation::where('ProductReference','1111111111111')->delete();
    }

    public function testBlacklist() {
        $p = new LibriProduct();
        $p->VLBSchemeOld = 5100;
        $failedConditions = FairmondoProductBuilder::checkConditions($p);
        $this->assertNotContains("NotOnBlacklist",$failedConditions);

        $p->PublisherName = config('fairmondoproduct.Blacklist.PublisherName')[0***REMOVED***
        $failedConditions = FairmondoProductBuilder::checkConditions($p);
        $this->assertContains("NotOnBlacklist",$failedConditions);
        $p->PublisherName = "Some Publisher";

        $p->Author = config('fairmondoproduct.Blacklist.Author')[0***REMOVED***
        $failedConditions = FairmondoProductBuilder::checkConditions($p);
        $this->assertContains("NotOnBlacklist",$failedConditions);
        $p->Author = "Some Author";

        $p->ProductReference = config('fairmondoproduct.Blacklist.ProductReference')[0***REMOVED***;
        $failedConditions = FairmondoProductBuilder::checkConditions($p);
        $this->assertContains("NotOnBlacklist",$failedConditions);
        $p->ProductReference = "1111111111111";

        $p->Blurb = config('fairmondoproduct.Blacklist.Blurb')[0***REMOVED***;
        $failedConditions = FairmondoProductBuilder::checkConditions($p);
        $this->assertContains("NotOnBlacklist",$failedConditions);
    }

    public function _testRealData() {
        $csv = Reader::createFromPath(storage_path("testing/ONIXToolsData/170303114731/170303114731-Fairmondo-0000001.csv"));
        $csv->setDelimiter(';');

        // headers are [0] => 'title'. we want it flipped!
        $headers = array_flip($csv->fetchOne());

        // get an interator
        $csvIterator = $csv->fetch();

        // let's skip a few test cases
        $offset = 5;
        for($i=0;$i<$offset;$i++) $csvIterator->next();

        $productsToSkip = [9780008202132,9780008181833,9783125354029,9783125620421, 9783131669919, 9783125613096,4009750255766,9780007161850,9780199545469,9780295989075,9780300123999,9780393329810***REMOVED*** //['0028947920717','0028947970439','0028948303939','0042284302821','0091037567970','0602498717004','0602517810266'***REMOVED***
        $fieldsToSkip = ['quantity','content','transport_time','action','price_cents','title'***REMOVED***

        while ($csvIterator->valid()) {
            // get current product
            $csvProduct = $csvIterator->current();

            // advance to the next row for next iteration
            $csvIterator->next();

            $gtin = $csvProduct[$headers['gtin']***REMOVED***
            ConsoleOutput::info(sprintf("checking %s (action %s)",$gtin,$csvProduct[$headers['action']]));

            if(in_array($gtin, $productsToSkip)) {
                ConsoleOutput::info("Skip.");
                continue;
            }

            // get Libri Product
            $lProduct = LibriProduct::find($gtin);

            if($lProduct) {
                ConsoleOutput::info('Product found!');
            } else {
                ConsoleOutput::error("Product $gtin not found!");
                continue;
            }

            $fProduct = FairmondoProductBuilder::create($lProduct);
            $this->assertNotNull($fProduct,"Product $gtin wasn't created. Doesn't meet: ".implode(', ',FairmondoProductBuilder::checkConditions($lProduct)));

            foreach (config('fairmondoproduct.fields') as $field) {

                // skip some fields that may vary
                if(in_array($field,$fieldsToSkip)) continue;

                $expected = $csvProduct[$headers[$field]***REMOVED***
                $actual = $fProduct->$field;

                // remove special character since they're a large source of minor mismatches
                if ($field == 'title') {
                    $expected = preg_replace('/[^A-Za-z0-9\-]/', '', $expected);
                    $actual = preg_replace('/[^A-Za-z0-9\-]/', '', $actual);
                }

                if($field == 'categories') {
                    $expected = explode(',',$expected);
                    $actual = explode(',',$actual);

                    foreach ($expected as $expectedCategory) {
                        $this->assertContains($expectedCategory,$actual,"expected $gtin to have category $expectedCategory");
                    }
                } else {

                    if($expected == 'false') {
                        $expected = false;
                    } elseif($expected == 'true') {
                        $expected = true;
                    }

                    $this->assertEquals($expected,$actual,"Content of field '$field' doesn't match.");
                }

            }


        }
    }
}
