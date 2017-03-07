***REMOVED***

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('libri_updates', function (Blueprint $table) {
            $table->string('id');
            $table->integer('state')->nullable();
            $table->string('remote_filepath');
            $table->string('local_filepath');

            $table->primary('id');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('libri_updates');
    }
}
