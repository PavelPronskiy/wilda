<?php

namespace app\core;

/**
 * Configuration variables and functions
 */
class Config
{
	/**
	 * domain
	 *
	 * @var [type]
	 */
	public static $domain;

	/**
	 * config
	 *
	 * @var [type]
	 */
	public static $config;

	public static $route;
	public static $hash;
	public static $crypt;
	public static $hash_key;
	public static $compress;
	public static $request_uri;
	public static $name = 'wilda';
	public static $editor = [];
	public static $lang = [];
	public static $mail = [];
	public static $metrics = [];
	public static $favicon = [];
	public static $inject =  [];
	public static $access = [];
	const CONFIG_ACCESS = PATH . '/app/config/access.json';
	const CONFIG_GLOBAL = PATH . '/app/config/global.json';
	const CONFIG_HOSTS = PATH . '/app/config/hosts.json';

	const QUERY_PARAM_IMG = '/?img=';
	const QUERY_PARAM_ICO = '/?ico=';
	const QUERY_PARAM_JS = '/?js=';
	const QUERY_PARAM_CSS = '/?css=';
	const QUERY_PARAM_FONT = '/?font=';

	const URI_QUERY_TYPES = [ 'ico', 'img', 'js', 'css', 'font' ];
	const URI_QUERY_ADMIN = [ 'cleaner', 'flush', 'keys' ];

	function __construct()
	{
		static::initialize();
	}

	
	public static function initialize(): void
	{
		static::$lang 		= (array) 	[];
		static::$access 	= (array) 	[];
		static::$mail 		= (object) 	[];
		static::$metrics 	= (object) 	[];
		static::$favicon 	= (object) 	[];
		static::$inject 	= (object) 	[];
		static::$editor 	= (object) 	[];

		$request_uri = parse_url(
			preg_replace('{^//}', '/', $_SERVER[ 'REQUEST_URI' ])
		);

		static::$route = (object) [ 
			'domain' => $_SERVER[ 'HTTP_HOST' ],
			'path' => $request_uri[ 'path' ],
			'site' => isset($_SERVER[ 'HTTP_X_FORWARDED_PROTO' ])
			? $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] . '://' . $_SERVER[ 'HTTP_HOST' ]
			: $_SERVER[ 'REQUEST_SCHEME' ] . '://' . $_SERVER[ 'HTTP_HOST' ],
			'url' => isset($_SERVER[ 'HTTP_X_FORWARDED_PROTO' ])
			? $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] . '://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ]
			: $_SERVER[ 'REQUEST_SCHEME' ] . '://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ]
		];

		if (isset($request_uri[ 'query' ]))
		{
			parse_str($request_uri[ 'query' ], $query);
			static::$route->query = (object) $query;
		}
		else
		{
			static::$route->query = (object) [];
		}

		if (isset($_POST) && count($_POST) > 0)
			static::$route->post = (object) $_POST;

		static::$config = (object) [
			... (array) static::getGlobalConfig(),
			... (array) static::getHostsConfig()
		];
		
		// $device_type   = static::isMobile() ? 'mobile' : 'desktop';
		static::$domain  = (object) static::getDomainConfig();
		static::$access  = (array) Access::getAccessConfig();

		/**
		 * set lang translations
		 * @var [type]
		 */
		static::$lang = isset(static::$domain->lang)
			? (object) static::$config->translations->{static::$domain->lang}
			: (object) static::$config->translations->{static::$config->lang};


		/**
		 * if not type defined
		 */
		if (!isset(static::$domain->type))
			\app\util\Curl::curlErrorHandler(500);

		if (isset(static::$domain->styles))
			static::$config->styles = static::$domain->styles;

		if (isset(static::$domain->scripts))
			static::$config->scripts = static::$domain->scripts;

		if (isset(static::$domain->images))
			static::$config->images = static::$domain->images;

		if (
			is_array(
				static::$domain->site
			) && in_array(
				static::$route->site,
				static::$domain->site
			)
		) {
			static::$config->site = static::$route->site;
			static::$domain->site = static::$route->site;
		}


		/**
		 * set privoxy variables
		 */
		if (isset(static::$domain->privoxy)) {
			if (isset(static::$domain->privoxy->enabled))
				static::$config->privoxy->enabled = static::$domain->privoxy->enabled;

			if (isset(static::$domain->privoxy->host))
				static::$config->privoxy->host = static::$domain->privoxy->host;

			if (isset(static::$domain->privoxy->port))
				static::$config->privoxy->port = static::$domain->privoxy->port;
		}

		/**
		 * set cache variables
		 */
		if (isset(static::$domain->cache->enabled))
			static::$config->cache->enabled = static::$domain->cache->enabled;

		if (isset(static::$domain->cache->expire))
			static::$config->cache->expire = static::$domain->cache->expire;

		if (isset(static::$domain->cache->stats))
			static::$config->cache->stats = static::$domain->cache->stats;

		/**
		 * set mail submit variables
		 */
		if (isset(static::$domain->mail->enabled))
			static::$mail->enabled = static::$domain->mail->enabled;
		else
			static::$mail->enabled = static::$config->mail->enabled;

		if (isset(static::$domain->mail->subject))
			static::$mail->subject = static::$domain->mail->subject;
		else
			static::$mail->subject = static::$config->mail->subject;

		if (isset(static::$domain->mail->name))
			static::$mail->name = static::$domain->mail->name;
		else
			static::$mail->name = static::$config->mail->name;

		if (isset(static::$domain->mail->from))
			static::$mail->from = static::$domain->mail->from;
		else
			static::$mail->from = static::$config->mail->from;

		if (isset(static::$domain->mail->to))
			static::$mail->to = static::$domain->mail->to;
		else
			static::$mail->to = static::$config->mail->to;

		if (isset(static::$domain->mail->success))
			static::$mail->success = static::$domain->mail->success;
		else
			static::$mail->success = static::$config->mail->success;

		if (isset(static::$domain->mail->error))
			static::$mail->error = static::$domain->mail->error;
		else
			static::$mail->error = static::$config->mail->error;


		/**
		 * set favicon variables
		 */
		if (isset(static::$domain->favicon->enabled))
			static::$favicon->enabled = static::$domain->favicon->enabled;
		else
			static::$favicon->enabled = static::$config->favicon->enabled;

		static::$favicon->path = "app/favicon";

		/**
		 * set compress variables
		 */
		if (isset(static::$domain->compress))
			static::$compress = static::$domain->compress;
		else
			static::$compress = static::$config->compress;

		/**
		 * set metrics variables
		 */
		if (isset(static::$domain->metrics->enabled))
			static::$metrics->enabled = static::$domain->metrics->enabled;
		else
			static::$metrics->enabled = static::$config->metrics->enabled;

		static::$metrics->path = APP_PATH . "tpl/metrics";

		if (isset(static::$domain->metrics->ga))
			static::$metrics->ga = static::$domain->metrics->ga;

		if (isset(static::$domain->metrics->ya))
			static::$metrics->ya = static::$domain->metrics->ya;

		/**
		 * set inject variables
		 */
		if (isset(static::$domain->inject->enabled))
			static::$inject->enabled = static::$domain->inject->enabled;
		else
			static::$inject->enabled = static::$config->inject->enabled;

		if (isset(static::$domain->inject->header))
			static::$inject->header = static::$domain->inject->header;
		else
			static::$inject->header = static::$config->inject->header;

		if (isset(static::$domain->inject->path))
			static::$inject->path = APP_PATH . 'inject';
		else
			static::$inject->path = APP_PATH . 'inject';

		if (isset(static::$domain->inject->footer))
			static::$inject->footer = static::$domain->inject->footer;
		else
			static::$inject->footer = static::$config->inject->footer;


		static::$editor->path = 'tpl/editor';

		// set hash keys by cache required
		static::$hash_key = static::$name . ':' .
			static::$route->domain . ':' .
			static::$domain->type;
			
		static::$hash = static::$hash_key . ':' .
			static::getKeyUserDisplayResolution() . ':' .
			static::getURIEncryptHash();

	}

	/**
	 * Эта функция извлекает глобальные параметры конфигурации из файла JSON и добавляет имя текущего
	 * объекта в конфигурацию перед ее возвратом.
	 * 
	 * @return глобальные параметры конфигурации в виде объекта JSON с добавленным свойством «имя».
	 */
	public static function getGlobalConfig()
	{
		$config_json = [];
		if (file_exists(static::CONFIG_GLOBAL)) {
			$config_json = json_decode(file_get_contents(static::CONFIG_GLOBAL));
			if (json_last_error() > 0)
				die(json_last_error_msg() . ' ' . static::CONFIG_GLOBAL);

		}
		else
			die('Global config: ' . static::CONFIG_GLOBAL . ' not found');


		$config_json->name = static::$name;

		return $config_json;
	}

	/**
	 * Функция проверяет, является ли данная строка допустимой конфигурацией JSON, и возвращает логическое
	 * значение.
	 * 
	 * @param string data Параметр `` представляет собой строку, представляющую данные конфигурации
	 * JSON. Функция `validateConfig` принимает этот параметр и проверяет, является ли он допустимым JSON,
	 * используя функцию `json_decode`. Если декодирование не удается, возвращается false, в противном
	 * случае возвращается true.
	 * 
	 * @return bool логическое значение. Он вернет «true», если входная строка «» является допустимой
	 * строкой JSON, и «false» в противном случае.
	 */
	public static function validateConfig(string $data): bool
	{
		json_decode($data);
		return json_last_error() > 0 ? false : true;
	}

	public static function getHostsConfig()
	{
		$config_json = [];
		if (file_exists(static::CONFIG_HOSTS)) {
			$config_json = json_decode(file_get_contents(static::CONFIG_HOSTS));
			if (json_last_error() > 0) {
				die(json_last_error_msg() . ' ' . static::CONFIG_HOSTS);
			}
		}
		else
			die('User config: ' . static::CONFIG_HOSTS . ' not found');

		return $config_json;
	}

	/**
	 * Эта функция устанавливает данные конфигурации пользователя, записывая их в файл JSON, если файл
	 * существует.
	 * 
	 * @param array data  — это массив, содержащий данные конфигурации пользователя, которые
	 * необходимо записать в файл.
	 */
	public static function setUserConfig(array $data): void
	{
		if (file_exists(static::CONFIG_HOSTS))
			file_put_contents(
				static::CONFIG_HOSTS,
				(string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
			);
	}

	/**
	 * [getURIEncryptHash description]
	 * @return [type] [description]
	 */
	public static function getURIEncryptHash(): string
	{
		foreach (Config::URI_QUERY_TYPES as $type)
			if (isset(static::$route->query->{$type}))
				return $type . ':' . static::$route->query->{$type};

		return 'html' . ':' . static::$route->url;
	}

	/**
	 * [render description]
	 * @param  [type] $response [description]
	 * @return [type]           [description]
	 */
	public static function render(object $response): void
	{
		if (RUN_METHOD == 'web')
		{

			if (isset($response->error) && isset($response->code))
				http_response_code($response->code);
				
			header("Content-type: " . $response->content_type);
			die($response->body);
		}
	}

	/**
	 * [isMobile description]
	 * @return boolean [description]
	 */
	public static function isMobile(): bool
	{
		if (isset($_SERVER[ 'HTTP_USER_AGENT' ]) and !empty($_SERVER[ 'HTTP_USER_AGENT' ])) {
			$bool = false;
			if (preg_match('/(Mobile|Android|Tablet|GoBrowser|[0-9]x[0-9]*|uZardWeb\/|Mini|Doris\/|Skyfire\/|iPhone|Fennec\/|Maemo|Iris\/|CLDC\-|Mobi\/)/uis', $_SERVER[ 'HTTP_USER_AGENT' ])) {
				$bool = true;
			}
		}

		return $bool;
	}

	/**
	 * [isMobile description]
	 * @return boolean [description]
	 */
	public static function getKeyUserDisplayResolution(): string
	{
		return static::isMobile() ? 'mobile' : 'desktop';
	}

	/**
	 * [getSiteName description]
	 * @return [type] [description]
	 */
	public static function getSiteName()
	{
		return str_replace([ 'http://', 'https://' ], '', static::$domain->site);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public static function getProjectName() : string
	{
		return str_replace([ 'http://', 'https://' ], '', static::$domain->project);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public static function removeProtoUrl($url) : string
	{
		return str_replace([ 'http://', 'https://' ], '', $url);
	}

	public static function forceProto($url) : string
	{
		if (static::$config->forceSSL)
			return str_replace('http://', 'https://', $url);
		else
			return $url;
	}

	/**
	 * Undocumented function
	 *
	 * @param float $microtime
	 * @return string
	 */
	public static function microtimeAgo(float $microtime): string
	{
		return round((microtime(true) - $microtime) * 1000, 2) . 's';
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $time
	 * @param boolean $reverse
	 * @return void
	 */
	public static function timeAgo($time, $reverse = false) : string
	{
		$s             = 0;
		$estimate_time = $reverse === false
			? (int) $time - time()
			: time() - (int) $time;
		// var_dump($estimate_time);

		if ($estimate_time < 1) {
			return $s;
		}

		$condition = [ 
			24 * 60 * 60 => 'd',
			60 * 60 => 'h',
			60 => 'm',
			1 => 's'
		];

		foreach ($condition as $secs => $str) {
			$d = $estimate_time / $secs;

			if ($d >= 1) {
				$r = round($d);
				return $r . $str;
			}
		}
	}


	/**
	 * [getDomainConfig description]
	 * @return [type]        [description]
	 */
	public static function getDomainConfig() : object
	{

		foreach (static::$config->hosts as $host) {
			if (is_array($host->site))
				foreach ($host->site as $site) {
					$parse_host_site = (object) parse_url($site);
					if (static::$route->domain === $parse_host_site->host) {
						$host->site = $site;
						return $host;
					}
				}
			else {
				$parse_host_site = (object) parse_url($host->site);
				if (static::$route->domain === $parse_host_site->host)
					return $host;
			}
		}

		return (object) [];
	}
}