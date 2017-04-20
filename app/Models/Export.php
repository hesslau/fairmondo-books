***REMOVED***

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    public function scopeLatest($query) {
        $query->orderBy('created_at','desc')->take(1);
    }
}
