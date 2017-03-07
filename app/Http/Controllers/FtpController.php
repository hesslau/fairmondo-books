***REMOVED***

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use ConsoleOutput;
use Illuminate\Routing\Controller as BaseController;
use App\FtpSettings;
use ErrorException;

class FtpController extends BaseController
{
    private $connection;
    private $settings;

    public function __construct(FtpSettings $settings=null) {
        if(is_null($settings)) {
            $settings = new FtpSettings();
        }
        $this->settings = $settings;
    }

    public function setDirectory($newDirectory) {
        $this->settings->setDirectory($newDirectory);
    }

    public function connect() {
        // connect
        $this->connection = ftp_connect($this->settings->host) or die('could not connect to '.$this->settings->host);

        // login
        ftp_login($this->connection, $this->settings->user, $this->settings->password) or die('could not login.');

        // enter passive mode
        ftp_pasv($this->connection, true) or die('could not enable passive mode.');

        return true;
    }

    public function reconnect() {
        ftp_close($this->connection);
        $this->connect();
    }

    public function getFileList($detailed=false) {
        if(!$this->connection) $this->connect();

        if($detailed) {
            $filelist = $this->getDetailedFileList();
        } else {
            $filelist = ftp_nlist($this->connection, $this->settings->directory);
        }
        return $filelist;
    }

    public function getDetailedFileList() {
        if(!$this->connection) $this->connect();

        if (is_array($children = @ftp_rawlist($this->connection, $this->settings->directory))) {
            $items = array();

            foreach ($children as $child) {
                $chunks = preg_split("/\s+/", $child);
                list($item['rights'], $item['number'], $item['user'], $item['group'], $item['size'], $item['month'], $item['day'], $item['time']) = $chunks;
                $item['type'] = $chunks[0]{0} === 'd' ? 'directory' : 'file';
                array_splice($chunks, 0, 8);
                $items[implode(" ", $chunks)] = $item;
            }

            return $items;
        }

        // @todo
        // Throw exception or return false < up to you
    }

    /**
     * Downloads the file at $remoteFilepath to $downloadDirectory
     *
     * @param $remoteFilepath Absolute path to file
     * @param null $downloadDirectory
     * @return bool|string False on failure otherwise the path to downloaded file.
     */
    public function downloadFile($remoteFilepath, $downloadDirectory=null) {
        if(!$this->connection) $this->connect();

        // if no download Directory was given, download to default dir
        if(!$downloadDirectory) $downloadDirectory = $this->settings->downloadDirectory;

        $localFilepath = $downloadDirectory.DIRECTORY_SEPARATOR.basename($remoteFilepath);

        if(ftp_get($this->connection,$localFilepath,$remoteFilepath, FTP_BINARY)) {
            return $localFilepath;
        } else {
            return false;
        }

    }
}
