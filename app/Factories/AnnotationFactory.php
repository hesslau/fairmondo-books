***REMOVED***
/**
 * Created by PhpStorm.
 * User: hesslau
 * Date: 2/27/17
 * Time: 12:54 PM
 */

namespace App\Factories;
use App\Facades\ConsoleOutput;
use App\Models\Annotation;
use DOMDocument;
use ErrorException;
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

        ***REMOVED***$annotation***REMOVED***
    }

    private static function makePictureAnnotation($filepath) {
        $parts = explode('_',basename($filepath));
        $productReference = $parts[1***REMOVED***
        list($annotationType,$extension) = explode('.',$parts[3]);

        $destination = 'app/media'.DIRECTORY_SEPARATOR
                        .substr($productReference,-3,3).DIRECTORY_SEPARATOR
                        .$productReference.'.'.$extension;

        @mkdir(dirname(storage_path($destination)));
        copy($filepath,storage_path($destination));

        $annotation = new Annotation();
        $annotation->AnnotationType = $annotationType;
        $annotation->ProductReference = $productReference;
        $annotation->AnnotationContent = $destination;

        return $annotation;
    }

    private static function makeTextAnnotation($filepath) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);   // don't throw errors on malformed html
        $dom->loadHTMLFile($filepath);

        $annotation = new Annotation();

        foreach ($dom->getElementsByTagName('meta') as $node) {
            switch($node->getAttribute("name")) {
                case "TYPE":
                    $annotation->AnnotationType = $node->getAttribute("content");
                    break;
                case "EAN":
                    $annotation->ProductReference = $node->getAttribute("content");
                    break;
                case "LIBRI":
                    // since we're not listing Libri's internal products, skip this one
                    return null;
            }
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
            /*Annotation::where([
                'ProductReference' => $annotation->ProductReference,
                'AnnotationType' => $annotation->AnnotationType,
                'AnnotationLanguage' => $annotation->AnnotationLanguage
        ***REMOVED***)->delete();*/

            // insert new record
            $annotation->save();
        }

        ConsoleOutput::finish($progress);
        return true;
    }

}