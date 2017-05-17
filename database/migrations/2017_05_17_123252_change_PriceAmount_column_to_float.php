***REMOVED***

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangePriceAmountColumnToFloat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    DB::statement('ALTER TABLE `libri_products` MODIFY `PriceAmount` FLOAT UNSIGNED NOT NULL;');
    DB::statement('UPDATE libri_products SET PriceAmount = PriceAmount+1');
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
{
    DB::statement('UPDATE libri_products SET PriceAmount = PriceAmount-1');
    DB::statement('ALTER TABLE `libri_products` MODIFY `PriceAmount` INTEGER UNSIGNED NULL;');
}
}