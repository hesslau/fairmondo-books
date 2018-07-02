<?php
/**
 * Created by PhpStorm.
 * User: hesslau
 * Date: 2/14/17
 * Time: 3:57 PM
 */

namespace App;


class DatabaseSupervisor
{
    private $insertHalted;

    public function __construct() {}

    public static function Instance() {
        static $inst = null;
        if(is_null($inst)) $inst = new DatabaseSupervisor();
        return $inst;
    }

    public function insertionsHalted() {
        return $this->insertHalted;
    }

    public function haltInsertions() {
        $this->insertHalted = true;
    }

    public function allowInsertions() {
        $this->insertHalted = false;
    }
}