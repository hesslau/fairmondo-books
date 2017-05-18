***REMOVED***
/**
 * Created by PhpStorm.
 * User: hesslau
 * Date: 5/18/17
 * Time: 10:27 AM
 */

namespace App\Managers;


use App\Facades\ConsoleOutput;
use App\Factories\IFactory;

/*
 * Imports files into Database.
 */
class ImportManager
{
    public function __construct(IFactory $_factory) {
        $this->factory = $_factory;
    }

    /*
     * Imports a file into database by calling the factory functions.
     */
    public function importFile($filename) {
        $startTime = microtime(true);

        ConsoleOutput::info("Parse ${filename}:");
        $items = $this->factory->makeFromFile($filename);

        ConsoleOutput::info("Store ${filename}:");
        $this->factory->store($items);

        ConsoleOutput::info(sprintf("Import of $filename took %s seconds.",microtime(true)-$startTime));
    }
}