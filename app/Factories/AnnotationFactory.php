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

class AnnotationFactory implements IFactory
{
    public static function makeFromFile(string $filepath): array
    {
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
                    ***REMOVED***null***REMOVED***
            }
        }
        try {
            $annotation->AnnotationContent = trim($dom->getElementsByTagName("body")->item(0)->nodeValue);
        } catch (ErrorException $e) {
            Log::info("ErrorException parsing $filepath",[$e]);
            ***REMOVED***null***REMOVED***
        }

        ***REMOVED***$annotation***REMOVED***
    }

    public static function store(array $annotations): bool
    {
        // display progress bar
        $progress = ConsoleOutput::progress(count($annotations));

        foreach ($annotations as $annotation) {

            // adance progress bar
            ConsoleOutput::advance($progress);

            if(is_null($annotation)) continue;

            // delete previous records
            Annotation::where([
                'ProductReference' => $annotation->ProductReference,
                'AnnotationType' => $annotation->AnnotationType,
                'AnnotationLanguage' => $annotation->AnnotationLanguage
        ***REMOVED***)->delete();

            // insert new record
            $annotation->save();
        }

        ConsoleOutput::finish($progress);
        return true;
    }

}