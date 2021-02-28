<?php

namespace Curl;

class Controller
{
	public static $uri_params = ['ico', 'img', 'js', 'css', 'font'];
	public static $curl;

	public static function get($url)
	{
		$curl = \curl_init();

		if (\Config\Controller::$config->privoxy->enabled)
		{
			curl_setopt($curl, CURLOPT_PROXY,
				\Config\Controller::$config->privoxy->host . ':' .
				\Config\Controller::$config->privoxy->port
			);
		}
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_USERAGENT, \Config\Controller::$config->headers->ua);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt(
			$curl,
			CURLOPT_REFERER,
			(RUN_METHOD === 'web')
				? \Config\Controller::$domain->project
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
		
		if (self::curlErrorHandler($http_code)) {
			return (object) [
				'body' => $response,
				'content_type' => $content_type
			];
		} else {
			return \Config\Controller::render( (object) [
				'body' => 'Error: ' . $http_code,
				'content_type' => 'text/html'
			]);
		}
	}

	public static function rget() : object
	{
		$cacheController = new \Cache\Controller;
		$results = [];
		if (\Config\Controller::$config->cache->enabled)
		{
			// get cache results
			$results = $cacheController->get(\Config\Controller::$hash);
			if (count( (array) $results) == 0)
			{
				if (isset(\Config\Controller::$route->query) && in_array(key(\Config\Controller::$route->query), self::$uri_params))
				{
					$results = self::get(
						\Encrypt\Controller::decode(
							\Config\Controller::$route
								->query
								->{key(\Config\Controller::$route->query)})
					);
				}
				else
				{
					// get remote results and cache
					$results = self::get(
						\Config\Controller::$domain->project .
						\Config\Controller::$route->path
					);

				}

				if (empty($results))
				{
					die('no results');
				}
				else
				{
					if ($results->content_type == 'text/html; charset=UTF-8') {
						$results->body = \Tags\Controller::stripHTML($results->body);
					}

					$cacheController->set($results, \Config\Controller::$hash);
				}
			}
		}
		else
		{
			
			if (isset(\Config\Controller::$route->query) && in_array(key(\Config\Controller::$route->query), self::$uri_params))
			{
				$results = self::get(
					\Encrypt\Controller::decode(\Config\Controller::$route
						->query->{key(\Config\Controller::$route->query)})
				);

			}
			else
			{
				// get remote results and cache
				$results = self::get(
					\Config\Controller::$domain->project .
					\Config\Controller::$route->path
				);
			}

			if ($results->content_type == 'text/html; charset=UTF-8') {
				$results->body = \Tags\Controller::stripHTML($results->body);
			}
		}

		return $results;
	}

	private static function curlErrorHandler($http_code) : bool
	{
		switch($http_code)
		{
			case 404:
				if (RUN_METHOD == 'web')
					header("HTTP/1.0 404 Not Found");

				return false;
			case 503:
				if (RUN_METHOD == 'web')
					header("HTTP/1.0 503 Error");
	
				return false;
			break;
			
			case 200:
				return true;
			break;
			

			case 0:
			default:
				return false;
			break;
		}
	}

}