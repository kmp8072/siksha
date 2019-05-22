<?php

namespace shezar_core\hook;

class phpunit_reset_dataroot extends \shezar_core\hook\base {

    protected $datarootalreadyreset = false;

    public function __construct() {

    }

    public function set_dataroot_already_reset($value = true) {
        $this->datarootalreadyreset = (bool)$value;
    }

    public function is_dataroot_already_reset() {
        return $this->datarootalreadyreset;
    }

}