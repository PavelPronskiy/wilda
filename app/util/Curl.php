<?php

namespace app\util;

use app\core\Config;
use app\util\Encryption;
use app\core\Tags;
use app\module\Tilda;
use app\module\Wix;

class Curl
{

	/**
	 * [typesModificator description]
	 * @param  [type] $obj [description]
	 * @return [type]      [description]
	 */
	public static function typesModificator($obj) : object
	{
		switch($obj->content_type)
		{
			case 'application/javascript; charset=utf-8': $obj->body = Tags::jsModify($obj->body); break;
			case 'text/html; charset=UTF-8': $obj->body = Tags::htmlModify($obj->body); break;
		}

		return $obj;
	}

	public static function typesCacheStats($obj) : object
	{
		switch($obj->content_type)
		{
			case 'text/html; charset=UTF-8': $obj->body = Cache::injectWebStats($obj->body); break;
		}

		return $obj;
	}

	/**
	 * [preCachedRequest description]
	 * @return [type] [description]
	 */
	public static function preCachedRequest() : object
	{
		Cache::$microtime = \microtime(true);		

		$results = [];
		// $cache = new Cache;
		if (Config::$config->cache->enabled)
		{
			// get cache results
			$results = Cache::get(Config::$hash);
			if (count( (array) $results) == 0)
			{
				$results = self::rget();

				if (empty($results))
					self::curlErrorHandler(500);
				else
					Cache::set(self::typesModificator($results), Config::$hash);
			}

			return self::typesCacheStats($results);
		}
		else
		{
			$results = self::rget();
			if (empty($results))
				self::curlErrorHandler(500);
			else
				return self::typesModificator($results);
		}

	}

	/**
	 * [rget description]
	 * @return [type] [description]
	 */
	public static function rget() : object
	{
		$build_query = count((array) Config::$route->query) > 0
			? '?' . http_build_query((array) Config::$route->query)
			: '';

		return isset(Config::$route->query) && in_array(key(Config::$route->query), Config::URI_QUERY_TYPES)
			? self::get(Encryption::decode(Config::$route->query->{key(Config::$route->query)}))
			: self::get(Config::$domain->project . Config::$route->path . $build_query);
	}

	/**
	 * [curlErrorHandler description]
	 * @param  [type] $http_code [description]
	 * @return [type]            [description]
	 */
	private static function curlErrorHandler($http_code)
	{
		if (RUN_METHOD == 'web')
		{
			switch($http_code)
			{
				case 404:
					return Config::render( (object) [
						'code' => 404,
						'error' => true,
						'body' => '<html><head><meta name="robots" content="noindex,nofollow"></head><body><h1>Ошибка: 404</h1>' . Config::$lang[1] . '</body></html>',
						'content_type' => 'text/html'
					]);

				case 503:
					return Config::render( (object) [
						'code' => 503,
						'error' => true,
						'body' => '<html><head><meta name="robots" content="noindex,nofollow"></head><body><h1>Ошибка: 503</h1>' . Config::$lang[2] . '</body></html>',
						'content_type' => 'text/html'
					]);
				case 502:
					return Config::render( (object) [
						'code' => 502,
						'error' => true,
						'body' => '<html><head><meta name="robots" content="noindex,nofollow"></head><body><h1>Ошибка: 502</h1>' . Config::$lang[0] . '</body></html>',
						'content_type' => 'text/html'
					]);
				case 500:
					return Config::render( (object) [
						'code' => 500,
						'error' => true,
						'body' => '<html><head><meta name="robots" content="noindex,nofollow"></head><body><h1>Ошибка: 503</h1>' . Config::$lang[3] . '</body></html>',
						'content_type' => 'text/html'
					]);
				
				case 200:
					return true;
				
				case 0:
				default:
					return false;
			}
		}
	}

	/**
	 * [get description]
	 * @param  [type] $url [description]
	 * @return [type]      [description]
	 */
	public static function get($url)
	{
		$curl = \curl_init();
		$ua = isset($_SERVER['HTTP_USER_AGENT'])
			? $_SERVER['HTTP_USER_AGENT']
			: Config::$config->headers->ua;

		if (Config::$config->privoxy->enabled)
		{
			curl_setopt($curl, CURLOPT_PROXY,
				Config::$config->privoxy->host . ':' .
				Config::$config->privoxy->port
			);
		}
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_USERAGENT, $ua);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt(
			$curl,
			CURLOPT_REFERER,
			(RUN_METHOD === 'web')
				? Config::$domain->project
				: ''
		);
		// curl_setopt($curl, CURLOPT_VERBOSE, true);
		curl_setopt($curl, CURLOPT_ENCODING, "gzip");

		$response = curl_exec($curl);
		$info = curl_getinfo($curl);
		$content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
		// $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		if (self::curlErrorHandler($http_code))
			return (object) [
				'body' => $response,
				'status' => $http_code,
				'content_type' => $content_type
			];
		else
			return self::curlErrorHandler(502);

	}


}