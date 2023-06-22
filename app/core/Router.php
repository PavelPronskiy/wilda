<?php

namespace app\core;

use app\util\Cache;
use app\util\Curl;
use app\util\Editor;

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
                    case 'clear':
                    case 'flush':
                        Cache::clear();
                        break;
                    case 'keys':
                        Cache::keys();
                        break;
                    case 'editor':
                        new Editor();
                        break;
                    /*case 'reports':
                    new Reports();
                    break;*/
                    case 'cleaner':
                        Cache::webCacheCleaner();
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
                Curl::preCachedRequest()
            )
        );
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
