<?php

namespace App\Models;

use App\Exceptions\UndefinedPropertyException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\Null_;

class LibriProduct extends Model
{
    protected $primaryKey = 'ProductReference';
    public $incrementing = false;

    // make sure the ProductReference is a String
    public function getProductReferenceAttribute($value) {
        return "$value";
    }

    // todo outsource raw data to config file
    public function isDigital() {
        $digitalProductForms = ['AC','DB','DA','AI','VI','VO','ZE','00'];
        return in_array($this->attributes['ProductForm'],$digitalProductForms);
    }

    public function isAudioBook() {
        if(!key_exists('VLBSchemeOld',$this->attributes)) return false;
        $productGroup = $this->attributes['VLBSchemeOld'];

        /*  Die Warengruppe, die Musik-CDs umfassend abgebildet ist die 59*.
	        Die Warengruppen 51 bis 58 bezeichnen Hörbücher zu den jeweiligen Themen aus der Warengruppensystematik.
            Da die 559 die Warengruppe „Musik“ (CDs) bezeichnet, können auch da Musik-CDs reingeraten.  */

        // if ProductForm is AC (Audio-CD), DB (CD), DA (MP3-CD) or AI (Audio-DVD) and ProductGroup is non-music it's an audiobook.
        if(in_array($this->attributes['ProductForm'],['AC','DB','DA','AI']) and $productGroup >= 5100 and $productGroup <= 5899) {
            return true;
        } else {
            return false;
        }
    }

    public function scopeUpdatedSince($query,$date) {
        return $query->where('updated_at','>',$date);
    }

    public function scopeSelectFairmondoProducts($query)
    {
        $validProductForms = array_keys(config('fairmondoproduct.maps.ProductForm'));
        $validAvailabilityStatus = config('fairmondoproduct.conditions.AvailabilityStatus');
        $invalidAudienceCodeValues = config('fairmondoproduct.conditions.invalidAudienceCodeValues');

        return $query   ->whereIn("ProductForm", $validProductForms)                    // select supported product forms
                        ->whereIn("AvailabilityStatus", $validAvailabilityStatus)       // select available titles
                        ->whereIn("NotificationType", ["03","05"])                      // select published or deleted titles
                        ->whereNotIn("AudienceCodeValue", $invalidAudienceCodeValues)   // select titles which are appropiate for audience
                        ;
    }

    /*
    create temporary table selected_products (gtin varchar(13) not null primary key,action varchar(6));
    insert into selected_products select ProductReference, 'create' from libri_products where created_at > '2017-05-20' and AvailabilityStatus = "20";
    insert ignore into selected_products select gtin,'delete' from fairmondo_products,libri_products where libri_products.created_at > '2017-05-20' and gtin=ProductReference;
    update selected_products,fairmondo_products set selected_products.action='update' where selected_products.gtin=fairmondo_products.gtin and selected_products.action<>'delete';
     */

    public function newFromStd($std) {
        // backup fillable
        $fillable = $this->getFillable();

        $std->DateOfData = new Carbon($std->DateOfData);

        // set id and other fields you want to be filled
        $this->fillable([
            "ProductReference",
            "ProductReferenceType",
            "RecordReference",
            "ProductForm",
            "DistinctiveTitle",
            "NotificationType",
            "ProductEAN",
            "ProductISBN10",
            "ProductISBN13",
            "Author",
            "CoverLink",
            "AudienceCodeValue",
            "ProductLanguage",
            "PublisherName",
            "NumberOfPages",
            "PublicationDate",
            "VLBSchemeOld",
            "ProductHeight",
            "ProductWidth",
            "ProductThickness",
            "ProductWeight",
            "OrderTime",
            "QuantityOnHand",
            "AvailabilityStatus",
            "PriceAmount",
            "TaxRateCode1",
            "PriceTypeCode",
            "DiscountPercent",
            "Blurb",
            "CatalogUpdate",
            "created_at",
            "updated_at",
            "Lib_MSNo",
            "AvailabilityCode",
            "DateOfData",
            "PublishingStatus",
        ]);

        // fill $this->attributes array
        $this->fill((array) $std);

        // fill $this->original array
        $this->syncOriginal();

        $this->exists = true;

        // restore fillable
        $this->fillable($fillable);

        return $this;
    }

    public function getDateOfDataAttribute($date) {
        return is_null($date) ? Null : new Carbon($date);
    }

    public function setDateOfDataAttribute(Carbon $date) {
        $this->attributes['DateOfData'] = $date->toDateTimeString();
    }

    public function getAttribute($key) {
        $inAttributes = array_key_exists($key, $this->attributes);
        if ($inAttributes || $this->hasGetMutator($key)) {
            return $this->getAttributeValue($key);
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            return $this->getRelationshipFromMethod($key);
        }

        throw new UndefinedPropertyException("Property $key for LibriProduct not found.");
    }

    /*
     *  where (
ProductReference is not Null
-- and ProductEAN = ProductReference
and DistinctiveTitle is not Null
-- and ProductLanguage in ('ger', 'eng', 'fre', 'spa', 'ita', 'fin', 'tur', 'dan')
and ProductForm in ('BA', 'BB', 'BC', 'BG', 'BH', 'BI', 'BP', 'BZ', 'AC', 'AI', 'VI', 'VO', 'ZE', 'DA', 'DG', 'PC')
and AvailabilityStatus IN (20,21,22)
and (AudienceCodeValue is Null or (AudienceCodeValue not like '%16%' and AudienceCodeValue not like '%17%' and AudienceCodeValue not like '%18%'))
and (QuantityOnHand > 0 or AvailabilityStatus = 22)
     */
}
