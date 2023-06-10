<?php

namespace app\util;

use app\core\Config;
use app\core\Modify;
use app\util\Encryption;

// use app\module\Tilda;
// use app\module\Wix;

class Curl
{
    /**
     * Handles HTTP error codes and renders an appropriate response.
     *
     * @param  int       $http_code The HTTP status code to handle.
     * @return bool|null Returns true if HTTP status code is 200, false if code is or unhandled, null for all other codes.
     */
    public static function curlErrorHandler($http_code)
    {
        Config::$lang = (array) Config::$lang;

        switch ($http_code)
        {
            case 404:
                $result = Config::$runType === 'web' ? [
                    'code'         => 404,
                    'error'        => true,
                    'body'         => '<html><head><meta name="robots" content="noindex,nofollow"></head><body><h1>Ошибка: 404</h1>' . Config::$lang[1] . '</body></html>',
                    'content_type' => 'text/html',
                ] : [
                    'body' => 'Ошибка: 404 ' . Config::$lang[1],
                ];
                break;

            case 503:
                $result = Config::$runType === 'web' ? [
                    'code'         => 503,
                    'error'        => true,
                    'body'         => '<html><head><meta http-equiv="refresh" content="3"><meta name="robots" content="noindex,nofollow"></head><body><h1>Ошибка: 503</h1>' . Config::$lang[2] . '</body></html>',
                    'content_type' => 'text/html',
                ] : [
                    'body' => 'Ошибка: 503 ' . Config::$lang[2],
                ];
                break;

            case 502:
                $result = Config::$runType === 'web' ? [
                    'code'         => 502,
                    'error'        => true,
                    'body'         => '<html><head><meta http-equiv="refresh" content="3"><meta name="robots" content="noindex,nofollow"></head><body><h1>Ошибка: 502</h1>' . Config::$lang[0] . '</body></html>',
                    'content_type' => 'text/html',
                ] : [
                    'body' => 'Ошибка: 502 ' . Config::$lang[0],
                ];
                break;

            case 500:
                $result = Config::$runType === 'web' ? [
                    'code'         => 500,
                    'error'        => true,
                    'body'         => '<html><head><meta name="robots" content="noindex,nofollow"></head><body><h1>Ошибка: 500</h1>' . Config::$lang[3] . '</body></html>',
                    'content_type' => 'text/html',
                ] : [
                    'body' => 'Ошибка: 500 ' . Config::$lang[3],
                ];
                break;

            case 200:
                return true;

            case 0:
            default:
                return false;
        }

        Config::render((object) $result);
    }

    /**
     * [get description]
     * @param  [type] $url            [description]
     * @return [type] [description]
     */
    public static function get($url)
    {
        $curl = \curl_init();
        $ua   = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : Config::$config->headers->ua;

        if (Config::$config->privoxy->enabled)
        {
            curl_setopt($curl, CURLOPT_PROXY, Config::$config->privoxy->host . ':' . Config::$config->privoxy->port);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_USERAGENT, $ua);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_REFERER, (Config::$runType === 'web') ? Config::$domain->project : '');
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');

        $response = curl_exec($curl);

        $info         = curl_getinfo($curl);
        $content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        // get cache results
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (self::curlErrorHandler($http_code))
        {
            return (object) [
                'body'         => $response,
                'status'       => $http_code,
                'content_type' => $content_type,
            ];
        }
        else
        {
            return self::curlErrorHandler(502);
        }

    }

    /**
     * [preCachedRequest description]
     * @return [type] [description]
     */
    public static function preCachedRequest(): object
    {
        Cache::$microtime = \microtime(true);

        $results = (object) [];
        // var_dump(Config::$route);
        if (Config::$config->cache->enabled)
        {
            // curl_setopt($curl, CURLOPT_VERBOSE, true);
            $results = Cache::get(Config::$hash);
            if (count((array) $results) == 0)
            {
                $results = self::rget();

                if (empty($results))
                {
                    self::curlErrorHandler(500);
                }
                else
                {
                    Cache::set(
                        Modify::byContentType($results),
                        Config::$hash
                    );
                }
            }
        }
        else
        {
            $results = self::rget();

            if (empty($results))
            {
                self::curlErrorHandler(500);
            }
            else
            {
                return Modify::byContentType($results);
            }
        }

        return $results;
    }

    /**
     * Retrieve resource from server using GET request
     * @return object Response from server
     */
    public static function rget(): object
    {
        $build_query = count((array) Config::$route->query) > 0 ? '?' . http_build_query((array) Config::$route->query) : '';

        return isset(Config::$route->query) && in_array(key(Config::$route->query), Config::URI_QUERY_TYPES)
        ? self::get(Encryption::decode(Config::$route->query->{key(Config::$route->query)}))
        : self::get(Config::$domain->project . Config::$route->path . $build_query);
    }
}
