<?php

namespace app\util;

// namespace Utils;
use app\core\Config;

class Booster
{
    public function __construct()
    {
        self::run();
    }

    /**
     * { function_description }
     */
    public static function robotsParser($host): void
    {
        foreach ($host->site as $site)
        {
            $robotsUrl = $site . '/robots.txt';
            // $robotsData = Curl::get($robotsUrl);
            // var_dump($robotsData);
            var_dump($robotsUrl);
            exit;
        }
    }

    public static function run(): void
    {
        foreach (Config::$config->hosts as $host)
        {
            self::robotsParser($host);
        }
    }
}
