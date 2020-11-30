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
use XMLReader;

class AnnotationFactory implements IFactory
{
    /*
     * Returns an Annotation wrapped in an array.
     */
    public static function makeFromFile(string $filepath, $renameFile = false): array
    {

        switch (substr($filepath,-3,3)) {
            case "xml":
                $annotation = self::makeCbildAnnotation($filepath);
                break;
            case "HTM":
                $annotation = self::makeTextAnnotation($filepath);
                break;
            case "JPG":
                $annotation = self::makePictureAnnotation($filepath, $renameFile);
                break;
            default:
                throw new Exception("Unsupported Annotation.");
        }

        return [$annotation];
    }

    /*
     * Copies the picture to storage.
     */
    private static function makePictureAnnotation($filepath, $renameFile) {
        $parts = explode('_',basename($filepath));
        $productReference = $parts[1];
        list($annotationType,$extension) = explode('.',$parts[3]);

        $destination = 'app/media'.DIRECTORY_SEPARATOR
                        .substr($productReference,-3,3).DIRECTORY_SEPARATOR
                        .sprintf('/EAN_%013s.%s',$productReference,strtolower($extension));

        @mkdir(dirname(storage_path($destination)));

        if($renameFile) {
            rename($filepath, storage_path($destination));
        } else {
            copy($filepath, storage_path($destination));
        }

        // no need to return an object since we're not storing picture annotations in the database
        return null;
    }

    /*
     * Returns CBILD Urls
     */
    private static function makeCbildAnnotation($filepath) {
        $reader = new XMLReader();
        $reader->open($filepath);

        $user = env("LIBRI_USER");
        $password = env('LIBRI_MEDIA_PASSWORD');
        $needle = array('$$URL$$','$$USER$$');
        $replace = array("http://$user:$password@media.librinet.de","$user/");

        $cblid = array();
        while($reader->read()) {
            if($reader->name === 'content')  {
                $xml = simplexml_load_string($reader->readOuterXml());

                foreach($xml->link as $link) {
                    if((string) $link->url != "" && (string) $link->size == 'xl') {
                        /*$cbild[] = array(
                            'docid' => (string) $xml->docid,
                            'ean'   => (string) $xml->ean,
                            'type'  => (string) $link->type,
                            'size'  => (string) $link->size,
                            'url'   => str_replace($search,$replace,(string) $link->url)
                        );*/
                        $remote_url = str_replace($needle,$replace,(string) $link->url);
                        $local_path = "storage/app/media/EAN_".(string) $xml->ean.".jpg";
                        if(file_put_contents($local_path,file_get_contents($remote_url))) {
                            print "Downloaded $remote_url to $local_path\n";
                        } else {
                            print "Failed to download $remote_url\n";
                        }
                    }
                }

            }
        }
        $reader->close();
        return 0;//$cbild;
    }

    /*
     * Returns a KtextAnnotation.
     */
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

        try {
            // if ProductReference wasn't found, there might be something wrong with the file, let's store it for further inspection.
            if($annotation->ProductReference === NULL) {
                $failedFile = storage_path('failed_imports').'/'.basename($filepath);
                @mkdir(dirname($failedFile));
                copy($filepath,$failedFile);
                throw new Exception("Annotation doesn't contain ProductReference. Malformed file was copied to $failedFile.");
            }

            $annotation->AnnotationContent = trim($dom->getElementsByTagName("body")->item(0)->nodeValue);
        } catch (Exception $e) {
            Log::info("Exception parsing $filepath",[$e]);
            return null;
        }


        return $annotation;
    }

    /*
     * Saves an array of annotations to database.
     */
    public static function store(array $annotations): bool
    {

        foreach ($annotations as $annotation) {

            if(is_null($annotation) or is_array($annotation)) continue;     // todo: should throw exception

            // delete previous records
            KtextAnnotation::where('ProductReference',$annotation->ProductReference)->delete();

            // insert new record
            var_dump($annotation->save());
        }

        return true;
    }

}