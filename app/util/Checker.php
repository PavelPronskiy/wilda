<?php

namespace app\util;

// namespace Utils;
use app\core\Config;
use app\util\Curl;

class Checker
{
    public function __construct()
    {
        self::run();
    }

    public static function run(): void
    {
        foreach (Config::$config->hosts as $host)
        {
            $get_site = Curl::get($host->site);

            if (!empty($get_site->body))
            {
                echo 'Site work: ' . $host->site . PHP_EOL;
            }
            else
            {
                echo 'Site not work: ' . $host->site . PHP_EOL;
            }
        }
    }
}
