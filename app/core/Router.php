<?php

namespace app\core;

use app\util\Cache;
// use app\util\Curl;
use app\util\Editor;
// use app\util\Chromium;

class Router
{
    public function __construct()
    {
        if (isset(Config::$route->query))
        {
            if (key(Config::$route->query))
            {
                switch (key(Config::$route->query))
                {
                    // case 'chromium-settings':
                        // Chromium::getSettings();
                        // break;
                    case 'clear':
                    case 'flush':
                        Cache::clear();
                        break;
                    case 'clean-cache':
                        Cache::cleanCacheXhr();
                        break;
                    case 'keys':
                        Cache::keys();
                        break;
                    case 'editor':
                        new Editor();
                        break;
                }
            }
        }

        // for post submits only
        if (isset(Config::$route->post))
        {
            new Submit();
        }

        // for gets
        Config::render(
            self::routeGet(
                Cache::preCachedRequest()
            )
        );
    }

    public static function seoTypes(
        array $type = []
    ): array
    {
        $url_path_arr = self::getPath();

        if (isset($url_path_arr[0]) && in_array($url_path_arr[0], Config::URI_QUERY_TYPES))
        {
            $type = [
                'type' => $url_path_arr[0],
                'hash' => $url_path_arr[1],
            ];
        }

        return $type;
    }

    /**
     * [getRelativePath description]
     * @param  [type] $path           [description]
     * @param  [type] $type           [description]
     * @return [type] [description]
     */
    public static function getRelativePath(
        string $path,
        string $type
    ): string
    {
        if (Config::$config->{$type} === 'relative')
        {
            $path = Cache::getEncyptMapPath($path);
        }

        return $path;
    }

    /**
     * [parseURL description]
     * @param  [type] $src            [description]
     * @return [type] [description]
     */
    public static function parseURL($src): string
    {
        $url = parse_url($src);

        return isset($url['host']) ? $src : Config::$domain->project . $src;
    }


    /**
     * Sets the route url.
     *
     * @param      string  $src    The source
     * @param      string  $type   The type
     *
     * @return     string  ( description_of_the_return_value )
     */
    public static function setRouteUrl(
        string $src,
        string $type
    ): string
    {

        if (Config::$seo->enabled)
        {
            $seo_param_type = Config::SEO_PARAM_TYPES[$type];
        }
        else
        {
            $seo_param_type = Config::QUERY_PARAM_TYPES[$type];
        }

        return $seo_param_type . self::getRelativePath(self::parseURL($src), $type);
    }


    /**
     * Gets the path.
     *
     * @return     <type>  The path.
     */
    public static function getPath()
    {
        return array_values(array_filter(explode('/', Config::$route->path)));
    }

    /**
     * [routeGet description]
     * @param  [type] $content        [description]
     * @return [type] [description]
     */
    private static function routeGet($content)
    {
        switch (Config::$route->path)
        {
            case '/robots.txt':
                return Modify::robots($content);

            case '/sitemap.xml':
                return Modify::sitemap($content);

            default:
                return $content;
        }
    }
}
