<?php

namespace app\core;

use app\core\Config;
// use app\util\Encryption;
// use app\util\Cache;
// use zz\Html\HTMLMinify;

/**
 * Tags
 */
abstract class Modify
{

    public static string $module;
    public static array $types = [ 
        'css' => 'text/css',
        'javascript' => 'application/javascript',
        'html' => 'text/html'
    ];

    public static $class_module_name = "\\app\\module\\";

    /**
     * [typesModificator description]
     * @param  [type] $obj [description]
     * @return [type]      [description]
     */
    public static function byContentType($obj): object
    {
        static::$module = static::$class_module_name . Config::$domain->type;

        foreach (static::$types as $type => $mime)
            if (str_contains($obj->content_type, $mime))
                $obj->body = self::module($obj->body, $type);

        return $obj;
    }


    public static function module(string $content, string $type) : string
    {
        if (method_exists(self::$module, $type))
            return self::$module::{$type}($content);
        else
            return $content;
        
    }

}