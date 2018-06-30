<?php
/**
 * Created by PhpStorm.
 * User: hesslau
 * Date: 1/16/17
 * Time: 3:34 PM
 */

namespace App\Exceptions;
use Exception;

class MissingDataException extends Exception {
    protected $reference;
    protected $rawData;

    public function getReference() {
        return $this->reference;
    }

    public function getRawData() {
        return $this->rawData;
    }

    public function __construct($message = "", $reference = null, $rawData = null, Exception $previous = null) {
        $this->reference = $reference;
        $this->rawData = $rawData;
        parent::__construct($message,0,$previous);
    }
}