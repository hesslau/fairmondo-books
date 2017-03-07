***REMOVED***
/**
 * Created by PhpStorm.
 * User: hesslau
 * Date: 2/27/17
 * Time: 3:54 PM
 */

namespace App;


class FtpSettings {

    public $host;
    public $user;
    public $password;
    public $directory;
    public $downloadDirectory;

    public function __construct(array $config=null) {
        if(is_null($config)) $config = config('ftp.updates');

        if(!isset($config['host'])) throw new Exception("Host missing.");
        if(!isset($config['user'])) throw new Exception("User missing.");
        if(!isset($config['password'])) $config['password'] = '';
        if(!isset($config['directory'])) $config['directory'] = DIRECTORY_SEPARATOR;
        if(!isset($config['downloadDirectory'])) $config['downloadDirectory'] = storage_path('app/downloads');

        $this->host = $config['host'***REMOVED***
        $this->user = $config['user'***REMOVED***
        $this->password = $config['password'***REMOVED***
        $this->directory = $config['directory'***REMOVED***
        $this->downloadDirectory = $config['downloadDirectory'***REMOVED***
    }

    public function setDirectory($dir) {
        $this->directory = $dir;
    }
}