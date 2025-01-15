<?php


namespace app\util;

use app\core\Config;
use phpssdb\Core\SimpleSSDB;


class SsdbController {

    public static $instance;

    public static function ssdbInstance()
    {
        try {
            static::$instance = new SimpleSSDB(
                Config::$config->storage->ssdb->host,
                Config::$config->storage->ssdb->port
            );
            
            static::$instance->easy();
        }
        catch (\Exception $e)
        {
            die(__LINE__ . ' ' . $e->getMessage());
        }
    }

}