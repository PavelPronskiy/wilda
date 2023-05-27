<?php

namespace app\module;

use app\core\Tags;

/**
 * Plain Controller
 */
class Plain extends Tags
{
    /**
     * @param string $content
     * @return mixed
     */
    public static function css(string $content): string
    {
        return $content;
    }

    /**
     * @param string $content
     * @return mixed
     */
    public static function html(string $content): string
    {
        return $content;
    }

    /**
     * @param string $content
     * @return mixed
     */
    public static function javascript(string $content): string
    {
        return $content;
    }

    /**
     * @param object $content
     * @return mixed
     */
    public static function robots(object $content): object
    {
        return $content;
    }
}
