<?php

namespace app\util;

use app\core\Config;
use app\core\Modify;
use app\core\Router;
use app\util\Encryption;
use \Campo\UserAgent as UA;

// use app\module\Tilda;
// use app\module\Wix;

class Curl
{
    /**
     * @var array
     */
    // public static $devices = ['Mobile', 'Tablet', 'Desktop'];

    /**
     * Handles HTTP error codes and renders an appropriate response.
     *
     * @param  int       $http_code The HTTP status code to handle.
     * @return bool|null Returns true if HTTP status code is 200, false if code is or unhandled, null for all other codes.
     */
    // public static function curlErrorHandler($http_code, $code = '')
    // {
        // Config::$lang = (array) Config::$lang;

        // if (!empty($code) && isset(Config::$lang[$code]))
        // {
        //     $code_message = Config::$lang[$code];
        // }
        // else
        // {
        //     $code_message = '';
        // }

    //     $result = [
    //         'no_cache'     => true,
    //         'error'        => true,
    //         'content_type' => 'text/html',
    //     ];


    //     switch ($http_code)
    //     {
    //         case 404:
    //             $result['code'] = 404;
    //             $result['body'] = ErrorHandler::webTemplatePageMessage(404, Config::getLangTranslationMessage(1001));
    //             break;

    //         case 503:
    //             $result['code'] = 503;
    //             $result['body'] = ErrorHandler::webTemplateReloader(503, Config::getLangTranslationMessage(1002));
    //             break;

    //         case 502:
    //             $result['code'] = 502;
    //             $result['body'] = ErrorHandler::webTemplateReloader(502, Config::getLangTranslationMessage(1000));
    //             break;

    //         case 500:
    //             $result['code'] = 500;
    //             $result['body'] = ErrorHandler::webTemplateReloader(500, Config::getLangTranslationMessage(1000));
    //             break;

    //         case 200:
    //             return true;

    //         case 0:
    //         default:
    //             return false;
    //     }

    //     Config::render((object) $result);
    // }


    /**
     * Gets the headers.
     *
     * @param      string  $response_headers  The response headers
     * @param      array   $result_headers    The result headers
     *
     * @return     array   The headers.
     */
    public static function getHeaders(
        string $response_headers,
        array $result_headers = []
    ) : object
    {
        foreach (explode("\r\n", $response_headers) as $i => $line) {
            if (!empty($line))
            {
                @list ($key, $value) = explode(': ', $line);
                if (!empty($key) && !empty($value))
                {
                    $result_headers[$key] = $value;
                }
            }
        }

        if (isset($result_headers) > 0)
            return (object) $result_headers;
        else
            return (object) [];
    }


    /**
     * [get description]
     * @param  [type] $url            [description]
     * @return [type] [description]
     */
    public static function get(
        string|object $url,
        string $deviceType = ''
    )
    {
        $curl = \curl_init();

        $userAgent = isset($_SERVER['HTTP_USER_AGENT'])
        ? $_SERVER['HTTP_USER_AGENT']
        : UA::random(['device_type' =>
            !empty($deviceType) ? [$deviceType] : Config::DEVICE_DIMENSIONS,
        ]);

        if (Config::$config->privoxy->enabled)
        {
            curl_setopt($curl, CURLOPT_PROXY, Config::$config->privoxy->host . ':' . Config::$config->privoxy->port);
        }

        // curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, Config::$config->curl->timeout);
        curl_setopt($curl, CURLOPT_REFERER, Config::$domain->project);
        // curl_setopt($curl, CURLOPT_REFERER, (Config::$runType === 'web') ? Config::$domain->project : '');
        curl_setopt($curl, CURLOPT_ENCODING, Config::$config->curl->encoding);
        curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, []);
        curl_setopt($curl, CURLOPT_HEADER, true);

        $response = curl_exec($curl);
        
        // extract header
        $content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $header = substr($response, 0, $header_size);
        $headers = self::getHeaders($header);
        $body = substr($response, $header_size);

        curl_close($curl);

        if ($http_code === 200)
        {
            // get results
            return (object) [
                'body'         => $body,
                'status'       => $http_code,
                'content_type' => $content_type,
                'headers'      => $headers,
            ];
        }
        else
        {
            throw new ErrorHandler('', $http_code);
            // return self::curlErrorHandler(502);
        }
    }


    /**
     * { function_description }
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public static function rget()
    {
        if (isset(Config::$route->query) && in_array(key(Config::$route->query), Config::URI_QUERY_TYPES))
        {
            $url = Cache::getMapFilePath(Config::$route->query->{key(Config::$route->query)});

            if (!$url)
            {
                throw new ErrorHandler('', 404);
                // return self::curlErrorHandler(404);
            }

            return self::get($url);
        }

        $seoType = Router::seoTypes();
        if (Config::$seo->enabled && isset($seoType['hash']))
        {
            $url = Cache::getMapFilePath($seoType['hash']);

            if (!$url)
            {
                throw new ErrorHandler('', 404);
                // return self::curlErrorHandler(404);
            }

            return self::get($url);
        }


        $build_query = count((array) Config::$route->query) > 0 ? '?' . http_build_query((array) Config::$route->query) : '';
        return self::get(Config::$domain->project . Config::$route->path . $build_query);
    }


    /**
     * Retrieve resource from server using GET request
     * @return object Response from server
     */
/*    public static function rget()
    {
        $build_query = count((array) Config::$route->query) > 0 ? '?' . http_build_query((array) Config::$route->query) : '';
        if (isset(Config::$route->query) && in_array(key(Config::$route->query), Config::URI_QUERY_TYPES))
        {
            $url = Cache::getMapFilePath(
                Config::$route->query->{key(Config::$route->query)}
            );

            if (!$url)
            {
                return self::curlErrorHandler(404);
            }

            return self::get($url);
        }
        else
        {
            return self::get(Config::$domain->project . Config::$route->path . $build_query);
        }

        // $url = Config::$domain->project . Config::$route->path . $build_query;
        // var_dump($url);
        // return self::get($url);
    }*/
}
