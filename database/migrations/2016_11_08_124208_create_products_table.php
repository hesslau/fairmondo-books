<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('libri_products', function (Blueprint $table) {
            $table->primary('ProductReference');
            $table->string('ProductReference');
            $table->string('ProductReferenceType');
            $table->string('RecordReference');
            $table->string('ProductForm');
            $table->string('DistinctiveTitle');
            $table->string('NotificationType');

            $table->string('ProductEAN')->nullable();
            $table->string('ProductISBN10')->nullable();
            $table->string('ProductISBN13')->nullable();

            $table->string('Author')->nullable();
            $table->string('CoverLink')->nullable();
            $table->string('AudienceCodeValue')->nullable();
            $table->string('ProductLanguage')->nullable();
            $table->string('PublisherName')->nullable();
            $table->integer('NumberOfPages')->nullable();
            $table->integer('PublicationDate')->nullable();
            $table->integer('VLBSchemeOld')->nullable();
            // unused field
            // $table->string('ProductGroupDescription')->nullable();
            $table->integer('ProductHeight')->nullable();
            $table->integer('ProductWidth')->nullable();
            $table->integer('ProductThickness')->nullable();
            $table->integer('ProductWeight')->nullable();
            $table->integer('OrderTime')->nullable();
            $table->integer('QuantityOnHand')->nullable();
            $table->string('AvailabilityStatus')->nullable();

            $table->integer('PriceAmount')->nullable();
            $table->char('TaxRateCode1')->nullable();
            $table->integer('PriceTypeCode')->nullable();
            $table->integer('DiscountPercent')->nullable();
            //$table->string('LibRelevanz')->nullable(); // Field is not being used
            $table->string('Blurb')->nullable();
            $table->string('CatalogUpdate');

            $table->timestamps();
        });

        Schema::create('fairmondo_products', function(Blueprint $table){
            $table->primary('gtin');
            $table->string('gtin');
            $table->string('title');
            $table->string('categories')->nullable();
            $table->string('condition')->nullable();
            $table->string('content')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('price_cents')->nullable();
            $table->integer('vat')->nullable();
            $table->string('external_title_image_url')->nullable();
            $table->boolean('transport_type1')->nullable();
            $table->string('transport_type1_provider')->nullable();
            $table->integer('transport_type1_price_cents')->nullable();
            $table->integer('transport_type1_number')->nullable();
            $table->string('transport_details')->nullable();
            $table->string('transport_time')->nullable();
            $table->boolean('unified_transport')->nullable();
            $table->boolean('payment_bank_transfer')->nullable();
            $table->boolean('payment_paypal')->nullable();
            $table->boolean('payment_invoice')->nullable();
            $table->boolean('payment_voucher')->nullable();
            $table->string('payment_details')->nullable();
            $table->string('custom_seller_identifier')->nullable();
            $table->string('action')->nullable();

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
        Schema::dropIfExists('libri_products');
        Schema::dropIfExists('fairmondo_products');
    }
}
