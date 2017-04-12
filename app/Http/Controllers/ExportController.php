***REMOVED***

namespace App\Http\Controllers;

use App\Facades\ConsoleOutput;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public static function makeDelta($startDate, $testrun = false) {
        $filepath = storage_path('app/export/')."Export-".time()."-%s.csv";
        $zipArchive = storage_path('app/export/')."Export-".time().".zip";
        $chunkSize = 40000;
        $lastExport = Export::orderBy('created_at','desc')->take(1)->get();
        if(count($lastExport) > 0) {
            ConsoleOutput::info("Previous Export found. Selecting all new records since ".$lastExport[0]['created_at']);
            $query = LibriProduct::where('updated_at','>',$lastExport[0]['created_at']);
        } else {
            ConsoleOutput::info("No previous export found. Selecting all records");
            $query = LibriProduct::where('updated_at','>','');                          // don't use ::all() ! will result in memory exhaust
        }

        // generate progress bar
        $numberOfItems = $testrun ? 100 : $query->count();
        $progress = ConsoleOutput::progress($numberOfItems);

        $files = [***REMOVED***
        $productHandler = function($products) use ($progress,&$files,$filepath,$testrun) {

            // intiate csv holder
            $export = self::getExportBuffer();

            foreach ($products as $product) {

                // get Fairmondo Product
                $fairmondoProduct = self::getFairmondoProduct($product);

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

        if($testrun) $productHandler($query->take(100)->get());
        else $query->chunk($chunkSize, $productHandler);

        ConsoleOutput::info("Export finished.");

        // make Zip Archive
        if(ZipController::makeArchive($zipArchive,$files) && file_exists($zipArchive)) {
            ConsoleOutput::info("Created ZipArchive at $zipArchive.");

            // Save Export Info to Database
            if(!$testrun) {
                $export = new Export();
                $export->number_of_products = $numberOfItems;
                $export->export_file = basename($zipArchive);
                $export->save();
            }

        } else {
            ConsoleOutput::error("Creating ZipArchive at $zipArchive failed.");
        }

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

    public static function getFairmondoProduct(LibriProduct $product) {
        if(FairmondoProductBuilder::meetsRequirements($product)) {
            // convert data into Fairmondo Product
            return FairmondoProductBuilder::create($product);
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
