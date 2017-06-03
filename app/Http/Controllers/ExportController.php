<?php

namespace App\Http\Controllers;

use App\Facades\ConsoleOutput;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Writer;
use App\Models\FairmondoProduct;
use App\Models\LibriProduct;
use App\Factories\FairmondoProductBuilder;
use Exception;
use SebastianBergmann\Environment\Console;
use App\Models\Export;

class ExportController extends Controller
{
    /**
     * Converts all LibriProducts into FairmondoProducts and writes them to disc.
     */
    public static function makeDelta($startDate, $skip=0, $testrun = false) {
        $filepath = storage_path('app/export/')."Export-".date('Ymd')."-%s.csv";
        $zipArchive = storage_path('app/export/')."Export-".time('Ymd').".zip";
        $chunkSize = 20000;
        /*$lastExport = Export::latest()->get();
        if(count($lastExport) > 0) {
            ConsoleOutput::info("Previous Export found. Selecting all new records since ".$lastExport[0]['created_at']);
            $query = LibriProduct::selectFairmondoProducts()->updatedSince($lastExport[0]['created_at']);
        } else {
            ConsoleOutput::info("No previous export found. Selecting all records");
            $query = LibriProduct::selectFairmondoProducts();  // don't use ::all() ! will result in memory exhaust
        }*/

        self::prepareExport();
        $query = DB::table('selected_products')->join('libri_products','ProductReference','=','gtin');

        // generate progress bar
        $numberOfItems = $testrun ? 1000 : $query->count() - $skip;
        $progress = ConsoleOutput::progress($numberOfItems);

        // skip items
        if($skip>0) $query = $query->skip($skip);

        $files = [];
        $productHandler = function($products) use ($progress,&$files,$filepath,$testrun) {

            // intiate csv holder
            $export = self::getExportBuffer();

            foreach ($products as $product) {

                // build LibriProduct
                $libriProduct = with(new LibriProduct)->newFromStd( $product );

                // get Fairmondo Product
                $fairmondoProduct = self::getFairmondoProduct($libriProduct);


                if(!is_null($fairmondoProduct)) {
                    // write to export file
                    $export->insertOne($fairmondoProduct->toArray());

                    // save the product to database
                    if(!$testrun) self::storeFairmondoProduct($fairmondoProduct);
                }

                // advance progress bar
                ConsoleOutput::advance($progress);
            }

            // finally write all to export file
            $filename = sprintf($filepath,count($files));
            self::writeToFile($export->__toString(),$filename);
            ConsoleOutput::info("\nChunk exported to $filename.");

            // move to next chunk
            $files[] = $filename;
        };

        if($testrun) $productHandler($query->take(1000)->get());
        else $query->chunk($chunkSize, $productHandler);

        ConsoleOutput::info("Export finished.");

        // make Zip Archive
        if(ZipController::makeArchive($zipArchive,$files) && file_exists($zipArchive)) {
            ConsoleOutput::info("Created ZipArchive at $zipArchive.");

            // delete the csv files
            foreach ($files as $file) {
                @unlink($file);
            }

            // Save Export Info to Database
            if(!$testrun) {
                $export = new Export();
                $export->number_of_products = $numberOfItems;
                $export->export_file = basename($zipArchive);
                $export->save();
            }

            return $zipArchive;
        } else {
            ConsoleOutput::error("Creating ZipArchive at $zipArchive failed.");
            return false;
        }


    }

    private static function query($query){
        return DB::unprepared(DB::raw($query));
    }

    public static function prepareExport() {
        $date = Export::latest()->get()[0]['created_at'];
        print $date;

        $dropTempTable = self::query("TRUNCATE TABLE selected_products ;");

        //$createTempTable = self::query("create temporary table selected_products (gtin varchar(13) not null primary key,action varchar(6));");
        //print $createTempTable;


        $filterLibriProducts = self::query("insert into selected_products select ProductReference, 'create' from libri_products where 
                                            created_at > '$date' 
                                            and AvailabilityStatus in ('20','21','23')
                                            and ProductForm in ('BA','BB','BC','BG','BH','BI','BP','BZ','AC','DA','AI','VI','VO','ZE','DG','PC')
                                            and NotificationType in ('03','05')
                                            and AudienceCodeValue not in ('16','17','18');");

        $deleteUnqualifiedFairmondoProducts = self::query("insert ignore into selected_products select gtin,'delete' from fairmondo_products,libri_products where libri_products.created_at > '$date' and gtin=ProductReference;");

        $updateQualifiedFairmondoProducts = self::query("update selected_products,fairmondo_products set selected_products.action='update' where selected_products.gtin=fairmondo_products.gtin and selected_products.action<>'delete';");

    }

    private static function storeFairmondoProduct($product) {
        // delete previous records
        FairmondoProduct::destroy($product->gtin);

        // save new record
        try {
            $product->save();
            return true;
        } catch (QueryException $e) {
            $msg = "Query Exception:". $e->getMessage();
            ConsoleOutput::error($msg);
            Log::error($msg);
            return false;
        }
    }

    public static function getFairmondoProduct($product) {
        if(FairmondoProductBuilder::meetsRequirements($product)) {
            // convert data into Fairmondo Product
            $p = FairmondoProductBuilder::create($product);
            return $p;
        } else {
            // product doesn't meet required conditions to become fairmondo product
            return null;
        }
    }

    private static function writeToFile($content,$filename) {
        if(file_exists($filename)) {
            throw new Exception("File $filename already exists.");
        }
        $exportFileHandle = fopen($filename,'w');
        fwrite($exportFileHandle,$content);
    }

    private static function getExportBuffer() {
        $headers = config('fairmondoproduct.fields');
        $csv = Writer::createFromFileObject(new \SplTempFileObject());
        $csv->setDelimiter(';');

        // The fairmondo marketplace CSV file needs the first character to be a € sign
        // to prove the correct character formatting.
        // So we add the € character in the headers.
        $headers = array_merge(['€'],$headers);
        $csv->insertOne($headers);

        // And we add a row formatter that prefixes each row with an empty column.
        $csv->addFormatter(function(array $row){
            return array_merge([''],$row);
        });

        return $csv;
    }

}
