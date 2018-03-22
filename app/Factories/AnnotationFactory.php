<?php
/**
 * Created by PhpStorm.
 * User: hesslau
 * Date: 2/27/17
 * Time: 12:54 PM
 */

namespace App\Factories;
use App\Facades\ConsoleOutput;
use App\Models\Annotation;
use App\Models\KtextAnnotation;
use DOMDocument;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AnnotationFactory implements IFactory
{
    public static function makeFromFile(string $filepath): array
    {

        switch (substr($filepath,-3,3)) {
            case "HTM":
                $annotation = self::makeTextAnnotation($filepath);
                break;
            case "JPG":
                $annotation = self::makePictureAnnotation($filepath);
                break;
            default:
                throw new Exception("Unsupported Annotation.");
        }

        return [$annotation];
    }

    private static function makePictureAnnotation($filepath) {
        $parts = explode('_',basename($filepath));
        $productReference = $parts[1];
        list($annotationType,$extension) = explode('.',$parts[3]);

        $destination = 'app/media'.DIRECTORY_SEPARATOR
                        .substr($productReference,-3,3).DIRECTORY_SEPARATOR
                        .sprintf('/EAN_%013s.%s',$productReference,strtolower($extension));

        @mkdir(dirname(storage_path($destination)));
        copy($filepath,storage_path($destination));

        // no need to return an object since we're not storing picture annotations in the database
        return null;
    }

    private static function makeTextAnnotation($filepath) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);   // don't throw errors on malformed html
        $dom->loadHTMLFile($filepath);

        $annotation = new KtextAnnotation();

        foreach ($dom->getElementsByTagName('meta') as $node) {
            switch($node->getAttribute("name")) {
                case "EAN":
                    $annotation->ProductReference = $node->getAttribute("content");
                    break;
                case "LIBRI":
                    // since we're not listing Libri's internal products, skip this one
                    return null;
            }
        }

        // if ProductReference wasn't found, there might be something wrong with the file, let's store it for further inspection.
        if($annotation->ProductReference === NULL) {
            $failedFile = storage_path('failed_imports').'/'.basename($filepath);
            @mkdir(dirname($failedFile));
            copy($filepath,$failedFile);
            throw new Exception("Annotation doesn't contain ProductReference. Malformed file was copied to $failedFile.");
        }

        try {
            $annotation->AnnotationContent = trim($dom->getElementsByTagName("body")->item(0)->nodeValue);
        } catch (ErrorException $e) {
            Log::info("ErrorException parsing $filepath",[$e]);
            return null;
        }

        return $annotation;
    }

    public static function store(array $annotations): bool
    {
        // display progress bar
        $progress = ConsoleOutput::progress(count($annotations));

        foreach ($annotations as $annotation) {

            // adance progress bar
            ConsoleOutput::advance($progress);

            if(is_null($annotation) or is_array($annotation)) continue;

            // delete previous records
            KtextAnnotation::where('ProductReference',$annotation->ProductReference)->delete();

            // insert new record
            $annotation->save();
        }

        ConsoleOutput::finish($progress);
        return true;
    }

}