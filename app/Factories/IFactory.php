***REMOVED***
/**
 * Created by PhpStorm.
 * User: hesslau
 * Date: 2/27/17
 * Time: 12:30 PM
 */

namespace App\Factories;
use Illuminate\Database\Eloquent\Model;
/**
 * An IFactory takes a File and returns an array of Eloquent Models.
 *
 * @package App\Factories
 */
interface IFactory
{
    public static function makeFromFile(string $filepath): array;
    public static function store(array $products): bool;
}