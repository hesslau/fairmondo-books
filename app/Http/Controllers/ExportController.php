<?php

namespace App\Http\Controllers;

use App\Facades\ConsoleOutput;
use Illuminate\Http\Request;
use League\Csv\Writer;
use App\Models\FairmondoProduct;
use App\Models\LibriProduct;
use App\Factories\FairmondoProductBuilder;

class ExportController extends Controller
{
    /**
     * Collects all changes made to libri database since specified date and exports them into FairmondoProduct format.
     */
    public static function makeDelta($startDate, $testrun = false) {
        $export = self::getExportBuffer();
        $lastDelta = '2017-01-01';
        $query = LibriProduct::where('updated_at','>',$lastDelta);

        // if this is a testrun, break this loop early
        if($testrun) $query->take(50);

        // apply query
        $products = $query->get();

        // generate progress bar
        $progress = ConsoleOutput::progress(count($products));

        foreach ($products as $product) {
            if(FairmondoProductBuilder::meetsRequirements($product)) {

                // convert data into Fairmondo Product
                $fairmondoProduct = FairmondoProductBuilder::create($product);

                // delete previous records
                FairmondoProduct::destroy($fairmondoProduct->gtin);

                // save new record
                $fairmondoProduct->save();

                // write to export
                $export->insertOne($fairmondoProduct->toArray());
            } else {
                // product doesn't meet required conditions to become fairmondo product
            }

            // advance progressbar
            ConsoleOutput::advance($progress);
        }

        self::writeToFile($export->__toString());
    }

    private static function writeToFile($content) {
        $exportFileHandle = fopen(storage_path('app/export/export.csv'),'w');
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
