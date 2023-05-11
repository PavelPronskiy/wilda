<?php

namespace app\core;

/**
 * Configuration variables and functions
 */
class Config
{
	public static $domain;
	public static $config;
	public static $route;
	public static $hash;
	public static $crypt;
	public static $hash_key;
	public static $compress;
	public static $request_uri;
	public static $name 			= 'wilda';
	public static $lang 			= [];
	public static $mail 			= [];
	public static $metrics 			= [];
	public static $favicon 			= [];
	public static $inject 			= [];

	const CONFIG_GLOBAL 			= PATH . '/global.json';
	const CONFIG_USER 				= PATH . '/.config.json';

	const QUERY_PARAM_IMG 			= '/?img=';
	const QUERY_PARAM_ICO 			= '/?ico=';
	const QUERY_PARAM_JS 			= '/?js=';
	const QUERY_PARAM_CSS 			= '/?css=';
	const QUERY_PARAM_FONT 			= '/?font=';

	const URI_QUERY_TYPES 			= ['ico', 'img', 'js', 'css', 'font'];
	const URI_QUERY_ADMIN 			= ['cleaner', 'flush', 'keys'];

	function __construct()
	{
		self::initialize();
	}

	/**
	 * [initialize description]
	 * @return [type] [description]
	 */
	public static function initialize() : void
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

			if (isset($_POST) && count($_POST) > 0)
				self::$route->post = (object) $_POST;

		}

		if (file_exists(self::CONFIG_GLOBAL))
		{
			$config_json = json_decode(file_get_contents(self::CONFIG_GLOBAL));
			if (json_last_error() > 0)
				die(json_last_error_msg() . ' ' . self::CONFIG_GLOBAL);
		
		}
		else
			die('Global config: ' . self::CONFIG_GLOBAL . ' not found');

		if (file_exists(self::CONFIG_USER))
		{
			$config_user_json = json_decode(file_get_contents(self::CONFIG_USER));
			if (json_last_error() > 0)
				die(json_last_error_msg() . ' ' . self::CONFIG_USER);
		}
		else
			die('User config: ' . self::CONFIG_USER . ' not found');

		$array = (object) array_merge((array)$config_json, (array)$config_user_json);
		
		self::$config = $array;

		if (RUN_METHOD == 'web')
		{
			$device_type = self::isMobile() ? 'mobile' : 'desktop';
			self::$domain = self::getDomainConfig($array);
			self::$mail = (object) [];
			self::$favicon = (object) [];
			self::$inject = (object) [];
			self::$metrics = (object) [];

			if (!isset(self::$domain->type))
				die('Error domain type');

			if (isset(self::$domain->styles))
				self::$config->styles = self::$domain->styles;

			if (isset(self::$domain->scripts))
				self::$config->scripts = self::$domain->scripts;

			if (isset(self::$domain->images))
				self::$config->images = self::$domain->images;

			/**
			 * set privoxy variables
			 */
			if (isset(self::$domain->privoxy))
			{
				if (isset(self::$domain->privoxy->enabled))
					self::$config->privoxy->enabled = self::$domain->privoxy->enabled;
				
				if (isset(self::$domain->privoxy->host))
					self::$config->privoxy->host = self::$domain->privoxy->host;

				if (isset(self::$domain->privoxy->port))
					self::$config->privoxy->port = self::$domain->privoxy->port;
			}

			/**
			 * set cache variables
			 */
			if (isset(self::$domain->cache))
			{
				if (isset(self::$domain->cache->enabled))
					self::$config->cache->enabled = self::$domain->cache->enabled;

				if (isset(self::$domain->cache->expire))
					self::$config->cache->expire = self::$domain->cache->expire;
			}

			/**
			 * set mail submit variables
			 */
			if (isset(self::$domain->mail->enabled))
				self::$mail->enabled = self::$domain->mail->enabled;
			else
				self::$mail->enabled = self::$config->mail->enabled;

			if (isset(self::$domain->mail->subject))
				self::$mail->subject = self::$domain->mail->subject;
			else
				self::$mail->subject = self::$config->mail->subject;

			if (isset(self::$domain->mail->name))
				self::$mail->name = self::$domain->mail->name;
			else
				self::$mail->name = self::$config->mail->name;

			if (isset(self::$domain->mail->from))
				self::$mail->from = self::$domain->mail->from;
			else
				self::$mail->from = self::$config->mail->from;

			if (isset(self::$domain->mail->to))
				self::$mail->to = self::$domain->mail->to;
			else
				self::$mail->to = self::$config->mail->to;

			if (isset(self::$domain->mail->success))
				self::$mail->success = self::$domain->mail->success;
			else
				self::$mail->success = self::$config->mail->success;
		
			if (isset(self::$domain->mail->error))
				self::$mail->error = self::$domain->mail->error;
			else
				self::$mail->error = self::$config->mail->error;
		

			/**
			 * set favicon variables
			 */
			if (isset(self::$domain->favicon->enabled))
				self::$favicon->enabled = self::$domain->favicon->enabled;
			else
				self::$favicon->enabled = self::$config->favicon->enabled;

			if (isset(self::$domain->favicon->path))
				self::$favicon->path = self::$domain->favicon->path;
			else
				self::$favicon->path = self::$config->favicon->path;

			if (isset(self::$domain->favicon->default))
				self::$favicon->default = self::$domain->favicon->default;
			else
				self::$favicon->default = self::$config->favicon->default;

			/**
			 * set compress variables
			 */
			if (isset(self::$domain->compress))
				self::$compress = self::$domain->compress;
			else
				self::$compress = self::$config->compress;


			/**
			 * set metrics variables
			 */
			if (isset(self::$domain->metrics->enabled))
				self::$metrics->enabled = self::$domain->metrics->enabled;
			else
				self::$metrics->enabled = self::$config->metrics->enabled;

			if (isset(self::$domain->metrics->path))
				self::$metrics->path = APP_PATH . self::$domain->metrics->path;
			else
				self::$metrics->path = APP_PATH . self::$config->metrics->path;

			if (isset(self::$domain->metrics->ga))
				self::$metrics->ga = self::$domain->metrics->ga;

			if (isset(self::$domain->metrics->ya))
				self::$metrics->ya = self::$domain->metrics->ya;

			/**
			 * set inject variables
			 */
			if (isset(self::$domain->inject->enabled))
				self::$inject->enabled = self::$domain->inject->enabled;
			else
				self::$inject->enabled = self::$config->inject->enabled;

			if (isset(self::$domain->inject->header))
				self::$inject->header = self::$domain->inject->header;
			else
				self::$inject->header = self::$config->inject->header;

			if (isset(self::$domain->inject->path))
				self::$inject->path = APP_PATH . self::$domain->inject->path;
			else
				self::$inject->path = APP_PATH . self::$config->inject->path;

			if (isset(self::$domain->inject->footer))
				self::$inject->footer = self::$domain->inject->footer;
			else
				self::$inject->footer = self::$config->inject->footer;


			/**
			 * set lang translations
			 * @var [type]
			 */
			self::$lang = isset(self::$domain->lang)
				? self::$config->translations->{self::$domain->lang}
				: self::$config->translations->{self::$config->lang};

			self::$hash_key = self::$name . ':' . self::$route->domain . ':' . self::$domain->type;
			self::$hash = self::$hash_key . ':' . $device_type . ':' . self::getURIEncryptHash();
		}
		else
			self::$lang = self::$config->translations->ru;

	}

	/**
	 * [getURIEncryptHash description]
	 * @return [type] [description]
	 */
	public static function getURIEncryptHash() : string
	{
		foreach (Config::URI_QUERY_TYPES as $type)
		{
			if (isset(self::$route->query->{$type}))
				return $type . ':' . self::$route->query->{$type};
		}

		return 'html' . ':' . self::$route->url;
	}

	/**
	 * [render description]
	 * @param  [type] $response [description]
	 * @return [type]           [description]
	 */
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

	/**
	 * [isMobile description]
	 * @return boolean [description]
	 */
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

	/**
	 * [getSiteName description]
	 * @return [type] [description]
	 */
	public static function getSiteName()
	{
		return str_replace(['http://', 'https://'] , '', self::$domain->site);
	}
	
	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public static function getProjectName()
	{
		return str_replace(['http://', 'https://'] , '', self::$domain->project);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public static function removeProtoUrl($url)
	{
		return str_replace(['http://', 'https://'] , '', $url);
	}

	public static function forceProto($url)
	{
		if (self::$config->forceSSL)
			return str_replace('http://' , 'https://', $url);
		else
			return $url;
	}

	/**
	 * [getDomainConfig description]
	 * @param  [type] $array [description]
	 * @return [type]        [description]
	 */
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
