<?php

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
use App\Facades\ConsoleOutput;

class FairmondoProductBuilder {

    const ACTION_DELETE = 'delete';
    const ACTION_UPDATE = 'update';
    const ACTION_CREATE = 'create';

    /**
     * Builds a FairmondoProduct from a LibriProduct.
     */
    public static function create(LibriProduct $source) {
        $product = new FairmondoProduct;

        // set default values first
        $defaultValues = config('fairmondoproduct.default');
        foreach ($defaultValues as $attribute => $default) {
            $product->$attribute = $default;
        }

        $product->title = self::getTitle($source);
        $product->custom_seller_identifier = self::getCustomSellerIdentifier($source);
        $product->gtin = self::getGtin($source);
        $product->action = self::getAction($source);

        try {
            $product->categories = join(',',self::getCategories($source));
        } catch (MissingDataException $e) {

            // don't create product
            if($product->action == self::ACTION_CREATE) {
                ConsoleOutput::error($e->getMessage());
                return null;
            }

            // don't update product; delete instead
            $product->action = self::ACTION_DELETE;
            return $product;
        }

        $product->quantity = self::getQuantity($source);
        $product->content = self::getContent($source);                      // timekiller!
        $product->vat = self::getVat($source);
        $product->external_title_image_url = self::getExternalTitleImageUrl($source);
        $product->external_image_2_url = self::getExternalImage2Url($source);
        $product->price_cents = self::getPriceCents($source);
        $product->transport_time = self::getTransportTime($source);

        return $product;
    }

    private function __construct() {}

    /**
     * Returns true if LibriProduct meets requirements to become a FairmondoProduct.
     */
    public static function meetsRequirements(LibriProduct $product) {
        return (count(self::checkConditions($product)) === 0);
    }

    /**
     * Checks conditions required for being a valid Fairmondo Product and returns an array with
     * descriptions of the failed conditions.
     *
     * @param LibriProduct The product which needs to be checked
     * @return array description of failed conditions
     * @todo what happens when AvailabilityStatus changes?
     */
    public static function checkConditions(LibriProduct $product) {

        /*
         * No need to check for ProductForm, AvailabilityStatus, AudienceCodeValue or valid Price since
         * those are already considered in the selecting SQL query.
         *
         * HasDistinctiveTitle  // redundant condition since products without title will fail the previous import
         * HasQuantityOnHand    // not a requirement, since existing products need to be updated with available quantity
         */

        // description of condition with matching expression
        $conditions = array(
            "HasCategory"               => ($product->VLBSchemeOld !== 0 ||
                key_exists($product->ProductForm,config('fairmondoproduct.maps.ProductForm2FairmondoCategory'))),
            "NotOnBlacklist"            => !self::isBlacklisted($product),
        );

        // filter out the failed conditions
        $failedConditions = array_keys(array_filter($conditions, function($condition) {
            return !$condition;
        }));

        return $failedConditions;
    }

    /**
     * Checks if product is blacklisted.
     *
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

    /**
     * Builds the title string.
     *
     * @todo use templating engine
     */
    public static function getTitle(LibriProduct $source) {

        $info = [];
        $info['Title'] = $source->DistinctiveTitle;
        $info['Author'] = $source->Author ? sprintf("%s: ", $source->Author) : false;
        $info['ProductForm'] = sprintf("%s, ",self::getProductFormDescription($source->ProductForm));
        $info['AudioBook']   = $source->isAudioBook() ? config('fairmondoproduct.AudiobookDescription').', ' : false ;
        $info['ProductReference'] = sprintf('EAN %013s',$source->ProductReference);

        $title = config("fairmondoproduct.TitleTemplate");

        foreach ($info as $key => $value) {
            $title = str_replace("%$key",$value,$title);
        }

        if(strlen($title) > 200) {
            $overflow = strlen($title) - strlen($info['Title']);
            $trimmedTitle = self::cleanTrim($info['Title'], 200-$overflow);
            $title = str_replace($info['Title'],$trimmedTitle,$title);
        }

        // trim title to maximal length
        return self::removeForbiddenChars($title);
    }

    /**
     * Trim a string while making sure to not split any words or multibyte characters.
     */
    private static function cleanTrim($text,$number_of_characters) {
        return (strlen($text) < $number_of_characters) ? $text : substr($text, 0, strrpos(substr($text, 0, $number_of_characters - 3), ' '))."...";
    }

    /**
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
            $categories[] = $map[$source->ProductForm];
        }

        if(!$categories) throw new MissingDataException("Product doesn't belong to any categories.",$source->ProductReference);

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
        if(key_exists($productForm,$map)) return $map[$productForm];
        else return "";
    }

    /*
     * Returns a human readable description of the product language.
     */
    private static function getProductLanguageDescription($productLanguage) {
        $map = config('fairmondoproduct.maps.ProductLanguage');
        if(key_exists($productLanguage,$map)) return $map[$productLanguage];
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

        // Using strval() because of weird intval() behaviour.
        // See http://php.net/manual/de/function.intval.php#101439
        $price_cents = intval(strval($source->PriceAmount * 100));
        return $price_cents;
    }

    /**
     * Returns formatted date as string.
     *
     * @return string
     */
    private static function formatDate($date) {
        $formatter = new IntlDateFormatter('de_DE', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
        $formatter->setPattern('MMMM y');

        // attempt to create DateTime object from $date
        $possibleFormats = ['Ym','Ymd'];
        foreach($possibleFormats as $format) {
            $dateTime = DateTime::createFromFormat($format,$date);
            if($dateTime) break;
        }

        return $formatter->format($dateTime);
    }

    /**
     * Builds the content from the patterns defined in the config.
     *
     * @return string
     */
    public static function getContent(LibriProduct $source)
    {
        // holder for rendered templates
        $renderedTemplates = [];

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
                    $value = self::cleanTrim(self::getBlurb($source),9000);     // trim content before adding html code
                    $value = self::removeForbiddenChars($value);                // remove forbidden characters
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

        return $content;
    }

    public static function removeForbiddenChars($text) {
        $forbiddenChars = config('fairmondoproduct.ForbiddenCharacters');
        foreach($forbiddenChars as $needle => $replacement) {
            $text = str_replace($needle,$replacement,$text);
        }
        return $text;
    }

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
        if($source->AntCbildUrl) return $source->AntCbildUrl;
        else {

            $filepath = /*substr($source->ProductReference, -3,3).*/ sprintf('%s.jpg',$source->RecordReference);


            if(file_exists(storage_path('app/media/').$filepath)) {
                // if no CoverLink available, build it from ProductReference (EAN)
                return config('fairmondoproduct.CoverLinkBaseUrl').$filepath;
            } else {
                return "";
            }
        }
    }

    public static function getExternalImage2Url(LibriProduct $source) {
        if($source->AntRueckUrl) return $source->AntRueckUrl;
        if($source->AntAbildUrl) return $source->AntAbildUrl;
        return null;
    }

    public static function getGtin(LibriProduct $source) {
        return $source->ProductReference;
    }

    public static function getCustomSellerIdentifier(LibriProduct $source) {
        return $source->RecordReference;
    }

    /**
     * Returns the appropiate action for the product.
     *
     * @param LibriProduct $source
     * @return mixed|string
     */
    public static function getAction(LibriProduct $source) {
        // check if the notification type is a deletion
        if($source->NotificationType == "05") {
            $action = self::ACTION_DELETE;
        } else if(property_exists($source,'action')) {
            // if the LibriProduct came from selected_products table it will have the `action` attribute set
            $action = $source->action;
        } else {
            // try to get product details from database
            $previousRecord = FairmondoProduct::find(self::getCustomSellerIdentifier($source));

            // all other notification types signal an update to an old record or a new record
            // if the old record was found and it wasn't meant to be deleted, set action to 'update'
            if($previousRecord and $previousRecord->action != self::ACTION_DELETE) {
                $action = self::ACTION_UPDATE;
            }
            // otherwise the action is 'create'
            else {
                $action = self::ACTION_CREATE;
            }
        }
        return $action;
    }

    /**
     * Returns estimated transport time as string.
     *
     * @param LibriProduct $source
     * @return string
     */
    public static function getTransportTime(LibriProduct $source) {
        if($source->OrderTime <= 4) {
            $transportTime = '1-4';
        } else if($source->OrderTime <= 10) {
            $transportTime = '5-10';
        } else {
            $transportTime = $source->OrderTime + 1;
        }
        return $transportTime;
    }

    /**
     * Returns additional description if available, otherwise null.
     *
     * @param LibriProduct $source
     * @return string
     */
    public static function getBlurb(LibriProduct $source) {
        $result = KtextAnnotation::where('ProductReference', $source->ProductReference)->take(1)->get();
        if(count($result) > 0) return $result[0]->AnnotationContent;
        else return null;
    }
}
