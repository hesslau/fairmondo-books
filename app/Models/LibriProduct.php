***REMOVED***

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
        $digitalProductForms = ['AC','DB','DA','AI','VI','VO','ZE','00'***REMOVED***
        return in_array($this->attributes['ProductForm'],$digitalProductForms);
    }

    public function isAudioBook() {
        if(!key_exists('VLBSchemeOld',$this->attributes)) return false;
        $productGroup = $this->attributes['VLBSchemeOld'***REMOVED***

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
                        ->where(function ($query) {                                     // select titles in stock
                            $query  ->where("QuantityOnHand", ">", 0)
                                    ->orWhere("AvailabilityStatus", "22");
                        });
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
            return $this->relations[$key***REMOVED***
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
