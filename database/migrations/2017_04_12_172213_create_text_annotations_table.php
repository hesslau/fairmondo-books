<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTextAnnotationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ktext_annotations', function(Blueprint $table) {
            $table->string('ProductReference');
            $table->primary('ProductReference');
            $table->text('AnnotationContent',50000);
            $table->string('AnnotationLanguage')->nullable();

            $table->timestamps();
        });

        App\Models\Annotation::chunk(1000, function($chunk) {
            foreach($chunk as $annotation) {
                if($annotation->AnnotationType == 'KTEXT') {

                    $existingKtextAnnotation = \App\Models\KtextAnnotation::find($annotation->ProductReference);
                    if($existingKtextAnnotation) {
                        if($existingKtextAnnotation->date_created > $annotation->date_created) {
                            continue;
                        } else {
                            $existingKtextAnnotation->delete();
                        }
                    }

                    $ktext = new \App\Models\KtextAnnotation();
                    $ktext->ProductReference = $annotation->ProductReference;
                    $ktext->AnnotationContent = $annotation->AnnotationContent;
                    $ktext->AnnotationLanguage = $annotation->AnnotationLanguage;
                    $ktext->created_at = $annotation->created_at;
                    $ktext->save();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ktext_annotations');
    }
}