***REMOVED***

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

    public function isDigital() {
        $digitalProductForms = ['AC','DB','DA','AI','VI','VO','ZE','00'***REMOVED***
        return in_array($this->attributes['ProductForm'],$digitalProductForms);
    }

    public function isAudioBook() {
        $productGroup = (Integer) substr($this->attributes['VLBSchemeOld'],0,2);

        /*  Die Warengruppe, die Musik-CDs umfassend abgebildet ist die 59*.
	        Die Warengruppen 51 bis 58 bezeichnen Hörbücher zu den jeweiligen Themen aus der Warengruppensystematik.
            Da die 559 die Warengruppe „Musik“ (CDs) bezeichnet, können auch da Musik-CDs reingeraten.  */

        // if ProductForm is AC (Audio-CD), DB (CD), DA (MP3-CD) or AI (Audio-DVD) and ProductGroup is non-music it's an audiobook.
        if(in_array($this->attributes['ProductForm'],['AC','DB','DA','AI']) and $productGroup >= 51 and $productGroup <= 58) {
            return true;
        } else {
            return false;
        }
    }
}
