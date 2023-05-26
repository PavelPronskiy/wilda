<?php

namespace app\core;

use app\core\Config;

/**
 * This class describes a modify.
 */
abstract class Modify
{
    /**
     * @var string
     */
    public static $class_module_name = '\\app\\module\\';

    public static string $module;

    public static array $types = [
        'css'        => 'text/css',
        'javascript' => 'application/javascript',
        'html'       => 'text/html',
    ];

    /**
     * [typesModificator description]
     * @param  [type] $obj            [description]
     * @return [type] [description]
     */
    public static function byContentType(object $obj): object
    {
        static::$module = static::$class_module_name . Config::$domain->type;

        foreach (static::$types as $type => $mime)
        {
            if (str_contains($obj->content_type, $mime))
            {
                $obj->body = self::module($obj->body, $type);
            }
        }

        return $obj;
    }

    /**
     * @param  string  $content
     * @param  string  $type
     * @return mixed
     */
    public static function module(
        string $content,
        string $type
    ): string
    {
        if (method_exists(self::$module, $type))
        {
            return self::$module::{$type}($content);
        }
        else
        {
            return $content;
        }
    }

    /**
     * @param string $content
     */
    public static function robots(
        object $content
    )
    {
        $content->body = self::module($content->body, 'robots');

        return $content;
    }
}
