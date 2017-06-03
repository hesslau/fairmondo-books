***REMOVED***

namespace App\Factories;

use Carbon\Carbon;
use Faker\Provider\cs_CZ\DateTime;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Types\Integer;
use PONIpar;
use PONIpar\Parser;
use App\Facades\ConsoleOutput;
use App\Models\LibriProduct;

// Exceptions
use App\Exceptions\MissingDataException;
use ErrorException;

class LibriProductFactory implements IFactory {

    public static function makeFromFile(string $file): array {

        // get date of catalog update (don't use simplexml, the file might be very large)
        $dateOfCatalogUpdate = new Carbon(self::getDateOfCatalogUpdate($file));

        // get number of items in file to make a progress bar
        $numberOfItems = substr_count(file_get_contents($file),'<product>');
        $progress = ConsoleOutput::progress($numberOfItems);

        // holds all products
        $products = [***REMOVED***
        $productHandler = function($product) use ($progress,&$products,$dateOfCatalogUpdate) {

            ConsoleOutput::advance($progress);

            try {
                // create Object from ONIX message
                $libriProduct = self::create($product);

                if(!is_null($libriProduct)) {
                    $libriProduct->DateOfData = $dateOfCatalogUpdate;
                    $products[] = $libriProduct;
                }

            } catch (MissingDataException $e) {
                ConsoleOutput::error($e->getMessage());
                Log::warning($e->getMessage());
            }
        };

        try {

            $parser = new Parser();                         // create instance of PINOpar Parser
            $parser->setProductHandler($productHandler);    // define a product handler which will be called for each <product> tag
            $parser->useFile($file);
            $parser->parse();
            ConsoleOutput::finish($progress);

        } catch(PONIpar\XMLException $e) {

            // error in ONIXMessage, e.g. missing <ProductIdentifier>
            // todo: find a way to continue parsing the onixmessage
            ConsoleOutput::error($e->getMessage());
            Log::warning($e->getMessage());

        } catch(Exception $e) {

            ConsoleOutput::error($e->getMessage());
            Log::error($e->getMessage());
            // todo: find a way to continue parsing after caught exception

        }

        // clear $products from null values
        $products = array_filter($products, function($item) {
            return !is_null($item);
        });

        return $products;
    }

    private static function getDateOfCatalogUpdate($file) {
        $file = fopen($file, "r");

        if ($file)
        {
            while (($line = fgets($file)) !== false)
            {
                $match = Null;
                preg_match('/\<m182\>([0-9]{8})\<\/m182\>/',$line,$match);
                if(isset($match[1])) return $match[1***REMOVED***
            }
        }
        else
        {
            echo "Unable to open the file";
        }
    }

    /*
     * Generate a fake Product for testing purposes.
     */
    public static function makeFakeProduct($attributes = []) {
        list($product) = self::makeFromFile(storage_path('/testing/VALID_TESTFILE.XML'));
        foreach ($attributes as $attribute => $value) {
            $product->$attribute = $value;
        }
        return $product;
    }

    // todo: implement memory friendly version of this
    public static function store(array $products): bool {
        $progress = ConsoleOutput::progress(count($products));

        foreach ($products as $product) {
            $existingProduct = LibriProduct::find($product->ProductReference);

            // if this product doesn't exists, save it
            if(!$existingProduct) $product->save();

            // if this product already exists and comes from an older CatalogUpdate
            // delete existing product and save the new one
            elseif( is_null($existingProduct->DateOfData) || is_null($product->DateOfData)
                    || $existingProduct->DateOfData < $product->DateOfData) {
                $existingProduct->delete();
                $product->save();
            }

            ConsoleOutput::advance($progress);
        }

        // complete the progress bar
        ConsoleOutput::finish($progress);

        return true;
    }

    // todo: implement memory friendly version of this
    static function create(PONIpar\Product $onix){
        $libriProduct = new LibriProduct;
        $controller = new LibriProductFactory();
        $controller->product = $onix;

        $recordReference = $controller->getRecordReference();

        /* ---------------- required data -------------- */
        $libriProduct->RecordReference          = $recordReference;
        $libriProduct->ProductForm              = $controller->getProductForm();
        $libriProduct->DistinctiveTitle         = $controller->getDistinctiveTitle();

        $tmpReference                           = $controller->getProductReference();
        if(!$tmpReference) return null;         // if no valid reference was found, this product is invalid

        $libriProduct->ProductReference         = (String) $tmpReference[0***REMOVED***
        $libriProduct->ProductReferenceType     = (String) $tmpReference[1***REMOVED***

        // test required fields
        $requiredFields = [
            'RecordReference',
            'ProductReference',
            'DistinctiveTitle',
            'ProductForm'
    ***REMOVED***;

        foreach ($requiredFields as $field) {
            if ($libriProduct->$field === false) throw new MissingDataException("Content of `$field` not found or empty.", $recordReference);
        }

        /* ---------------- optional data -------------- */
        $libriProduct->OrderTime            = (Integer) $controller->getOrderTime();
        $libriProduct->QuantityOnHand       = (Integer) $controller->getQuantityOnHand();
        $libriProduct->AvailabilityStatus   = (Integer) $controller->getAvailabilityStatus();
        $libriProduct->NumberOfPages        = (Integer) $controller->getNumberOfPages();
        $libriProduct->VLBSchemeOld         = (Integer) $controller->getVLBSchemeOld();
        $libriProduct->ProductWeight        = (Integer) $controller->getProductWeight();
        $libriProduct->ProductWidth         = (Integer) $controller->getProductWidth();
        $libriProduct->ProductThickness     = (Integer) $controller->getProductThickness();
        $libriProduct->AudienceCodeValue    = (Integer) $controller->getAudienceCodeValue();

        $libriProduct->Author               = (String) $controller->getAuthor();
        $libriProduct->CoverLink            = (String) $controller->getCoverLink();
        $libriProduct->ProductLanguage      = (String) $controller->getProductLanguage();
        $libriProduct->PublisherName        = (String) $controller->getPublisherName();
        $libriProduct->PublicationDate      = (String) $controller->getPublicationDate();
        $libriProduct->Blurb                = (String) $controller->getBlurb();

        $controller->CurrentPrice           = $controller->getPriceDeCurrent();
        $libriProduct->PriceAmount          = (float) $controller->getPriceAmount();
        $libriProduct->PriceTypeCode        = (Integer) $controller->getPriceTypeCode();
        $libriProduct->DiscountPercent      = (Integer) $controller->getDiscountPercent();
        $libriProduct->TaxRateCode1         = (String) $controller->getTaxRateCode1();
        $libriProduct->NotificationType     = $controller->getNotificationType();
        $libriProduct->Lib_MSNo             = $controller->getLibriNotificationKey();
        $libriProduct->AvailabilityCode     = $controller->getAvailabilityCode();
        $libriProduct->PublishingStatus     = $controller->getPublishingStatus();

        return $libriProduct;
    }


    public function __construct() {
    }

    /* from http://www.bastian.name/2006/11/isbn10-zu-isbn13-umrechnung-mit-php.html */
    private function isbn10to13($isbn10){
        $isbnclean = preg_replace("/([^d])/", "",substr($isbn10,0,-1));

        if (strlen($isbnclean) != 9) {
            return $isbn10;
        }

        $isbn="978".$isbnclean;

        $check=0;
        for ($i=0;$i<12;$i++) {
            $check+=(($i%2)*2+1)*$isbn{$i};
        }
        $check=(10-($check%10))%10;
        return "978".substr($isbn10,0,-1).$check;
    }

    /* returns nodeValue of first match if query is successful */
    private function _getFirstElement($query, $debug=false) {
        $result = $this->product->get($query);
        if($debug && env('APP_DEBUG')) var_dump($result);
        if($result) {
            return $result[0]->nodeValue;
        } else return false;
    }

    /**
     * returns an array of childNodes of DOMElement with nodeName as key and nodeValue as value
     * @param \DOMElement $DOMElement
     */
    private function _childNodes2Array(\DOMElement $DOMElement) {
        $childNodes = array();

        foreach ($DOMElement->childNodes as $node) {
            $childNodes[$node->nodeName] = $node->nodeValue;
        }

        return $childNodes;
    }

    public function getRecordReference() {
        return $this->_getFirstElement('RecordReference');
    }

    public function getProductReference() {
        // choose first ISBN13, then EAN then ISBN10 as reference
        $allowedIdentifierTypes = [
            PONIpar\ProductSubitem\ProductIdentifier::TYPE_ISBN13,      // 15
            PONIpar\ProductSubitem\ProductIdentifier::TYPE_GTIN13,      // 03
            PONIpar\ProductSubitem\ProductIdentifier::TYPE_ISBN10       // 02
    ***REMOVED***;

        $productReference = null;
        // loop through the allowed identifier types and pick the first successful match
        foreach ($allowedIdentifierTypes as $identifierType) {
            if($productReference = $this->getProductIdentifier($identifierType)) {
                if($identifierType == PONIpar\ProductSubitem\ProductIdentifier::TYPE_ISBN10) {
                    $productReference = self::isbn10to13($productReference);
                }
                return array((String) $productReference,$identifierType);
            }
        }
        return false;
        // throw new MissingDataException("No allowed product identifier for ".$this->getRecordReference());
    }

    /* Verarbeitungsregel für Feld "pr.ProductReference"
            Immer als ISBN13
            1. ProductIdentifier Composite mit ProductIDType = 15
            2. ProductIdentifier Composite mit ProductIDType = 03
            3. ProductIdentifier Composite mit ProductIDType = 02 (umgerechnet)
            ist keines davon vorhanden, ist der Satz fehlerhaft
       ONIX Reference: PR 2.7 / IDValue / b244  */
    public function getProductIdentifier($type) {
        try {
            $reference = $this->product->getIdentifier($type);
            return $reference;
        } catch(PONIpar\ElementNotFoundException $e) {}
        return false;
    }

    /*  Verarbeitungsregeln für pr.DistinctiveTitle
            1. TitleText aus erstem Title Composite mit TitleType=01 und Language=pr.ProductLanguage
            2. TitleText aus erstem Title Composite mit TitleType=01 ohne Language Angabe
            3. <DistinctiveTitle> PR 7.2
		wenn im gefundenen Composite ein leerer Tag ist, wir ein leerer Wert übernommen! Wenn kein Titel vorhanden ist, Warnmeldung ausgeben
    */
    public function getDistinctiveTitle() {
        $titles = $this->product->getTitles();
        foreach($titles as $title) {
            if($title->getType() == PONIpar\ProductSubitem\Title::TYPE_DISTINCTIVE_TITLE)
                return $title->getValue()['title'***REMOVED***
        }
        return false;
    }

    /*
     * @todo implement following sql selection
     	update macSelectProducts
	   set Contributor =
	       cast((
		   select Top 1 PersonName from refProductContributor tmp
		   where macSelectProducts.ProductReference = tmp.ProductReference and tmp.SequenceNumber = 1
		   and tmp.ContributorRole in ('A01') and PersonName not like '%Anonym%' and PersonName not like '%ohne Autor%' and PersonName not like '%nknown%' and PersonName not like '%ohne autor%'
		   )as varchar(200))
     */
    public function getAuthor() {
        $contributors = $this->product->getContributors();
        foreach ($contributors as $contributor) {
            if($contributor->getRole() == PONIpar\ProductSubitem\Contributor::ROLE_AUTHOR) {

                $value = $contributor->getValue();

                if(key_exists('PersonName',$value)) {
                    return $value['PersonName'***REMOVED***
                }
                elseif(key_exists('PersonNameInverted',$value)) {
                    $inverted = $value['PersonNameInverted'***REMOVED***
                    $author = implode(' ',array_reverse(explode(', ',$inverted)));
                }

                // if the first author string is too long (might be a collections of authors), skip to the next
                if(strlen($author) < 100) return $author;
                else continue;

            }
        }
        // if we reach this that means we haven't found an author
        return null;
    }

    /*	Get Link to highest resolution

	Codelists
		f114: MediaFileTypeCode
			04, Image, front cover
			06, Image, front cover, high quality
			10, wenn der Annotationtyp = ‚BPROB‘
			29, wenn der Annotationtyp = ‚VPROB‘
			30, wenn der Annotationtyp = ‚HPROB‘

		f115: MediaFileFormatCode
			02 GIF
			03 JPEG
			04 PDF
			05 TIF
			06 REALAudio 28.8
			07 MP3
			08 MPEG-4
	*/
    public function getCoverLink() {
        $link = $this->_getFirstElement('MediaFile[MediaFileTypeCode=06 or MediaFileTypeCode=04]/MediaFileLink');
        if($link) {
            return $link;
        } else return null;
    }

    /* Verarbeitungsregeln für Feld "pr.ProductLanguage"
        1. erstes Language composite mit Role=01
        2. <LanugageOfText>
        3. Header, DefaulLanguageOfText
        4. Defaultwert = "ger"
        Immer genau eine Angabe. Wir für Selection DistinctiveTitle, MainText usw. verwendet
    */
    public function getProductLanguage() {
        $result = $this->_getFirstElement('Language[LanguageRole=01]/LanguageCode');
        isset($result) or $result = $this->_getFirstElement('LanguageOfText');
        isset($result) or $result = $this->_getFirstElement('DefaultLanguageOfText');
        isset($result) or $result = config('libriproduct.default.ProductLanguage');

        return $result;
    }

    public function getProductForm() {
        return $this->_getFirstElement('ProductForm');
    }

    /* Verarbeitungsregel für Feld "pr.PublisherName"
            1. <PublisherName> aus Publisher Composite mit PublishingRole = 01
            2. <PublisherName> PR 19.6 (deprecated)
        ONIX Reference: PR 19.7 / PublisherName / b081 */
    public function getPublisherName() {
        return $this->_getFirstElement('Publisher[PublishingRole=01]/PublisherName');
    }

    public function getNumberOfPages() {
        return $this->_getFirstElement('NumberOfPages');
    }

    public function getPublicationDate() {
        return $this->_getFirstElement('PublicationDate');
    }

    /* Verarbeitungsregel für Feld "pr.VLBSchemeOld"
        1. SubjectCode aus MainSubject Composite wenn SchemeIdentifier=26 und SchemeVersion=0 oder leer
        2. SubjectCode aus Subject Composite wenn SchemeIdentifier=26 und SchemeVersion=0 oder leer
        wenn 5-stellig und letzte Stelle "0" wird die letzte 0 entfernt */
    // @todo test case 2
    public function getVLBSchemeOld() {
        $result = $this->product->get("mainsubject[b191=26]/SubjectCode")
                    or $this->product->get("Subject[b191=26]/SubjectCode");
        if($result) {
            $vlbSchemeOld = $result[0]->nodeValue;
            // remove last "0" if there are 5 characters and last character is "0"
            if(strlen($vlbSchemeOld) == 5 && substr($vlbSchemeOld,-1) === "0") {
                $vlbSchemeOld = substr($vlbSchemeOld,0,4);
            }
            return $vlbSchemeOld;
        }
        else return false;
    }

    public function isUnpricedItem() {
        return ($this->_getFirstElement('UnpricedItemType') == true);
    }

    /* Verarbeitungsregel für Feld "pr.ProductWeight"
        <Measurement> aus Measure Composite mit TypeCode = 08, in Gramm
        ONIX Reference: PR 22.1 / Measurement / c094 */
    public function getProductWeight() {
        return $this->_getFirstElement('Measure[c093=08]/Measurement');
    }

    /* Verarbeitungsregeln für Feld "pr.ProductWidth"
        <Measurement> aus Measure composite mit TypeCode=02, in mm
        ONIX Reference: PR 22.1 / Measurement / c094 */
    public function getProductWidth() {
        return $this->_getFirstElement('Measure[c093=02]/Measurement');
    }

    /* Verarbeitungsregeln für Feld "pr.ProductThickness"
        <Measurement> aus Measure Composite mit TypeCode = 03, in mm
        ONIX Reference: PR 22.1 / Measurement / c094 */
    public function getProductThickness() {
        return $this->_getFirstElement('Measure[c093=03]/Measurement');
    }

    /* Verarbeitungsregeln für Feld "pr.OrderTime"
         <OrderTime>
         ONIX Reference: PR 24.36 / OrderTime / j144 */
    public function getOrderTime() {
        return $this->_getFirstElement('SupplyDetail/OrderTime');
    }

    /* Verarbeitungsregeln für Feld "pr.StockOnHand"
         <OnHand> aus erstem StockQuantity Composite
         ONIX Reference: PR 24.41 / Stock / j350 */
    public function getQuantityOnHand() {
        return $this->_getFirstElement('SupplyDetail/Stock/OnHand');
    }

    /*  Verarbeitungsregeln für Feld "pr.PriceDECurrent"
        Status, Betrag, TaxCode, Text, gültig ab, gültig bis
        Regeln: Preis mit TypeCode = 04 oder 02, MinOrder = 1 oder leer, Currency=EUR, Country=DE
        Datum gültig ab <= heute oder leer, Datum gültig bis >= heute oder leer,
        bei mehreren der Preis mit dem grössten Datum gültig ab
        Wenn kein Preis gefunden wird, Subskriptionspreis übernehmen (als Typ 04)
        ONIX Reference: nicht definiert / siehe Price Composite / nicht definiert */
    public function getPriceDeCurrent() {
        $today = date('Ymd');
        $prices = $this->getPrices();

        // sort from latest to earliest date
        usort($prices, function($a,$b) {
            return ($a['PriceEffectiveFrom'] <= $b['PriceEffectiveFrom']) ? 1 : -1;
        });

        // test conditions and return first match
        foreach ($prices as $price) {
            // @todo condition doesn't check for MinOrder
            if(
                ($price['PriceTypeCode'] == '04' or $price['PriceTypeCode'] == '02')
                and $price['CurrencyCode']  == config('libriproduct.whitelist.CurrencyCode')
                and $price['CountryCode']   == config('libriproduct.whitelist.CountryCode')
                and $price['PriceEffectiveFrom'] <= $today
                and (!isset($price['PriceEffectiveUntil']) or $price['PriceEffectiveUntil'] >= $today)
            ) {

                return $price;
            }
        }
        // note: Libri doesn't use subscription price
        return false;
    }

    public function getTaxRateCode1() {
        try {
            return $this->CurrentPrice['j153'***REMOVED***
        } catch (ErrorException $e) {
            return false;
        }
    }

    public function getPriceAmount() {
        try {
            return $this->CurrentPrice['PriceAmount'***REMOVED***
        } catch (ErrorException $e) {
            return false;
        }
    }

    public function getPriceTypeCode() {
        try {
            return $this->CurrentPrice['PriceTypeCode'***REMOVED***
        } catch (ErrorException $e) {
            return false;
        }
    }

    public function getDiscountPercent() {
        try {
            return $this->CurrentPrice['DiscountPercent'***REMOVED***
        } catch (ErrorException $e) {
            return false;
        }
    }


    /**
     * Retrieve Price information from ONIX data
     *
     * @return array price information
     */
    public function getPrices() {
        // get available price information
        $pricesAsDom = $this->product->get("SupplyDetail/Price");

        // get child nodes as array
        $pricesAsArray = array_map(array($this,'_childNodes2Array'), $pricesAsDom);
        return $pricesAsArray;
    }

    /**
     * Retrieve AudienceCodeValue
     *
     * @return integer AudienceCodeValue
     */
    public function getAudienceCodeValue() {
        return $this->_getFirstElement("Audience/AudienceCodeValue");
    }

    /*
     * Retrieve Blurb Text from ONIX data
     * @todo implement
     * @todo test
     *
     * respective SQL statement from spMacSelectProductsFairnopoly.sql:
       > update macSelectProducts
       > set Blurb =
       >    cast((
       >    select top 1 OtherText from refProductOtherText
       >    where macSelectProducts.ProductReference = refProductOtherText.ProductReference
       >    and refProductOtherText.TextTypeCode = '18')
       >    as varchar(4000))
     */
    public function getBlurb() {
        // check if there is text available in the onix message
        // and return as Blurb if it exists
        $records = $this->product->get('OtherText');
        foreach ($records as $record) {
            if($record->getType() == 18) {
                return $record->getValue();
            }
        }
        return false;
    }

    public function getAvailabilityStatus() {
        return $this->_getFirstElement('SupplyDetail/ProductAvailability');
    }

    public function getNotificationType() {
        return $this->_getFirstElement('NotificationType');
    }

    public function getLibriNotificationKey() {
        // get the code list
        $codeList = $this->_getFirstElement('lieferantintern/ms/ms01');

        // only return key if it's from the default code list
        return ($codeList == "01") ? $this->_getFirstElement('lieferantintern/ms/ms02') : null;
    }

    public function getAvailabilityCode() {
        $result = $this->_getFirstElement("SupplyDetail/AvailabilityCode");
        return $result?: null;
    }

    public function getPublishingStatus() {
        $result = $this->_getFirstElement("PublishingStatus");
        return $result?: null;
    }
}
