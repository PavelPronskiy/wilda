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
            // var_dump($robotsData);
            echo 'Get: ' . $robotsUrl . PHP_EOL;
            $robotsData = Curl::get($robotsUrl);
            var_dump($robotsData);
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
