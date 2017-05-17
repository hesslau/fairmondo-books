<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

        return $query->whereIn("ProductForm", $validProductForms)
                    ->whereIn("AvailabilityStatus", $validAvailabilityStatus)
                    ->whereNotIn("AudienceCodeValue", $invalidAudienceCodeValues)
                    ->where(function ($query) {
                        $query  ->where("QuantityOnHand", ">", 0)
                                ->orWhere("AvailabilityStatus", "22");
                    });
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
