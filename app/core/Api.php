<?php

namespace app\core;

use app\util\Cache;
use app\util\Chromium;
use app\util\ErrorHandler;

class Api
{
    public static $path;
    public function __construct()
    {

        self::$path = self::getPath();

        if (!isset(self::$path[0]))
        {
            throw new ErrorHandler('Page not found', 404);
        }

        switch (self::$path[0]) {
            case 'get-autocache-hosts':
                Chromium::getAutoCacheHosts();
                break;

            default:
                // code...
                break;
        }
    }

    public static function getPath()
    {
        return array_values(array_filter(explode('/', Config::$route->path)));
    }
}
