<?php

namespace app\module;


use app\core\Tags;
use app\core\Config;

/**
 * Plain Controller
 */
class Plain extends Tags
{

    public static function html(string $content): string
    {
        return $content;
    }

    public static function javascript(string $content): string
    {
        return $content;
    }

    public static function robots(object $content): object
    {

        return $content;
    }

}

