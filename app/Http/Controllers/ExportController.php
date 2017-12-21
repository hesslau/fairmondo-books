***REMOVED***

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
use Carbon\Carbon;

class ExportController extends Controller
{
    /**
     * Converts all LibriProducts into FairmondoProducts and writes them to disc.
     */
    public static function makeDelta($startDate, $skip=0, $testrun = false) {
        $filepath = storage_path('app/export/')."Export-".date('Ymd')."-%s.csv";
        $zipArchive = storage_path('app/export/')."Export-".date('Ymd').".zip";
        $chunkSize = 20000;

        if(file_exists($zipArchive)) throw new Exception("File $zipArchive already exists.");

        // Create New Export to get correct start time
        $exportInfo = new Export();
        if ($testrun) {
            Log::info("Test-Export started.");

        } else {
            Log::info("Export #{$exportInfo->id} started.");
            $exportInfo->save();
        }


        try {

            // get date of last completed Export
            $latestExport = Export::completed()->latest()->first();

            // if there is no latest export we use the start of the unix epoche
            $dateOfLatestExport = ($latestExport) ? $latestExport['created_at'] : Carbon::createFromTimestamp(0);

            // select Products which where updated after latest export
            $selectedProducts = self::selectProducts($dateOfLatestExport);

            // get product count
            $numberOfItems = $testrun ? 1000 : $selectedProducts->count() - $skip;

            // exit here if we have nothing to export
            if($numberOfItems === 0) throw new Exception("Nothing to export.");

            // update Export object
            $exportInfo->number_of_products = $numberOfItems;

            // display progress bar
            $progress = ConsoleOutput::progress($numberOfItems);

            // skip items
            if ($skip > 0) $selectedProducts = $selectedProducts->skip($skip);

            // build producthandler
            $files = [***REMOVED***
            $productHandler = self::getProductHandler($progress, $files, $filepath, $testrun);

            // apply producthandler to selected products
            if ($testrun) $productHandler($selectedProducts->take(1000)->get());
            else $selectedProducts->chunk($chunkSize, $productHandler);

            // exit if no files were created
            if(count($files) === 0) throw new Exception("No files were created.");

            // pack the files
            ZipController::makeArchive($zipArchive, $files);

            // Check if archive was created
            if (!file_exists($zipArchive)) throw new Exception("Could not create archive at $zipArchive.");

            // update export object
            $exportInfo->export_file = basename($zipArchive);

            // delete the csv files
            foreach ($files as $file) {
                @unlink($file);
            }

            // If we got to here, we can assume that everything went well.
            Log::info("Export #{$exportInfo->id} finished. (Products: {$exportInfo->number_of_products} File: {$exportInfo->export_file})");

        } catch(Exception $e) {

            // Something didn't go well. Write a log message and continue throwing the error.
            Log::error("Export #{$exportInfo->id} from {$exportInfo->created_at} failed: {$e->getMessage()}");
            // throw $e; // @todo: should we throw this or not?

        } finally {

            $exportInfo->finished_at = Carbon::now();

            // Update Export Model in Database
            if (!$testrun) {
                $exportInfo->save();
                Log::info("Export #{$exportInfo->id} finished.");
            } else {
                Log::info("Test-Export finished.");
            }

            return $exportInfo;

        }

    }

    private static function getProductHandler($progress,&$files,$filepath,$testrun) {
        return function($products) use ($progress,&$files,$filepath,$testrun) {

            // intiate csv holder
            $export = self::getExportBuffer();

            foreach ($products as $product) {

                try {

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
                } catch(\App\Exceptions\MissingDataException $e) {
                    Log::error("Failed to convert ".$product->ProductReference.". ".$e->getMessage());
                }

            }

            // finally write all to export file
            $filename = sprintf($filepath,count($files));           // replaces %s with index number
            self::writeToFile($export->__toString(),$filename);
            ConsoleOutput::info("\nChunk exported to $filename.");

            // move to next chunk
            $files[] = $filename;
        };

    }

    private static function query($query){
        return DB::unprepared(DB::raw($query));
    }

    public static function exportProducts($gtins) {
        $export = self::getExportBuffer();

        foreach ($gtins as $gtin) {
            $libriProduct = LibriProduct::find($gtin);
            if(!$libriProduct) ConsoleOutput::error("Prduct with reference '$gtin' not found.");
            else {
                $fairmondoProduct = FairmondoProductBuilder::create($libriProduct);
                /* TODO: Should the changes be written to the replication database in this use case? */
                // self::storeFairmondoProduct($fairmondoProduct);
                $export->insertOne($fairmondoProduct->toArray());
            }
        }

        $exportfile = sprintf("%s/Export-%s-%s.csv",storage_path('app/export'),date('Ymd'),date('U'));
        self::writeToFile($export->__toString(),$exportfile);
        ConsoleOutput::info("Exported to $exportfile.");
        return $exportfile;
    }


    public static function selectProducts($dateOfLatestExport) {
        $dropTempTable = self::query("TRUNCATE TABLE selected_products ;");

        //$createTempTable = self::query("create temporary table selected_products (gtin varchar(13) not null primary key,action varchar(6));");
        //print $createTempTable;

        ConsoleOutput::info("Selecting Products eligible for Fairmondo Market updated since $dateOfLatestExport.");
        $filterLibriProducts = self::query("insert into selected_products select ProductReference, 'create' from libri_products where 
                                            created_at > '$dateOfLatestExport' 
                                            and AvailabilityStatus in ('20','21','23')
                                            and ProductForm in ('BA','BB','BC','BG','BH','BI','BP','BZ','AC','DA','AI','VI','VO','ZE','DG','PC')
                                            and NotificationType in ('03','05')
                                            and AudienceCodeValue not in ('16','17','18')
                                            and PriceAmount between 0.99 and 10000.00;");

        // mark products for deletion which were updated in libri_products and exist in the current market fairmondo_products but didn't make it into selected_products
        ConsoleOutput::info("Marking ineligible Products in Market for deletion.");
        $deleteUnqualifiedFairmondoProducts = self::query("insert ignore into selected_products select gtin,'delete' from fairmondo_products,libri_products where libri_products.created_at > '$dateOfLatestExport' and fairmondo_products.created_at > '$dateOfLatestExport' and gtin=ProductReference;");

        // mark products for update which are selected for market and already exist in the market
        ConsoleOutput::info("Marking eligible Products in Market for update.");
        $updateQualifiedFairmondoProducts = self::query("update selected_products,fairmondo_products set selected_products.action='update' where selected_products.gtin=fairmondo_products.gtin and selected_products.action<>'delete';");

        return DB::table('selected_products')->join('libri_products','ProductReference','=','gtin');
    }

    /*
        Attempts to rollback changes made to replication database.
    */
    public static function rollbackLatestExport() {

        // find latest export (complete or incomplete)
        $export = Export::latest()->first();

        // find all records that were created in this export
        $products = FairmondoProduct::where('created_at','>',$export['created_at']);

        // delete records with action = delete
        $deleteCreated = $products->where('action','create')->delete();
        ConsoleOutput::info("Removed $deleteCreated records from products database.");

        // change records that indicate a deletion to be merely updates
        $updateDeleted = $products->where('action','delete')->update(['action' => 'update']);
        ConsoleOutput::info("Changed $updateDeleted deletions to be updates.");

        // remove export file
        $exportFile = storage_path('app/export/').$export->export_file;
        if($export->export_file && file_exists($exportFile)) {
            @unlink($exportFile);
            ConsoleOutput::info("Removed $exportFile.");
        }

        return $export->delete();
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

    public static function writeToFile($content,$filename) {
        if(file_exists($filename)) {
            ConsoleOutput::info("File $filename already exists. Will be deleted.");
            unlink($filename);
        }
        $exportFileHandle = fopen($filename,'w');
        fwrite($exportFileHandle,$content);
    }

    public static function getExportBuffer() {
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
