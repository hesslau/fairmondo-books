***REMOVED***

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FairmondoProduct extends Model
{
    protected $primaryKey = 'gtin';
    public $incrementing = false;

    public function toArray($fields = null) {
        if(null === $fields) $fields = config('fairmondoproduct.fields');

        $productAsArray = array();

        foreach ($fields as $field) {
            $productAsArray[$field] = $this->$field;
        }

        return $productAsArray;
    }
}
