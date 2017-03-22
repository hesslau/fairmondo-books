<?php

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

        $annotation = AnnotationFactory::makeFromFile($testfile)[0];

        $this->assertEquals($productReference, $annotation->ProductReference);
        $this->assertEquals($annotationType, $annotation->AnnotationType);
        $this->assertEquals($annotationContent, $annotation->AnnotationContent);
    }

    public function testStoreAnnotation() {
        $testfile = storage_path("testing/Annotations/EN_9780141024677_35337_KTEXT.HTM");
        $annotations = AnnotationFactory::makeFromFile($testfile);

        AnnotationFactory::store($annotations);

        $annotation = $annotations[0];

        $query = Annotation::where([
            'ProductReference' => $annotation->ProductReference,
            'AnnotationType' => $annotation->AnnotationType,
            'AnnotationLanguage' => $annotation->AnnotationLanguage
        ]);

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

    public function testPictureAnnotation() {
        $testfile = storage_path('testing/Annotations/EN_9789944387255_30_CBILD.JPG');
        $testfileCopy = storage_path('app/annotations').DIRECTORY_SEPARATOR.basename($testfile);
        $productReference = '9789944387255';
        $annotationType = 'CBILD';
        $expectedPath = storage_path('app/media').DIRECTORY_SEPARATOR.'255'.DIRECTORY_SEPARATOR.$productReference.'.JPG';

        copy($testfile,$testfileCopy);
        list($annotation) = AnnotationFactory::makeFromFile($testfileCopy);

        $this->assertEquals($productReference,$annotation->ProductReference);
        $this->assertEquals($annotationType,$annotation->AnnotationType);
        $this->assertEquals($expectedPath,storage_path($annotation->AnnotationContent));
        $this->assertTrue(file_exists($expectedPath));
        unlink($expectedPath);

    }
}
