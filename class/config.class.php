<?php

namespace Config;

class Controller
{
	public static $domain;
	public static $config;
	public static $route;
	public static $hash;
	public static $crypt;
	public static $hash_key;
	public static $name = 'tilda';
	public static $lang = [];
	// public static $req_site;

	function __construct()
	{
		\Config\Controller::getConfig();
	}

	public static function getConfig() : void
	{
		$array = [];
		$config_json = [];
		$config_user_json = [];

		if (RUN_METHOD == 'web')
		{
			$request_uri = parse_url(
				preg_replace('{^//}', '/', $_SERVER['REQUEST_URI'])
			);

			self::$route = (object) [
				'domain' => $_SERVER['HTTP_HOST'],
				'path' => $request_uri['path'],
				'site' => isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
					? $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://' . $_SERVER['HTTP_HOST']
					: $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'],
				'url' => isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
					? $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
					: $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
			];

			if (isset($request_uri['query']))
			{
				parse_str($request_uri['query'], $query);
				self::$route->query = (object) $query;
			}
			else
			{
				self::$route->query = (object) [];	
			}
		}

		if (file_exists(CONFIG_GLOBAL))
		{
			$config_json = json_decode(file_get_contents(CONFIG_GLOBAL));
			if (json_last_error() > 0)
				die(json_last_error_msg() . ' ' . CONFIG_GLOBAL);
		
		}
		else
			die('Global config: ' . CONFIG_GLOBAL . ' not found');

		if (file_exists(CONFIG_USER))
		{
			$config_user_json = json_decode(file_get_contents(CONFIG_USER));
			if (json_last_error() > 0)
				die(json_last_error_msg() . ' ' . CONFIG_USER);
		}
		else
			die('User config: ' . CONFIG_USER . ' not found');

		$array = (object) array_merge((array)$config_json, (array)$config_user_json);
		
		self::$config = $array;

		if (RUN_METHOD == 'web') {
			$device_type = self::isMobile() ? 'mobile' : 'desktop';
			self::$domain = self::getDomainConfig($array);

			if (!isset(self::$domain->type))
				die('Error domain type');


			if (isset(self::$domain->styles))
				self::$config->styles = self::$domain->styles;

			if (isset(self::$domain->scripts))
				self::$config->scripts = self::$domain->scripts;

			if (isset(self::$domain->images))
				self::$config->images = self::$domain->images;

			if (isset(self::$domain->privoxy))
			{
				if (isset(self::$domain->privoxy->enabled))
					self::$config->privoxy->enabled = self::$domain->privoxy->enabled;
				
				if (isset(self::$domain->privoxy->host))
					self::$config->privoxy->host = self::$domain->privoxy->host;

				if (isset(self::$domain->privoxy->port))
					self::$config->privoxy->port = self::$domain->privoxy->port;
			}

			if (isset(self::$domain->cache))
			{
				if (isset(self::$domain->cache->enabled))
					self::$config->cache->enabled = self::$domain->cache->enabled;

				if (isset(self::$domain->cache->expire))
					self::$config->cache->expire = self::$domain->cache->expire;
			}

			self::$lang = isset(self::$domain->lang) ? self::$config->translations->{self::$domain->lang} : self::$config->translations->{self::$config->lang};


			self::$hash_key = self::$name .
				':' . self::$route->domain .
				':' . self::$domain->type;

			self::$hash = self::$hash_key .
				':' . $device_type .
				':' . self::$route->url;
		}
	}
	
	public static function render($response) : void
	{
		if (RUN_METHOD == 'web')
		{
			if (isset($response->error) && isset($response->code))
				http_response_code($response->code);

			header("Content-type: " . $response->content_type);
			die($response->body);
		}
		else
		{
			echo $response->body;
		}
	}

	public static function isMobile() : bool
	{
		if(isset($_SERVER['HTTP_USER_AGENT']) and !empty($_SERVER['HTTP_USER_AGENT']))
		{
			$bool = false;
			if(preg_match('/(Mobile|Android|Tablet|GoBrowser|[0-9]x[0-9]*|uZardWeb\/|Mini|Doris\/|Skyfire\/|iPhone|Fennec\/|Maemo|Iris\/|CLDC\-|Mobi\/)/uis', $_SERVER['HTTP_USER_AGENT']))
			{
				$bool = true;
			}
		}

		return $bool;
	}

	public static function getDomainConfig($array)
	{
		foreach ($array->hosts as $host)
		{
			$parse_host_site = (object) parse_url($host->site);
			if (self::$route->domain === $parse_host_site->host)
				return $host;
		}
	}
}
