***REMOVED***

namespace App\Factories;

use App\Exceptions\MissingDataException;
use App\Models\FairmondoProduct;
use App\Models\KtextAnnotation;
use App\Models\LibriProduct;
use App\Models\Annotation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use DateTime;
use IntlDateFormatter;

class FairmondoProductBuilder {

    const ACTION_DELETE = 'delete';
    const ACTION_UPDATE = 'update';
    const ACTION_CREATE = 'create';

    public static function create(LibriProduct $source) {
        $product = new FairmondoProduct;
        $failedConditions = self::checkConditions($source);

        if(count($failedConditions) > 0) {
            // log failed conditions
            Log::info("Product ".$source->RecordReference." fails following conditions: ".implode(',',$failedConditions));
            return null;
        }
        else {

            // set default values first
            $defaultValues = config('fairmondoproduct.default');
            foreach ($defaultValues as $attribute => $default) {
                $product->$attribute = $default;
            }

            $product->title = self::getTitle($source);
            $product->categories = join(',',self::getCategories($source));
            $product->quantity = self::getQuantity($source);
            $product->content = self::getContent($source);                      // timekiller!
            $product->vat = self::getVat($source);
            $product->external_title_image_url = self::getExternalTitleImageUrl($source);
            $product->gtin = self::getGtin($source);
            $product->action = self::getAction($source);
            $product->price_cents = self::getPriceCents($source);
            $product->transport_time = self::getTransportTime($source);
            $product->custom_seller_identifier = self::getCustomSellerIdentifier($source);

            return $product;
        }
    }

    private function __construct() {}

    public static function meetsRequirements(LibriProduct $product) {
        return (count(self::checkConditions($product)) === 0);
    }

    /**
     * Checks conditions required for being a valid Fairmondo Product and returns an array with
     * descriptions of the failed conditions.
     * @param LibriProduct The product which needs to be checked
     * @return array description of failed conditions
     */
    public static function checkConditions(LibriProduct $product) {

        // get settings from config
        $validProductForms = array_keys(config('fairmondoproduct.maps.ProductForm'));
        $validAvailabilityStatus = config('fairmondoproduct.conditions.AvailabilityStatus');
        $invalidAudienceCodeValues = config('fairmondoproduct.conditions.invalidAudienceCodeValues');

        // description of condition with matching expression
        $conditions = array(
            "HasDistinctiveTitle"       => ($product->DistinctiveTitle != ''),     // note: useless condition since products without title will fail the previous import
            "HasValidProductForm"       => in_array($product->ProductForm,$validProductForms),
            // todo What happens when AvailabilityStatus changes?
            "IsAvailable"               => in_array($product->AvailabilityStatus,$validAvailabilityStatus),
            "HasAppropriateAudience"    => (isset($product->AudienceCodeValue) and !in_array($product->AudienceCodeValue,$invalidAudienceCodeValues)),
            //"HasQuantityOnHand"         => ($product->QuantityOnHand > 0) // or $product->Lib_MSNo = 15) // @todo what is Lib_MSNo???
            "HasCategory"               => ($product->VLBSchemeOld !== 0 || key_exists($product->ProductForm,config('fairmondoproduct.maps.ProductForm2FairmondoCategory'))),
            "NotOnBlacklist"            => !self::isBlacklisted($product)
        );

        // filter out the failed conditions
        $failedConditions = array_keys(array_filter($conditions, function($condition) {
            return !$condition;
        }));

        return $failedConditions;

        /*
        ProductEAN is not Null
	   and ProductEAN = ProductReference
	   and DistinctiveTitle is not Null
	   -- and ProductLanguage in ('ger', 'eng', 'fre', 'spa', 'ita', 'fin', 'tur', 'dan')
	   and ProductForm in ('BA', 'BB', 'BC', 'BG', 'BH', 'BI', 'BP', 'BZ', 'AC', 'AI', 'VI', 'VO', 'ZE', 'DA', 'DG', 'PC')
	   and AvailabilityStatus = 'LFB'
	   and (AudienceCodeValue is Null or (AudienceCodeValue not like '%16%' and AudienceCodeValue not like '%17%' and AudienceCodeValue not like '%18%'))
	   and (QuantityOnHand > 0 or Lib_MSNo = 15)
	       )
		or ( -- tolino Welt
		   DistinctiveTitle like '%tolino%'
	   and ProductForm in ('00', 'ZZ')
	   and VLBSchemeOld <> 8000
	   and AvailabilityStatus = 'LFB'
	       )
         */
    }

    /**
     * Checks if product is blacklisted.
     * @param LibriProduct $product
     * @return bool True if product is blacklisted.
     */
    private static function isBlacklisted(LibriProduct $product) {
        foreach (config("fairmondoproduct.Blacklist") as $field => $blacklist) {
            foreach ($blacklist as $item) {
                if($product->$field != "" && substr_count($product->$field,$item) > 0) {
                    //echo "$field is Blacklisted ('".$product->$field."')";
                    return true;
                }
            }
        }
        return false;
    }

    // @todo use templating engine
    public static function getTitle(LibriProduct $source) {

        $info = [***REMOVED***
        $info['Title'] = $source->DistinctiveTitle;
        $info['Author'] = $source->Author ? sprintf("%s: ", $source->Author) : false;
        $info['ProductForm'] = sprintf("%s, ",self::getProductFormDescription($source->ProductForm));
        $info['AudioBook']   = $source->isAudioBook() ? config('fairmondoproduct.AudiobookDescription').', ' : false ;
        $info['ProductReference'] = sprintf('EAN %013s',$source->ProductReference);

        $title = config("fairmondoproduct.TitleTemplate");

        foreach ($info as $key => $value) {
            $title = str_replace("%$key",$value,$title);
        }

        // trim title to maximal length
        return self::removeForbiddenChars(self::cleanTrim($title,200));
    }

    /*
     * Trim a string while making sure to not split any words or multibyte characters.
     */
    private static function cleanTrim($text,$number_of_characters) {
        return (strlen($text) < $number_of_characters) ? $text : substr($text, 0, strrpos(substr($text, 0, $number_of_characters), ' '));
    }

    /*
     * This method returns an array of integers which represent
     * the fairmondo market categories to which this product belongs to.
     *
     * @param LibriProduct $source
     * @return array Integers of Fairmondo-Categories
     */
    public static function getCategories(LibriProduct $source) {
        // Products may belong to multiple categories.
        $categories = array();

        // First we find the Fairmondo-Category-ID that correspondents to the VLBSchemeOld-ID.
        // Since the first digit of the VLBSchemeOld-ID determines the type of product (book, cd, dvd etc.)
        // and the last is always 0 (except for software products) we consider only the relevant digits.
        // (Also that's how the ProductGroups table is built.)
        $productGroupID = substr($source->VLBSchemeOld,1,4);

        // find category in auxiliary table 'ProductGroups'
        if($productGroup = DB::table('ProductGroups')->where('ProductGroupID',$productGroupID)->first()) {
            $categories[] = $productGroup->BooksFairnopolyID;       // the table still uses the old name of the company
        }

        // Second we check if this product is an Audiobook.
        if($source->isAudioBook()) {
            $categories[] = 117;
        }

        // Third we check if the ProductForm has a corresponding category defined in the config.
        $map = config('fairmondoproduct.maps.ProductForm2FairmondoCategory');
        if(count($categories) <= 1 && key_exists($source->ProductForm, $map)) {
            $categories[] = $map[$source->ProductForm***REMOVED***
        }

        if(!$categories) throw new MissingDataException("Product doesn't belong to any categories.");

        // todo: find better category matching that doesn't rely on VLBSchemeOld

        return $categories;
    }

    /**
     * Returns human readable description of ProductForm.
     *
     * @param $productForm
     * @return mixed
     */
    private static function getProductFormDescription($productForm) {
        $map = config('fairmondoproduct.maps.ProductForm');
        if(key_exists($productForm,$map)) return $map[$productForm***REMOVED***
        else return "";
    }

    private static function getProductLanguageDescription($productLanguage) {
        $map = config('fairmondoproduct.maps.ProductLanguage');
        if(key_exists($productLanguage,$map)) return $map[$productLanguage***REMOVED***
        else return "";
    }

    public static function getQuantity(LibriProduct $source) {
        if((Integer) $source->QuantityOnHand === 0) return 1;     // see spMacSelectProducts.sql:213 -- tolino Welt evtl. kein Bestand  oder "fehlt kurzfristig am Lager", trotzdem anbieten
        else return $source->QuantityOnHand;
    }

    /**
     * Returns the price in cents. Uses business logic to add to the Libri price.
     *
     * @todo the result doesn't match some test cases (expected is +200)
     * @param LibriProduct $source
     * @return int price in cents
     */
    public static function getPriceCents(LibriProduct $source) {

        $price_cents = intval(strval($source->PriceAmount * 100)); // strval() because of weird intval() behaviour. see http://php.net/manual/de/function.intval.php#101439

        // Preisanpassung +200 - Marge unter oder gleich 10% und Preis zwischen 20 und 40€ und keine Preisbindung
        if(
            $price_cents>=2000
            and $price_cents<4000
            and $source->PriceTypeCode == 2         // 2 means no fixed price
            and $source->DiscountPercent <= 10
        ) {
            $price_cents += 200;
        }

        // Preisanpassung +300 - Preis unter 20€ und keine Preisbindung
        if(
            $price_cents < 2000
            and $source->PriceTypeCode == 2
        ) {
            $price_cents += 300;
        }

        return $price_cents;
    }

    private static function formatDate($date) {
        $formatter = new IntlDateFormatter('de_DE', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
        $formatter->setPattern('MMMM y');

        // attempt to create DateTime object from $date
        $possibleFormats = ['Ym','Ymd'***REMOVED***
        foreach($possibleFormats as $format) {
            $dateTime = DateTime::createFromFormat($format,$date);
            if($dateTime) break;
        }

        return $formatter->format($dateTime);
    }

    /**
     * Builds the Content String from the patterns defined in the config.
     * @return string
     */
    public static function getContent(LibriProduct $source)
    {
        // holder for rendered templates
        $renderedTemplates = [***REMOVED***

        // we loop through all templates that are defined in the config
        foreach (config('fairmondoproduct.templates') as $attribute => $template) {

            // some attributes get special treatment
            switch($attribute) {
                case 'ProductForm':
                    $value = self::getProductFormDescription($source->ProductForm);
                    break;
                case 'PublicationDate':
                    $value = self::formatDate($source->PublicationDate);
                    break;
                case 'Blurb':
                    $value = self::getBlurb($source);
                    break;
                case 'AudioBook':
                    $value = $source->isAudioBook();
                    break;
                case 'ProductLanguage':
                    $value = self::getProductLanguageDescription($source->ProductLanguage);
                    break;
                default:
                    // e.G. $value = $source->Author
                    $value = $source->$attribute;
            }

            // insert the attribute and store it in an array for later
            $renderedTemplates[$attribute] = ($value) ? sprintf($template, $value) : "";

        }


        // feed all templates into the ContentTemplate
        $content = $source->isDigital() ? config('fairmondoproduct.DigitalTemplate')
                                        : config('fairmondoproduct.ContentTemplate');


        foreach ($renderedTemplates as $attribute => $renderedTemplate) {
            $content = str_replace("%$attribute",$renderedTemplate,$content);
        }

        // remove new lines
        $content = trim(preg_replace('/\s\s+/', ' ', $content));

        // trim to max 30000 characters
        $content = self::cleanTrim($content,30000);
        $content = self::removeForbiddenChars($content);

        return $content;
    }

    public static function removeForbiddenChars($text) {
        $forbiddenChars = config('fairmondoproduct.ForbiddenCharacters');
        foreach($forbiddenChars as $needle => $replacement) {
            $text = str_replace($needle,$replacement,$text);
        }
        return $text;
    }
    /*

    ,'<h3>' + DistinctiveTitle + '</h3>' + isnull('<p>von <b>' + Contributor + '</b></p>', '') as content


        update macSelectProductsFairnopoly
   set content = cast(
       content + '<p>'
        + isnull(macSelectProducts.ProductLanguage + ', ', '')
        + isnull(cast(macSelectProducts.NumberOfPages as varchar(20)) + ' Seiten, ', '')
        + isnull(macSelectProducts.PublicationDate + ', ', '')
        + isnull(macSelectProducts.PublisherName + ', ', '')
       + ProductForm + ', ' + isnull('ISBN ' + macSelectProducts.ProductISBN + ', ', '')
       + 'EAN ' + right('0000000000000' + cast(macSelectProductsFairnopoly.gtin as varchar(13)), 13) + '</p>'
       + isnull('<p><b>Beschreibung</b></p><p>' + replace(replace(replace(replace(replace(replace(replace(replace(macSelectProducts.Blurb, char(13), '<br \>'), char(10), '<br \>'), '<br \><br \><br \><br \>', '<br \>'), '<br \><br \><br \>', '<br \>'), '<br \><br \>', '<br \>'), '    ', ' '), '   ', ' '), '  ', ' ') + '</p>', '')
       as varchar(6000))
  from macSelectProducts
 where macSelectProductsFairnopoly.gtin = macSelectProducts.ProductReference
   and ProductForm in ('CD, Hörbuch', 'MP3 CD, Hörbuch', 'Audio CD, Hörbuch', 'Audio DVD, Hörbuch', 'Buch', 'Gebundenes Buch', 'Taschenbuch', 'Ledergebundenes Buch', 'Kalender')


    update macSelectProductsFairnopoly
   set content = cast(
       content + '<p>'
       + isnull(macSelectProducts.PublicationDate + ', ', '') + isnull(macSelectProducts.PublisherName + ', ', '')
       + ProductForm + ', EAN ' + right('0000000000000' + cast(macSelectProductsFairnopoly.gtin as varchar(13)), 13) + '</p>'
       + isnull('<p><b>Beschreibung</b></p><p>' + replace(replace(replace(replace(replace(replace(replace(replace(macSelectProducts.Blurb, char(13), '<br \>'), char(10), '<br \>'), '<br \><br \><br \><br \>', '<br \>'), '<br \><br \><br \>', '<br \>'), '<br \><br \>', '<br \>'), '    ', ' '), '   ', ' '), '  ', ' ') + '</p>', '')
       as varchar(6000))
  from macSelectProducts
 where macSelectProductsFairnopoly.gtin = macSelectProducts.ProductReference
   and ProductForm in ('CD', 'MP3 CD', 'Audio CD', 'Audio DVD', 'Video DVD', 'Blue Ray', 'Spiel', 'Hardware')
     */

    public static function getVat(LibriProduct $source) {
        switch($source->TaxRateCode1) {
            case "R":
                return 7;
            case "S":
                return 19;
            default:
                return 0;
        }
    }

    public static function getExternalTitleImageUrl(LibriProduct $source) {
        if($source->CoverLink) return $source->CoverLink;
        else {
            // if no CoverLink available, build it from ProductReference (EAN)
            $link = config('fairmondoproduct.CoverLinkBaseUrl')
                .substr($source->ProductReference, -3,3)
                .sprintf('/EAN_%013s.jpg',$source->ProductReference);
            return $link;
        }
    }

    public static function getGtin(LibriProduct $source) {
        return sprintf("%013s",$source->ProductReference);
    }

    public static function getCustomSellerIdentifier(LibriProduct $source) {
        $pattern = config('fairmondoproduct.CustomSellerIdentifierTemplate');
        return sprintf($pattern,$source->ProductReference);
    }

    public static function getAction(LibriProduct $source) {
        /**
        update macSelectProductsFairnopoly
        set [action] = 'create'
        where gtin in (select gtin from macSaveProductsFairnopoly where [action] = 'delete')

        update macSaveProductsFairnopoly
        set [action] = 'tobedeleted'
        where gtin not in (select gtin from macSelectProductsFairnopoly)
        and ([action] <> 'delete' or [action] is Null)

        insert into macSelectProductsFairnopoly (title, categories, condition, content, quantity, price_cents, vat, external_title_image_url
        ,transport_type1, transport_type1_provider, transport_type1_price_cents, transport_type1_number, transport_details, transport_time, unified_transport
        ,payment_bank_transfer, payment_paypal, payment_invoice, payment_voucher, payment_details, gtin, custom_seller_identifier, [action]
        )
        select title, categories, condition, content, quantity, price_cents, vat, external_title_image_url
        ,transport_type1, transport_type1_provider, transport_type1_price_cents, transport_type1_number, transport_details, transport_time, unified_transport
        ,payment_bank_transfer, payment_paypal, payment_invoice, payment_voucher, payment_details, gtin, custom_seller_identifier, [action]
        from macSaveProductsFairnopoly
        where [action] = 'tobedeleted'

        update macSelectProductsFairnopoly
        set [action] = 'delete'
        where [action] = 'tobedeleted'

        update macSelectProductsFairnopoly
        set [action] = 'donothing'
        from macSaveProductsFairnopoly
        where macSelectProductsFairnopoly.[action] = 'update'
        and macSelectProductsFairnopoly.gtin = macSaveProductsFairnopoly.gtin
        and macSelectProductsFairnopoly.title = macSaveProductsFairnopoly.title
        and macSelectProductsFairnopoly.categories = macSaveProductsFairnopoly.categories
        and macSelectProductsFairnopoly.price_cents = macSaveProductsFairnopoly.price_cents
        and macSelectProductsFairnopoly.quantity = macSaveProductsFairnopoly.quantity
        and macSelectProductsFairnopoly.transport_time = macSaveProductsFairnopoly.transport_time
        and macSelectProductsFairnopoly.unified_transport = macSaveProductsFairnopoly.unified_transport
        and macSelectProductsFairnopoly.content = macSaveProductsFairnopoly.content
         */

        // try to get product details from database
        $previousRecord = FairmondoProduct::where('gtin',self::getGtin($source))->first();

        // check if the notification type is a deletion
        if($source->NotificationType == "05") {
            $action = self::ACTION_DELETE;
        }
        // all other notification types signal an update to an old record or a new record
        // if the old record was found and it wasn't meant to be deleted, set action to 'update'
        else if($previousRecord and $previousRecord->action != self::ACTION_DELETE) {
            $action = self::ACTION_UPDATE;
        }
        // otherwise the action is 'create'
        else {
            $action = self::ACTION_CREATE;
        }

        // @todo question: do all titles that existed in the previous update and do not exist in this update get $action = 'delete'?
        return $action;
    }

    public static function getTransportTime(LibriProduct $source) {
        if($source->OrderTime <= 4) {
            $transportTime = '1-4';
        } else if($source->orderTime <= 10) {
            $transportTime = '5-10';
        } else {
            $transportTime = $source->orderTime + 1;
        }
        return $transportTime;
    }

    public static function getBlurb(LibriProduct $source) {
        $result = KtextAnnotation::where('ProductReference', $source->ProductReference)->take(1)->get();
        if(count($result) > 0) return $result[0]->AnnotationContent;
        else return null;
    }
}
