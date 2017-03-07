***REMOVED***

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Factories\AnnotationFactory;
use App\Models\Annotation;

class AnnotationTest extends TestCase
{
    public function testMakeAnnotation() {
        $testfile = storage_path("testing/Annotations/EN_9780141024677_35337_KTEXT.HTM");
        $productReference = "9780141024677";
        $annotationType = "KTEXT";
        $annotationContent = "This sociopolitical comedy";

        $annotation = AnnotationFactory::makeFromFile($testfile)[0***REMOVED***

        $this->assertEquals($productReference, $annotation->ProductReference);
        $this->assertEquals($annotationType, $annotation->AnnotationType);
        $this->assertEquals($annotationContent, $annotation->AnnotationContent);
    }

    public function testStoreAnnotation() {
        $testfile = storage_path("testing/Annotations/EN_9780141024677_35337_KTEXT.HTM");
        $annotations = AnnotationFactory::makeFromFile($testfile);

        AnnotationFactory::store($annotations);

        $annotation = $annotations[0***REMOVED***

        $query = Annotation::where([
            'ProductReference' => $annotation->ProductReference,
            'AnnotationType' => $annotation->AnnotationType,
            'AnnotationLanguage' => $annotation->AnnotationLanguage
    ***REMOVED***);

        $this->assertNotEmpty($query->get(), "Couldn't find stored Annotation.");
    }

    public function testPulling() {
        $downloadManager = new App\Managers\DownloadManager(
            new \App\FtpSettings(config("ftp.annotations")),
            new \App\Factories\AnnotationFactory(),
            function($filepath) {
                return (substr(basename($filepath),0,6) == "GKTEXT");
            }
        );

        $downloadManager->startPulling();
    }
}
