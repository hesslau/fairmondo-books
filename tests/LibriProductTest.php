<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use App\Factories\LibriProductFactory;
use PONIpar\Parser;
use App\Exceptions\MissingDataException;

class LibriProductTest extends TestCase
{

    const VALID_TESTFILE = 'testing/VALID_TESTFILE.XML';

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

        ];

        list($record) = $this->createLibriProductFromFile(self::VALID_TESTFILE);

        foreach($expectedData as $key => $value) {
            $this->assertEquals($value, $record->$key, "testing $key");
        }
    }*/

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

    public function testCalatolgUpdate() {
        list($product) = $this->createLibriProductFromFile("testing/GTUPD00014261.XML");
        $this->assertEquals(14261,$product->CatalogUpdate);
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

}
