***REMOVED***

namespace App\Http\Controllers;

use App\Facades\ConsoleOutput;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use League\Csv\Writer;
use App\Models\FairmondoProduct;
use App\Models\LibriProduct;
use App\Factories\FairmondoProductBuilder;
use Exception;
use SebastianBergmann\Environment\Console;

class ExportController extends Controller
{
    /**
     * Converts all LibriProducts into FairmondoProducts and writes them to disc.
     */
    public static function makeDelta($startDate, $testrun = false) {
        $numberOfItems = $testrun? 100 : LibriProduct::count();
        $filepath = storage_path('app/export/')."Export-".time()."-%s.csv";
        $zipArchive = storage_path('app/export/')."Export-".time().".zip";
        $chunkSize = 40000;
        $lastExport = FairmondoProduct::orderBy('updated_at','desc')->take(1)->get();
        if(count($lastExport) > 0) {
            $query = LibriProduct::where('updated_at','>',$lastExport[0]['updated_at']);
        } else {
            $query = LibriProduct::where('updated_at','<>','');
        }

        // generate progress bar
        $progress = ConsoleOutput::progress($numberOfItems);

        $files = [***REMOVED***
        $productHandler = function($products) use ($progress,&$files,$filepath,$testrun) {

            // intiate csv holder
            $export = self::getExportBuffer();

            foreach ($products as $product) {


                // get Fairmondo Product
                $fairmondoProduct = self::getFairmondoProduct($product);

                // write to export file
                if(!is_null($fairmondoProduct)) $export->insertOne($fairmondoProduct->toArray());

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
        } else {
            ConsoleOutput::error("Creating ZipArchive at $zipArchive failed.");
        }

    }

    public static function getFairmondoProduct($product) {
        if(FairmondoProductBuilder::meetsRequirements($product)) {

            // convert data into Fairmondo Product
            $fairmondoProduct = FairmondoProductBuilder::create($product);

            // delete previous records
            FairmondoProduct::destroy($fairmondoProduct->gtin);

            // save new record
            try {
                $fairmondoProduct->save();
                return $fairmondoProduct;
            } catch (QueryException $e) {
                $msg = "Query Exception:". $e->getMessage();
                ConsoleOutput::error($msg);
                Log::error($msg);
                return null;
            }
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
