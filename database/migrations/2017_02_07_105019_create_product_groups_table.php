***REMOVED***

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ProductGroups', function (Blueprint $table) {
            $table->integer('ProductGroupID');
            $table->string('ProductGroupDescription');
            $table->string('BooksEcobookstoreDescription');
            $table->integer('BooksFairnopolyID');
            $table->string('BooksFairnopolyDescription');
            $table->string('BooksGoogleDescription');

            $table->primary('ProductGroupID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ProductGroups');
    }
}
