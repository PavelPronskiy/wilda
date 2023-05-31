<?php

/* Класс Cache предоставляет методы для кэширования веб-страниц и очистки кэша. */
namespace app\util;

use app\core\Config;
use app\util\Encryption;
use phpssdb\Core\SimpleSSDB;

/**
 * This class describes a cache.
 */
class Cache
{
    /**
     * @var string
     */
    public $hash = '';

    /**
     * @var string
     */
    public static $instance = '';

    /**
     * @var string
     */
    public static $microtime = '';

    /**
     * @var string
     */
    public static $revision_key = 'rev';

    public function __construct()
    {
        static::$revision_key = Config::$name . ':' . static::$revision_key;

        $this->dbInstance();
    }

    /**
     * Clears the object.
     */
    public static function clear(): void
    {
        $pages = static::storageKeys(Config::$hash_key . ':*');

        if (count($pages) > 0)
        {
            foreach ($pages as $key)
            {
                self::$instance->del($key);
            }
        }

        Config::render((object) [
            'body'         => '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="2; url=\'' . Config::$domain->site . '\'" /></head><body><h4>Перенаправление на главную...</h4><p>Очищено элементов: ' . count($pages) . '</p></body></html>',
            'content_type' => 'text/html; charset=UTF-8',
        ]);
    }

    /**
     * @return mixed
     */
    public function dbInstance()
    {
        switch (Config::$storage->type)
        {
            case 'memory':
                return $this->redisInstance();

            case 'disk':
                return $this->ssdbInstance();

            default:
                break;
        }
    }

    /**
     * { function_description }
     *
     * @param string $revision The revision
     */
    public static function delConfigRevision(string $revision): void
    {
        $keys = static::storageKeys(static::$revision_key . ':' . $revision);

        if (count($keys) > 0)
        {
            foreach ($keys as $key)
            {
                self::$instance->del($key);
            }
        }
    }

    /**
     * Gets the specified hash.
     *
     * @param  string $hash The hash
     * @return object ( description_of_the_return_value )
     */
    public static function get(string $hash): object
    {
        $res = self::$instance->get($hash);
        if ($res)
        {
            $obj       = json_decode($res);
            $obj->body = base64_decode($obj->body);
        }
        else
        {
            $obj = (object) [];
        }

        return $obj;
    }

    /**
     * Эта функция извлекает все версии конфигурации, хранящиеся в Redis, и возвращает их в виде массива.
     *
     * шаблону `self::. ':*'`.
     * @return array Массив, содержащий все значения, хранящиеся в ключах Redis, которые соответствуют
     */
    public static function getAllConfigRevisions(): array
    {
        $array = [];
        $keys  = static::storageKeys(static::$revision_key . ':*');

        if (count($keys) > 0)
        {
            foreach ($keys as $key)
            {
                $array[$key] = self::$instance->get($key);
            }
        }

        return $array;
    }

    /**
     * Функция возвращает массив всех ключей для ревизий конфигурации, отсортированных по возрастанию.
     *
     * отсортированный по возрастанию. Массив заключен в другой массив с ключом `'revisions'`.
     * @return array Массив, содержащий список ключей ревизий с префиксом `self::`,
     */
    public static function getAllKeysConfigRevisions(): array
    {
        $maxSize = 10000;
        $array   = [];
        $revs    = [
            'revisions' => [],
        ];

        $keys = static::storageKeys(static::$revision_key . ':*');

        if ($keys === false)
        {
            return $revs;
        }

        foreach ($keys as $key)
        {
            if (str_contains($key, static::$revision_key))
            {
                $array[] = str_replace(static::$revision_key . ':', '', $key);
            }
        }

        usort($array, function (
            int $a,
            int $b
        )
        {
            return (int) $a - (int) $b;
        });

        $revs['revisions'] = $array;

        return $revs;
    }

    /**
     * Эта функция PHP возвращает массив версий конфигурации на основе заданного ключа версии.
     *
     * номер редакции конфигурации. Он используется для извлечения данных конфигурации, связанных с этой
     * конкретной версией, из кэша с использованием хранилища ключей и значений Redis.
     * полученной из кэша Redis с использованием указанного ключа версии.
     * @param  string revision                 Параметр «revision» — это строковая переменная, представляющая версию или
     * @return array  Возвращается массив. Массив является результатом декодирования строки JSON,
     */
    public static function getConfigRevision(string $revision): array
    {
        return (array) json_decode(self::$instance->get(static::$revision_key . ':' . $revision));
    }

    /**
     * Функция вставляет элемент div со ссылкой для очистки содержимого веб-страницы, если установлен
     * определенный файл cookie.
     *
     * верхний левый угол страницы.
     * аргумента, либо исходный HTML-код с внедренным элементом div в верхнем левом углу страницы, если
     * выполняется определенное условие.
     * @param  string html          строка, содержащая код HTML, который необходимо изменить, внедрив элемент div в
     * @return string строка, которая представляет собой либо исходный HTML-код, переданный в качестве
     */
    public static function injectWebCleaner(string $html): string
    {
        if (isset($_COOKIE['wilda']))
        {
            $exp = explode(':', $_COOKIE['wilda']);

            if (isset($exp[1]) && Encryption::decode($exp[1]) === Config::$config->salt)
            {
                $inject_html = '<div style="position:fixed;z-index:99999;left:0;top:0;padding:3px 6px;background-color:rgba(0,0,0,0.4"><a style="text-decoration:none;color:#fff;font-size:16pt;font-weight:normal" href="/?clear">&#10227;</a></div>';

                return str_replace('</body>', $inject_html . '</body>', (string) $html);
            }
        }

        return (string) $html;
    }

    /**
     * Эта функция PHP вводит веб-статистику в HTML-код, если включено кэширование.
     *
     * аргумента, либо исходный HTML-код с дополнительным комментарием, введенным перед закрывающим тегом
     * `</body>`. Комментарий содержит время работы кеша, которое вычисляется с помощью метода
     * `microtimeAgo` из класса `Config`. Решение вводить комментарий или нет основано на значении `stats
     * @param  html   Код        HTML, который необходимо изменить путем внедрения веб-статистики.
     * @return string строка, которая представляет собой либо исходный HTML-код, переданный в качестве
     */
    public static function injectWebStats(string $html): string
    {
        if (Config::$config->cache->stats)
        {
            $inject_html = '<!-- Cache runtime: ' . Config::microtimeAgo(self::$microtime) . ' -->';

            return str_replace('</body>', $inject_html . '</body>', (string) $html);
        }

        return (string) $html;
    }

    /**
     * Функция извлекает все ключи из кэша Redis и отображает количество кэшированных страниц или сообщение
     * о том, что кэш пуст.
     */
    public static function keys(): void
    {
        $pages = static::storageKeys(Config::$hash_key . ':*');

        if (count($pages) > 0)
        {
            self::notice('pages cached: ' . count($pages));
        }
        else
        {
            self::notice('cache is empty');
        }

    }

    /**
     * Функция выводит сообщение вместе с хостом HTTP и завершает выполнение скрипта.
     *
     * отображаться при вызове функции.
     * @param message Параметр сообщения — это строка, представляющая уведомление, которое будет
     */
    public static function notice($message): void
    {
        $host_str = 'Host: ' . $_SERVER['HTTP_HOST'];
        die('<pre>' . $host_str . ', ' . $message . '</pre>');
    }

    /**
     * Эта функция устанавливает значение кэша с заданным хешем и сроком действия на основе свойств
     * входного объекта.
     *
     * кэшированного объекта. Он используется для последующего извлечения кэшированного объекта.
     * @param object obj  Объект, который необходимо кэшировать. Он содержит тело и тип содержимого.
     * @param string hash Параметр hash — это строка, которая служит уникальным идентификатором
     */
    public static function set(
        object $obj,
        string $hash
    ): void
    {
        self::$instance->set(
            (string) $hash,
            (string) json_encode([
                'body'         => base64_encode($obj->body),
                'content_type' => $obj->content_type,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        self::$instance->expire(
            (string) $hash,
            (int) Config::$config->cache->expire * 60
        );

        $obj = (object) [];
    }

    /**
     * Эта функция устанавливает версию конфигурации, кодируя данные в формате JSON и сохраняя их с
     * уникальным хэш-ключом.
     *
     * Данные кодируются в виде строки JSON с использованием функции json_encode с параметрами
     * JSON_UNESCAPED_SLASHES и JSON_UNESCAPED_UNICODE, чтобы убедиться, что
     *  и текущей временной метки, а затем ее использованием в качестве ключа для сохранения
     * массива  в кеше с использованием метода set экземпляра . ` объект.
     * @param  array  data          — это массив данных, которые нужно хранить в кеше с уникальным ключом.
     * @return string строка, представляющая собой хэш, сгенерированный объединением свойства
     */
    public static function setConfigRevision(array $data): string
    {
        $hash = static::$revision_key . ':' . \time ();
        self::$instance->set(
            (string) $hash,
            (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return $hash;
    }

    /**
     * @param string $key
     */
    public static function storageKeys(string $key)
    {
        return self::$instance->keys(
            static::storageType($key)
        );
    }

    /**
     * @param  $key
     * @return mixed
     */
    public static function storageType(string $key)
    {
        switch (Config::$storage->type)
        {
            case 'disk':
                return [$key, '', 1000];

            case 'memory':
                return $key;
        }
    }

    /**
     * Функция очищает веб-кеш, устанавливая новое пустое значение со сроком действия и отображая
     * HTML-страницу с перенаправлением на домашнюю страницу.
     *
     * страницу через 2 секунды. Страница также содержит хеш-значение и сообщение о том, что пользователь
     * перенаправляется на домашнюю страницу.
     * @return HTML-страница с метатегом обновления, который перенаправляет пользователя на домашнюю
     */
    public static function webCacheCleaner()
    {
        $hash = Config::$name . ':' . Encryption::encode(Config::$config->salt);

        self::$instance->set(
            (string) $hash,
            ''
        );

        self::$instance->expire(
            (string) $hash,
            (int) Config::$config->cache->expire * 60
        );

        Config::render((object) [
            'body'         => '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="2; url=\'/?clear\'" /><script>function setCookie(name,value,days) { var expires = ""; if (days) { var date = new Date(); date.setTime(date.getTime() + (days*24*60*60*1000)); expires = "; expires=" + date.toUTCString(); } document.cookie = name + "=" + (value || "")  + expires + "; path=/"; }; setCookie("' . Config::$name . '", "' . $hash . '",' . Config::$config->cache->expire . ');</script></head><body><h4>Перенаправление на главную...</h4><p>Hash: ' . $hash . '</p></body></html>',
            'content_type' => 'text/html; charset=UTF-8',
        ]);
    }

    /**
     * { function_description }
     */
    private function redisInstance(): void
    {
        self::$instance = new \Redis ();
        self::$instance->connect(Config::$storage->redis->host, Config::$storage->redis->port);
    }

    /**
     * { function_description }
     */
    private function ssdbInstance(): void
    {
        try
        {
            self::$instance = new SimpleSSDB(Config::$storage->ssdb->host, Config::$storage->ssdb->port);
            self::$instance->easy();
        }
        catch (Exception $e)
        {
            die(__LINE__ . ' ' . $e->getMessage());
        }
    }
}
