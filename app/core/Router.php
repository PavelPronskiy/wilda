<?php

namespace app\core;

use app\util\Cache;
// use app\util\Curl;
use app\util\Editor;
use app\util\ErrorHandler;
// use app\util\Chromium;

class Router
{
    public function __construct()
    {

        if (empty(Config::$hash))
        {
            throw new ErrorHandler('', 4004);
        }

        if (isset(Config::$config->canonical))
        {
            Config::setCanonicalDomainRedirect();
        }


        if (isset(Config::$route->query))
        {
            if (key(Config::$route->query))
            {
                switch (key(Config::$route->query))
                {
                    // case 'chromium-settings':
                        // Chromium::getSettings();
                        // break;
                    case 'cleaner':
                    case 'clear':
                    case 'flush':
                        Cache::clear();
                        break;
                    case 'clean-cache':
                        Cache::cleanCacheXhr();
                        break;
                    // case 'keys':
                        // Cache::keys();
                        // break;
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

        if (
            isset($url_path_arr[0]) &&
            isset($url_path_arr[1]) &&
            in_array($url_path_arr[0], Config::URI_QUERY_TYPES)
        )
        {
            $type = [
                'type' => $url_path_arr[0],
                'hash' => pathinfo($url_path_arr[1], PATHINFO_FILENAME),
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
        string $url,
        string $type,
        string $ext = ''
    ): string
    {
        if (Config::$seo->enabled)
        {
            $seo_param_type = Config::SEO_PARAM_TYPES[$type];

            if (Config::$seo->ext)
            {
                $ext = self::parse_ext($url);
            }
        }
        else
        {
            $seo_param_type = Config::QUERY_PARAM_TYPES[$type];
        }

        return $seo_param_type . self::getRelativePath(self::parseURL($url), $type) . $ext;
    }


    /**
     * { function_description }
     *
     * @param      <type>  $url    The url
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public static function parse_ext(string $url) : string
    {
        $parse = parse_url($url);
        $ext = pathinfo($parse['path'], PATHINFO_EXTENSION);

        return !empty($ext) ? '.' . $ext : '';
    }


    /**
     * Gets the path.
     *
     * @return     <type>  The path.
     */
    public static function getPath() : array
    {
        return array_values(array_filter(explode('/', Config::$route->path)));
    }

    /**
     * [routeGet description]
     * @param  [type] $content        [description]
     * @return [type] [description]
     */
    private static function routeGet(object $content): object
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
