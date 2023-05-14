<?php

namespace app\core;

use app\core\Config;
use app\util\Encryption;
use app\util\Cache;
use zz\Html\HTMLMinify;

/**
 * Tags
 */
abstract class Modify
{

    public static $class_module_name = "\\app\\module\\";

    /**
     * [typesModificator description]
     * @param  [type] $obj [description]
     * @return [type]      [description]
     */
    public static function byContentType($obj): object
    {
        switch ($obj->content_type)
        {
            case 'application/javascript; charset=utf-8':
                $obj->body = self::javascript($obj->body);
                break;

            case 'text/html; charset=UTF-8':
                $obj->body = self::html($obj->body);
                break;
        }

        return $obj;
    }

    /**
     * [injectHTML description]
     * @param  [type] $html [description]
     * @return [type]       [description]
     */
    private static function injectHTML($html)
    {
        $path_header = Config::$inject->path . '/' . Config::getSiteName() . '-header.html';
        $path_footer = Config::$inject->path . '/' . Config::getSiteName() . '-footer.html';

        if (Config::$inject->enabled) {
            if (Config::$inject->header)
                if (file_exists($path_header))
                    $html = str_replace('</head>', file_get_contents($path_header) . '</head>', $html);

            if (Config::$inject->footer)
                if (file_exists($path_footer))
                    $html = str_replace('</body>', file_get_contents($path_footer) . '</body>', $html);

        }

        return $html;
    }


    /**
     * [compressHTML description]
     * @param  [type] $html [description]
     * @return [type]       [description]
     */
    private static function compressHTML($html): string
    {
        if (Config::$compress)
            $html = preg_replace([ 
                '/\>[^\S ]+/s',
                '/[^\S ]+\</s',
                '/(\s)+/s',
                '/<!--(.|\s)*?-->/',
                '/\n+/'
            ], [ 
                    '>',
                    '<',
                    '\\1',
                    '',
                    ' '
                ], $html);

        return $html;
    }

    public static function robots(object $content)
    {
        $module = self::$class_module_name . Config::$domain->type;
        
        if (method_exists($module, __FUNCTION__))
            return $module::robots($content);
        else
            return $content;
    }

    public static function javascript($content): string
    {
        $module = self::$class_module_name . Config::$domain->type;

        if (method_exists($module, __FUNCTION__))
            return $module::javascript($content);
        else
            return $content;
    }

    /**
     * [htmlModify description]
     * @param  [type] $html [description]
     * @return [type]       [description]
     */
    public static function html(string $html): string
    {
        $html      = self::injectHTML($html);
        $html      = Cache::injectWebCleaner($html);
        $html      = Cache::injectWebStats($html);
        $module    = self::$class_module_name . Config::$domain->type;

        if (method_exists($module, __FUNCTION__))
            $module::html($html);

        Tags::initialize($html);
        Tags::changeDomElements();

        return self::compressHTML(
            Tags::render()
        );
    }
}