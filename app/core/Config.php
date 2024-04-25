<?php

namespace app\core;

/**
 * Configuration variables and functions
 */
class Config
{
    const CONFIG_ACCESS = PATH . '/app/config/access.json';

    const CONFIG_GLOBAL = PATH . '/app/config/global.json';

    const CUSTOM_GLOBAL = PATH . '/.global.json';

    const CONFIG_HOSTS = PATH . '/app/config/hosts.json';

    const QUERY_PARAM_CSS = '/?css=';

    const QUERY_PARAM_FONT = '/?font=';

    const QUERY_PARAM_ICO = '/?ico=';

    const QUERY_PARAM_IMG = '/?img=';

    const QUERY_PARAM_JS = '/?js=';

    const REPORTS_CONFIG = PATH . '/app/config/reports.json';

    const URI_QUERY_ADMIN = ['cleaner', 'flush', 'keys'];

    const URI_QUERY_TYPES = ['ico', 'img', 'js', 'css', 'font'];

    /**
     * @var array
     */
    public static $access = [];

    /**
     * @var mixed
     */
    public static $auth;

    /**
     * @var array
     */
    public static $compress = [];

    /**
     * config
     *
     * @var [type]
     */
    public static $config;

    /**
     * @var mixed
     */
    public static $crypt;

    /**
     * domain
     *
     * @var [type]
     */
    public static $domain;

    /**
     * @var array
     */
    public static $editor = [];

    /**
     * @var array
     */
    public static $favicon = [];

    /**
     * @var mixed
     */
    public static $hash;

    /**
     * @var mixed
     */
    public static $hash_key;

    /**
     * @var array
     */
    public static $inject = [];

    /**
     * @var array
     */
    public static $lang = [];

    /**
     * @var array
     */
    public static $mail = [];

    /**
     * @var array
     */
    public static $metrics = [];

    /**
     * @var string
     */
    public static $name = 'wilda';

    /**
     * @var mixed
     */
    public static $reports;

    /**
     * @var mixed
     */
    public static $request_uri;

    /**
     * @var mixed
     */
    public static $route;

    /**
     * @var string
     */
    public static $runType = 'web';

    /**
     * @var mixed
     */
    public static $storage = [];

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        static::initialize();
    }

    /**
     * @param  $url
     * @return mixed
     */
    public static function forceProto($url): string
    {
        if (static::$config->forceSSL)
        {
            return str_replace('http://', 'https://', $url);
        }
        else
        {
            return $url;
        }
    }

    /**
     * @return mixed
     */
    public static function getAccessConfig(): array
    {
        $config_json = [];
        if (file_exists(self::CONFIG_ACCESS))
        {
            $config_json = json_decode(file_get_contents(self::CONFIG_ACCESS));
            if (json_last_error() > 0)
            {
                die(json_last_error_msg() . ' ' . self::CONFIG_ACCESS);
            }
        }
        else
        {
            $config_json = [
                (object) [
                    'login'    => 'admin',
                    'password' => 'admin',
                ],
            ];

            static::setAccessConfig($config_json);
        }

        return $config_json;
    }

    /**
     * [getDomainConfig description]
     * @return [type] [description]
     */
    public static function getDomainConfig(): object
    {

        foreach (static::$config->hosts as $host)
        {
            if (is_array($host->site))
            {
                foreach ($host->site as $site)
                {
                    $parse_host_site = (object) parse_url($site);
                    if (static::$route->domain === $parse_host_site->host)
                    {
                        $host->site = $site;

                        return $host;
                    }
                }
            }
            else
            {
                $parse_host_site = (object) parse_url($host->site);
                if (static::$route->domain === $parse_host_site->host)
                {
                    return $host;
                }
            }
        }

        return (object) [];
    }

    /**
     * Эта функция извлекает глобальные параметры конфигурации из файла JSON и добавляет имя текущего
     * объекта в конфигурацию перед ее возвратом.
     *
     * @return глобальные параметры конфигурации в виде объекта JSON с добавленным свойством «имя».
     */
    public static function getGlobalConfig(): object
    {
        $config_json = [];
        if (file_exists(static::CONFIG_GLOBAL))
        {
            $config_json = json_decode(file_get_contents(static::CONFIG_GLOBAL));
            if (json_last_error() > 0)
            {
                die(json_last_error_msg() . ' ' . static::CONFIG_GLOBAL);
            }
        }
        else
        {
            die('Global config: ' . static::CONFIG_GLOBAL . ' not found');
        }

        $config_json->name = static::$name;

        return $config_json;
    }

    public static function getCustomGlobalConfig(): object
    {
        $config_json = [];
        if (file_exists(static::CUSTOM_GLOBAL))
        {
            $config_json = json_decode(file_get_contents(static::CUSTOM_GLOBAL));
            if (json_last_error() > 0)
            {
                die(json_last_error_msg() . ' ' . static::CUSTOM_GLOBAL);
            }
        }
        else
        {
            die('Custom global config: ' . static::CUSTOM_GLOBAL . ' not found');
        }

        return $config_json;
    }

    /**
     * @return mixed
     */
    public static function getHostsConfig(): object
    {
        $config_json = (object) [];

        if (file_exists(static::CONFIG_HOSTS))
        {
            $config_json->hosts = json_decode(file_get_contents(static::CONFIG_HOSTS));
            if (json_last_error() > 0)
            {
                die(json_last_error_msg() . ' ' . static::CONFIG_HOSTS);
            }
        }
        else
        {
            $config_json = [
                (object) [
                    'hosts' => [],
                ],
            ];

            static::setHostsConfig($config_json);
        }

        return $config_json;
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
     * Undocumented function
     *
     * @return void
     */
    public static function getProjectName(): string
    {
        return str_replace(['http://', 'https://'], '', static::$domain->project);
    }

    /**
     * Gets the reports configuration.
     *
     * @return array|object The reports configuration.
     */
    public static function getReportsConfig(): object
    {
        $config_json = [];
        if (file_exists(static::REPORTS_CONFIG))
        {
            $config_json = json_decode(file_get_contents(static::REPORTS_CONFIG));
            if (json_last_error() > 0)
            {
                die(json_last_error_msg() . ' ' . static::REPORTS_CONFIG);
            }
        }
        else
        {
            die('Reports config: ' . static::REPORTS_CONFIG . ' not found');
        }

        return $config_json;
    }

    /**
     * [getSiteName description]
     * @return [type] [description]
     */
    public static function getSiteName()
    {
        return str_replace(['http://', 'https://'], '', static::$domain->site);
    }

    /**
     * [getURIEncryptHash description]
     * @return [type] [description]
     */
    public static function getURIEncryptHash(): string
    {
        foreach (self::URI_QUERY_TYPES as $type)
        {
            if (isset(static::$route->query->{$type}))
            {
                return $type . ':' . static::$route->query->{$type} . '';
            }
        }

        return 'html' . ':' . static::$route->url;
    }

    public static function initialize(): void
    {
        static::$lang     = (array) [];
        static::$access   = (array) [];
        static::$reports  = (object) [];
        static::$mail     = (object) [];
        static::$mail->smtp     = (object) [];
        static::$auth     = (object) [];
        static::$metrics  = (object) [];
        static::$favicon  = (object) [];
        static::$inject   = (object) [];
        static::$compress = (object) [];
        static::$editor   = (object) [];
        static::$storage  = (object) [];
        static::$config   = (object) [
            ...(array) static::getGlobalConfig(),
            ...(array) static::getCustomGlobalConfig(),
            ...(array) static::getHostsConfig(),
        ];

        if (self::$runType === 'cli')
        {
            static::$lang = static::$config->translations->cli->{static::$config->lang};
        }

        if (self::$runType === 'web')
        {
            $request_uri = parse_url(
                preg_replace('{^//}', '/', $_SERVER['REQUEST_URI'])
            );

            static::$route = (object) [
                'domain' => $_SERVER['HTTP_HOST'],
                'path'   => $request_uri['path'],
                'site'   => isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                ? $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://' . $_SERVER['HTTP_HOST']
                : $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'],
                'url'    => isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                ? $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
                : $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            ];

            if (isset($request_uri['query']))
            {
                parse_str($request_uri['query'], $query);
                static::$route->query = (object) $query;
            }
            else
            {
                static::$route->query = (object) [];
            }

            if (isset($_POST) && count($_POST) > 0)
            {
                static::$route->post = (object) $_POST;
            }

            if (isset($_SERVER['HTTP_REFERER']))
            {
                static::$route->referer = $_SERVER['HTTP_REFERER'];
            }

            // $device_type   = static::isMobile() ? 'mobile' : 'desktop';
            static::$domain = (object) static::getDomainConfig();
            static::$access = (array) static::getAccessConfig();

            /**
             * set lang translations
             * @var [type]
             */
            static::$lang = isset(static::$domain->lang)
            ? (array) static::$config->translations->web->{static::$domain->lang}
            : (array) static::$config->translations->web->{static::$config->lang};

            /**
             * if not type defined
             */
            if (!isset(static::$domain->type))
            {
                \app\util\Curl::curlErrorHandler(500);
            }

            static::$hash_key = static::$name . ':' . static::$route->domain . ':' . static::$domain->type;

            // hash cache key
            static::$hash = static::$hash_key . ':' . static::getKeyUserDisplayResolution() . ':' . static::getURIEncryptHash();

            if (isset(static::$domain->styles))
            {
                static::$config->styles = static::$domain->styles;
            }

            if (isset(static::$domain->scripts))
            {
                static::$config->scripts = static::$domain->scripts;
            }

            if (isset(static::$domain->images))
            {
                static::$config->images = static::$domain->images;
            }

            if (
                is_array(
                    static::$domain->site
                ) && in_array(
                    static::$route->site,
                    static::$domain->site
                )
            )
            {
                static::$config->site = static::$route->site;
                static::$domain->site = static::$route->site;
            }

            /**
             * set privoxy variables
             */
            if (isset(static::$domain->privoxy))
            {
                if (isset(static::$domain->privoxy->enabled))
                {
                    static::$config->privoxy->enabled = static::$domain->privoxy->enabled;
                }

                if (isset(static::$domain->privoxy->host))
                {
                    static::$config->privoxy->host = static::$domain->privoxy->host;
                }

                if (isset(static::$domain->privoxy->port))
                {
                    static::$config->privoxy->port = static::$domain->privoxy->port;
                }
            }

            /**
             * set cache variables
             */
            if (isset(static::$domain->cache->enabled))
            {
                static::$config->cache->enabled = static::$domain->cache->enabled;
            }

            if (isset(static::$domain->cache->expire))
            {
                static::$config->cache->expire = static::$domain->cache->expire;
            }

            if (isset(static::$domain->cache->stats))
            {
                static::$config->cache->stats = static::$domain->cache->stats;
            }

            if (isset(static::$domain->cache->browser))
            {
                static::$config->cache->browser = static::$domain->cache->browser;
            }

            /**
             * set mail submit variables
             */
            if (isset(static::$domain->mail->send_type))
            {
                static::$mail->send_type = static::$domain->mail->send_type;
            }
            else
            {
                static::$mail->send_type = static::$config->mail->send_type;
            }

            if (isset(static::$domain->mail->smtp->auth))
            {
                static::$mail->smtp->auth = static::$domain->mail->smtp->auth;
            }
            else
            {
                static::$mail->smtp->auth = static::$config->mail->smtp->auth;
            }

            if (isset(static::$domain->mail->smtp->host))
            {
                static::$mail->smtp->host = static::$domain->mail->smtp->host;
            }
            else
            {
                static::$mail->smtp->host = static::$config->mail->smtp->host;
            }

            if (isset(static::$domain->mail->smtp->port))
            {
                static::$mail->smtp->port = static::$domain->mail->smtp->port;
            }
            else
            {
                static::$mail->smtp->port = static::$config->mail->smtp->port;
            }

            if (isset(static::$domain->mail->smtp->username))
            {
                static::$mail->smtp->username = static::$domain->mail->smtp->username;
            }
            else
            {
                static::$mail->smtp->username = static::$config->mail->smtp->username;
            }

            if (isset(static::$domain->mail->smtp->password))
            {
                static::$mail->smtp->password = static::$domain->mail->smtp->password;
            }
            else
            {
                static::$mail->smtp->password = static::$config->mail->smtp->password;
            }

            if (isset(static::$domain->mail->debug))
            {
                static::$mail->debug = static::$domain->mail->debug;
            }
            else
            {
                static::$mail->debug = static::$config->mail->debug;
            }

            if (isset(static::$domain->mail->enabled))
            {
                static::$mail->enabled = static::$domain->mail->enabled;
            }
            else
            {
                static::$mail->enabled = static::$config->mail->enabled;
            }

            if (isset(static::$domain->mail->subject))
            {
                static::$mail->subject = static::$domain->mail->subject;
            }
            else
            {
                static::$mail->subject = static::$config->mail->subject;
            }

            if (isset(static::$domain->mail->name))
            {
                static::$mail->name = static::$domain->mail->name;
            }
            else
            {
                static::$mail->name = static::$config->mail->name;
            }

            if (isset(static::$domain->mail->from))
            {
                static::$mail->from = static::$domain->mail->from;
            }
            else
            {
                static::$mail->from = static::$config->mail->from . self::getSiteName();
            }

            if (isset(static::$domain->mail->to))
            {
                static::$mail->to = static::$domain->mail->to;
            }
            else
            {
                static::$mail->to = static::$config->mail->to;
            }

            if (isset(static::$domain->mail->success))
            {
                static::$mail->success = static::$domain->mail->success;
            }
            else
            {
                static::$mail->success = static::$config->mail->success;
            }

            if (isset(static::$domain->mail->error))
            {
                static::$mail->error = static::$domain->mail->error;
            }
            else
            {
                static::$mail->error = static::$config->mail->error;
            }

            /**
             * set favicon variables
             */
            if (isset(static::$domain->favicon->enabled))
            {
                static::$favicon->enabled = static::$domain->favicon->enabled;
            }
            else
            {
                static::$favicon->enabled = static::$config->favicon->enabled;
            }

            static::$favicon->path = 'app/favicon';

            /**
             * set compress variables
             */
            if (isset(static::$domain->compress->enabled))
            {
                static::$compress->enabled = static::$domain->compress->enabled;
            }
            else
            {
                static::$compress->enabled = static::$config->compress->enabled;
            }

            /**
             * set storage type [redis: memory, ssdb: disk]
             */
            if (isset(static::$domain->storage) && isset(static::$domain->storage->type))
            {
                static::$storage->type = static::$domain->storage->type;
            }
            else
            {
                static::$storage->type = static::$config->storage->type;
            }

            if (isset(static::$domain->storage) && isset(static::$domain->storage->redis))
            {
                static::$storage->redis = static::$domain->storage->redis;
            }
            else
            {
                static::$storage->redis = static::$config->storage->redis;
            }

            if (isset(static::$domain->storage) && isset(static::$domain->storage->ssdb))
            {
                static::$storage->ssdb = static::$domain->storage->ssdb;
            }
            else
            {
                static::$storage->ssdb = static::$config->storage->ssdb;
            }

            /**
             * set metrics variables
             */
            if (isset(static::$domain->metrics->enabled))
            {
                static::$metrics->enabled = static::$domain->metrics->enabled;
            }
            else
            {
                static::$metrics->enabled = static::$config->metrics->enabled;
            }

            static::$metrics->path = APP_PATH . 'tpl/metrics';

            if (isset(static::$domain->metrics->ga))
            {
                static::$metrics->ga = static::$domain->metrics->ga;
            }

            if (isset(static::$domain->metrics->ya))
            {
                static::$metrics->ya = static::$domain->metrics->ya;
            }

            /**
             * set inject variables
             */
            if (isset(static::$domain->inject->enabled))
            {
                static::$inject->enabled = static::$domain->inject->enabled;
            }
            else
            {
                static::$inject->enabled = static::$config->inject->enabled;
            }

            if (isset(static::$domain->inject->header))
            {
                static::$inject->header = static::$domain->inject->header;
            }
            else
            {
                static::$inject->header = static::$config->inject->header;
            }

            if (isset(static::$domain->inject->path))
            {
                static::$inject->path = APP_PATH . 'inject';
            }
            else
            {
                static::$inject->path = APP_PATH . 'inject';
            }

            if (isset(static::$domain->inject->footer))
            {
                static::$inject->footer = static::$domain->inject->footer;
            }
            else
            {
                static::$inject->footer = static::$config->inject->footer;
            }

            static::$editor->path  = 'tpl/editor';
            static::$auth->path    = 'tpl/auth';
            static::$reports->path = 'tpl/reports';

        }

    }

    /**
     * [isMobile description]
     * @return boolean [description]
     */
    public static function isMobile($bool = false): bool
    {
        if (isset($_SERVER['HTTP_USER_AGENT']) and !empty($_SERVER['HTTP_USER_AGENT']))
        {
            if (preg_match('/(Mobile|Android|Tablet|GoBrowser|[0-9]x[0-9]*|uZardWeb\/|Mini|Doris\/|Skyfire\/|iPhone|Fennec\/|Maemo|Iris\/|CLDC\-|Mobi\/)/uis', $_SERVER['HTTP_USER_AGENT']))
            {
                $bool = true;
            }
        }

        return $bool;
    }

    /**
     * Undocumented function
     *
     * @param  float    $microtime
     * @return string
     */
    public static function microtimeAgo(float $microtime): string
    {
        return round((microtime(true) - $microtime) * 1000, 2) . 's';
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public static function removeProtoUrl($url): string
    {
        return str_replace(['http://', 'https://'], '', $url);
    }

    /**
     * [render description]
     * @param  [type] $response       [description]
     * @return [type] [description]
     */
    public static function render(object $response): void
    {
        $ref_array = ['flush', 'clear'];
        $no_cache  = false;

        if (self::$runType === 'web')
        {
            if (isset($response->error) && isset($response->code))
            {
                http_response_code($response->code);
            }

            if (static::$config->cache->browser)
            {
                if (isset(static::$route->referer))
                {
                    $ref = parse_url(static::$route->referer);
                    if (isset($ref['query']) && in_array($ref['query'], $ref_array))
                    {
                        $no_cache = true;
                    }
                }

                if (!$no_cache)
                {
                    $etag = md5($response->body);
                    header('Cache-Control: max-age=' . static::$config->cache->expire);
                    header('ETag: ' . $etag);

                    if (isset($_SERVER['HTTP_IF_NONE_MATCH']))
                    {
                        if ($_SERVER['HTTP_IF_NONE_MATCH'] == $etag)
                        {
                            header('HTTP/1.1 304 Not Modified', true, 304);
                            exit();
                        }
                    }
                }
                else
                {
                    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                    header('Cache-Control: post-check=0, pre-check=0', false);
                    header('Pragma: no-cache');
                }
            }

            header('Content-type: ' . $response->content_type);
            die($response->body);
        }
        else
        {
            echo $response->body . PHP_EOL;
        }
    }

    /**
     * @param array $data
     */
    public static function setAccessConfig(array $data): void
    {
        file_put_contents(
            self::CONFIG_ACCESS,
            (string) json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )
        );
    }

    /**
     * Эта функция устанавливает данные конфигурации пользователя, записывая их в файл JSON, если файл
     * существует.
     *
     * необходимо записать в файл.
     * @param array data — это массив, содержащий данные конфигурации пользователя, которые
     */
    public static function setHostsConfig(array $data): void
    {
        file_put_contents(
            static::CONFIG_HOSTS,
            (string) json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )
        );
    }

    /**
     * Sets the reports configuration.
     *
     * @param <type> $config The configuration
     */
    public static function setReportsConfig($config): void
    {
        file_put_contents(
            static::REPORTS_CONFIG,
            (string) json_encode(
                $config,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )
        );
    }

    /**
     * Функция проверяет, является ли данная строка допустимой конфигурацией JSON, и возвращает логическое
     * значение.
     *
     * JSON. Функция `validateConfig` принимает этот параметр и проверяет, является ли он допустимым JSON,
     * используя функцию `json_decode`. Если декодирование не удается, возвращается false, в противном
     * случае возвращается true.
     * строкой JSON, и «false» в противном случае.
     * @param  string data                 Параметр `` представляет собой строку, представляющую данные конфигурации
     * @return bool   логическое значение. Он вернет «true», если входная строка «» является допустимой
     */
    public static function validateConfig(string $data): bool
    {
        json_decode($data);

        return json_last_error() > 0 ? false : true;
    }
}
