<?php

namespace Config;

class Controller
{
	public static $domain;
	public static $config;
	public static $route;
	public static $hash;
	public static $crypt;
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

		$request_uri = parse_url($_SERVER['REQUEST_URI']);

		self::$route = (object) [
			'domain' => $_SERVER['HTTP_HOST'],
			'path' => $_SERVER['REQUEST_URI'],
			'site' => isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
				? $_SERVER['HTTP_X_FORWARDED_PROTO']
				: $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'],
			'url' => isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
				? $_SERVER['HTTP_X_FORWARDED_PROTO']
				: $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
		];

		if (isset($request_uri['query'])) {
			parse_str($request_uri['query'], $query);
			self::$route->query = (object) $query;
		}

		if (file_exists(CONFIG)) {
			$config_json = json_decode(file_get_contents(CONFIG));
			if (json_last_error() > 0) {
				die(json_last_error_msg() . ' ' . CONFIG);
			}
		} else {
			die('Default config: ' . CONFIG . ' not found');
		}

		if (file_exists(CONFIG_USER)) {
			$config_user_json = json_decode(file_get_contents(CONFIG_USER));
			if (json_last_error() > 0) {
				die(json_last_error_msg() . ' ' . CONFIG_USER);
			}
		}

		$array = (object)array_merge((array)$config_json, (array)$config_user_json);

		self::$config = $array;
		self::$domain = self::getDomainConfig($array);
		self::$hash = isset($query)
			? $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . ':' . key($query) . ':' . $query[key($query)]
			: $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		//var_dump(self::$config);
		// return $array;
	}
	
	public static function render($response) : void
	{
		header("Content-type: " . $response->content_type);
		die($response->body);
	}

	public static function getDomainConfig($array)
	{
		//var_dump($_SERVER);
		foreach ($array->hosts as $host) {
			if (self::$route->site == $host->site) {
				return $host;
			}
		}
	}
}
