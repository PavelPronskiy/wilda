<?php

/* Класс Cache предоставляет методы для кэширования веб-страниц и очистки кэша. */

namespace app\util;

use app\core\Config;
use app\core\Modify;
// use app\util\Encryption;

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
     * @var mixed
     */
    public static $instance;

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
        static::$revision_key = Config::$config->name . ':' . static::$revision_key;

        // self::dbInstance();
    }

    /**
     * Clears the object.
     */
    public static function clear(): void
    {
        $countAllCachedFiles = self::cleanAllCachedFiles();

        Config::render((object) [
            'body'         => '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="2; url=\'' . Config::$domain->site . '\'" /></head><body><h4>Перенаправление на главную...</h4><p>Очищено элементов: ' . $countAllCachedFiles . '</p></body></html>',
            'content_type' => 'text/html; charset=UTF-8',
        ]);
    }

    /**
     * @return mixed
     */
/*    public static function dbInstance($instance = '')
    {
        if (!RedisController::$instance)
        {
            RedisController::redisInstance();
        }
    }
*/

    /**
     * { function_description }
     *
     * @param      string  $data   The data
     */
    public static function autoCacheEnabler(array $data): bool
    {

        $config = Config::getCustomGlobalConfig();

        if ($data === 'enabled')
        {
            $config->cache->enabled = true;
        }

        if ($data === 'disabled')
        {
            $config->cache->enabled = false;
        }

        return Config::setCustomGlobalConfig($config);
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
                RedisController::$instance->del($key);
            }
        }
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
                $array[$key] = RedisController::$instance->get($key);
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
        return (array) json_decode(RedisController::$instance->get(static::$revision_key . ':' . $revision));
    }



    /**
     * { function_description }
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public static function cleanAllCachedFiles()
    {
        $countMapFiles = self::delMapMediaFiles();
        $pages = static::storageKeys(Config::$hash_key . ':*');
        $countPages = count($pages);

        if ($countPages > 0)
        {
            foreach ($pages as $key)
            {
                RedisController::$instance->del($key);
            }
        }

        $countAllCachedFiles = ($countPages + $countMapFiles);

        return $countAllCachedFiles;
    }


    /**
     * { function_description }
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public static function cleanCacheXhr()
    {
        $countAllCachedFiles = self::cleanAllCachedFiles();
        return Config::render((object) [
            'content_type' => 'application/json',
            'body'         => json_encode(
                [
                    'status'  => true,
                    'message' => 'Удалено: ' . $countAllCachedFiles . ' файлов кеша',
                ]
            )
        ]);
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
    public static function injectWebCleaner(
        string $html,
        bool $granted = false
    ): string
    {

        foreach (Config::$access as $access)
        {
            if (
                isset($_COOKIE['wilda']) &&
                $_COOKIE['wilda'] === md5($access->login . $access->password)
            )
            {
                $granted = true;
                break;
            }
        }


        if ($granted)
        {
            // $inject_html = file_get_contents(PATH . '/tpl/cleaner/cleaner.html');
            return str_replace('</body>','<!-- WILDA CLEANER --><script src="/app/tpl/cleaner/cleaner.js"></script><wilda-cleaner></wilda-cleaner><!-- WILDA CLEANER --></body>', (string) $html);
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
            return str_replace('</body>', '<!-- CACHE RUNTIME: ' . Config::microtimeAgo(self::$microtime) . ' --></body>', $html);
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
            Config::notice('pages cached: ' . count($pages));
        }
        else
        {
            Config::notice('cache is empty');
        }
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
        $hash = static::$revision_key . ':' . \time();
        RedisController::$instance->set(
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
/*        return RedisController::$instance->keys(
            static::storageType($key)
        );
*/
        return RedisController::$instance->keys($key);
    }

    /**
     * @param  $key
     * @return mixed
     */
/*    public static function storageType(string $key)
    {
        switch (Config::$storage->type)
        {
            case 'disk':
                return [$key, '', 1000];

            case 'memory':
                return $key;
        }
    }*/

    /**
     * Функция очищает веб-кеш, устанавливая новое пустое значение со сроком действия и отображая
     * HTML-страницу с перенаправлением на домашнюю страницу.
     *
     * страницу через 2 секунды. Страница также содержит хеш-значение и сообщение о том, что пользователь
     * перенаправляется на домашнюю страницу.
     * @return HTML-страница с метатегом обновления, который перенаправляет пользователя на домашнюю
     */
/*    public static function webCacheCleaner()
    {
*//*        $hash = Config::$config->name . ':' . Encryption::encode(Config::$config->salt);

        RedisController::$instance->set(
            (string) $hash,
            ''
        );

        RedisController::$instance->expire(
            (string) $hash,
            (int) Config::$config->cache->expire * 60
        );
*/
        /*Config::render((object) [
            'body'         => '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="2; url=\'/?clear\'" /><script>function setCookie(name,value,days) { var expires = ""; if (days) { var date = new Date(); date.setTime(date.getTime() + (days*24*60*60*1000)); expires = "; expires=" + date.toUTCString(); } document.cookie = name + "=" + (value || "")  + expires + "; path=/"; }; setCookie("' . Config::$config->name . '", "' . $hash . '",' . Config::$config->cache->expire . ');</script></head><body><h4>Перенаправление на главную...</h4><p>Hash: ' . $hash . '</p></body></html>',
            'content_type' => 'text/html; charset=UTF-8',
        ]);*/
/*        Config::render((object) [
            'body'         => '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="2; url=\'/?clear\'" /><script>function setCookie(name,value,days) { var expires = ""; if (days) { var date = new Date(); date.setTime(date.getTime() + (days*24*60*60*1000)); expires = "; expires=" + date.toUTCString(); } document.cookie = name + "=" + (value || "")  + expires + "; path=/"; }; setCookie("' . Config::$config->name . '", "' . $hash . '",' . Config::$config->cache->expire . ');</script></head><body><h4>Перенаправление на главную...</h4><p>Hash: ' . $hash . '</p></body></html>',
            'content_type' => 'text/html; charset=UTF-8',
        ]);
    }
*/

    /**
     * Sets the media file.
     *
     * @param      string  $hash   The hash
     */
    public static function setMediaFile(
        object $obj,
        string $hash
    ) : void
    {
        $obj->body = base64_encode($obj->body);

        file_put_contents(
            PATH . '/' .
            Config::$config->storage->media->cache .
            '/' .
            $hash,
            json_encode($obj)
        );
    }


    /**
     * Gets the media file.
     *
     * @param      string  $hash   The hash
     */
    public static function getMediaFile(
        string $hash
    )
    {
        return file_get_contents(
            PATH . '/' .
            Config::$config->storage->media->cache .
            '/' .
            $hash
        );
    }


    /**
     * Gets the path.
     *
     * @param      string  $path   The path
     */
    public static function getEncyptMapPath(string $path)
    {
        $hash = Encryption::encode($path);

        if (self::getMapFilePath($hash, $path) === false)
        {
            self::setMapFilePath($hash, $path);
        }

        return $hash;
    }


    /**
     * Gets the map file path.
     *
     * @param      string  $hash   The hash
     *
     * @return     object  The map file path.
     */
    public static function getMapFilePath(
        string $hash
    )
    {
        return RedisController::$instance->hGet(
            Config::$hash_key . ':' . Config::$config->storage->keys->pathMap,
            $hash
        );
    }


    /**
     * { function_description }
     *
     * @param      int       $countMapFiles  The count map files
     *
     * @return     bool|int  ( description_of_the_return_value )
     */
    public static function delMapMediaFiles($countMapFiles = 0): int
    {
        $mapFiles = self::listMapFilePath();

        foreach(Config::URI_QUERY_TYPES as $types)
        {
            foreach(Config::DEVICE_DIMENSIONS as $dimension)
            {
                $hashFile = PATH . '/' .
                    Config::$config->storage->media->cache .
                    '/' .
                    Config::$hash_key .
                    ':' .
                    strtolower($dimension) .
                    ':' .
                    $types .
                    ':';

                foreach($mapFiles as $hash => $url)
                {
                    if (file_exists($hashFile . $hash))
                    {
                        $countMapFiles++;
                        unlink($hashFile . $hash);
                    }
                }
            }
        }

        return $countMapFiles > 0 ? $countMapFiles : 0;
    }


    /**
     * { function_description }
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public static function listMapFilePath()
    {
        return RedisController::$instance->hGetAll(
            Config::$hash_key . ':' . Config::$config->storage->keys->pathMap
        );
    }



    /**
     * Sets the map file path.
     *
     * @param      string  $hashPath  The hash path
     * @param      string  $path      The path
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public static function setMapFilePath(
        string $hashPath,
        string $path
    )
    {
        return RedisController::$instance->hSet(
            Config::$hash_key . ':' . Config::$config->storage->keys->pathMap,
            $hashPath,
            $path
        );
    }




    /**
     * Gets the specified hash.
     *
     * @param  string $hash The hash
     * @return object ( description_of_the_return_value )
     */
    public static function getCachedPage(string $hash): object
    {
        $filecache = PATH . '/' .
            Config::$config->storage->media->cache .
            '/' .
            $hash;

        // var_dump($filecache);
        // die();
        if (file_exists($filecache))
        {
            // return Curl::curlErrorHandler(503);
            $res = self::getMediaFile($hash);
        }
        else
        {
            // if (Config::$config->storage->redis->compression)

            $res = RedisController::$instance->get($hash);
        }

        if ($res)
        {
            $obj       = json_decode($res);
            $obj->body = base64_decode($obj->body);
            $obj->cache = 'HIT';
        }
        else
        {
            $obj = (object) [];
        }

        return $obj;
    }



    /**
     * Эта функция устанавливает значение кэша с заданным хешем и сроком действия на основе свойств
     * входного объекта.
     *
     * кэшированного объекта. Он используется для последующего извлечения кэшированного объекта.
     * @param object obj  Объект, который необходимо кэшировать. Он содержит тело и тип содержимого.
     * @param string hash Параметр hash — это строка, которая служит уникальным идентификатором
     */
    public static function setCachedPage(
        object $obj,
        string $hash
    ): void
    {

        if (in_array($obj->content_type, Config::URI_MEDIA_IMAGES_TYPES))
        {
            self::setMediaFile($obj, $hash);
        }
        else
        {
            // if (Config::$config->storage->redis->compression)
            // RedisController::setCompression(Config::$config->storage->redis->compression);

            RedisController::$instance->set(
                (string) $hash,
                (string) json_encode([
                    'body'         => base64_encode($obj->body),
                    'content_type' => $obj->content_type,
                    'headers'      => $obj->headers,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );

            RedisController::$instance->expire(
                (string) $hash,
                (int) Config::$config->cache->expire
            );
        }

        $obj = (object) [];
    }


    /**
     * { function_description }
     */
/*    public static function redisInstance(): void
    {
        RedisController::$instance = new \Redis();
        RedisController::$instance->connect(Config::$config->storage->redis->host, Config::$config->storage->redis->port);
    }*/

    /**
     * { function_description }
     */


    /**
     * [preCachedRequest description]
     * @return [type] [description]
     */
    public static function preCachedRequest()
    {
        self::$microtime = \microtime(true);

        $obj = (object) [];
        if (Config::$config->cache->enabled)
        {
            $obj = self::getCachedPage(Config::$hash);

            if (count((array) $obj) === 0)
            {
                $obj = Curl::rget();
                $obj->cache = 'MISS';
                
                if (empty($obj))
                {
                    Curl::curlErrorHandler(500);
                }
                else
                {
                    self::setCachedPage(
                        Modify::byContentType($obj),
                        Config::$hash
                    );
                }
            }

            if (preg_match('/text\/html/', $obj->content_type))
            {
                $obj->body = self::injectWebCleaner(
                    self::injectWebStats($obj->body)
                );
            }
        }
        else
        {
            $obj = Curl::rget();
            $obj->cache = 'BYPASS';

            if (empty($obj))
            {
                Curl::curlErrorHandler(500);
            }
            else
            {
                return Modify::byContentType($obj);
            }
        }


        return $obj;
    }


    public static function sendCleanCacheSite() : void
    {
        $domain_config = Config::getDomainConfig(Config::$cli->getArg('site'));
        if (count((array) $domain_config) > 0)
        {
            Config::$hash_key = Config::$config->name . ':' . Config::$cli->getArg('site') . ':' . $domain_config->type;
            $countAllCachedFiles = self::cleanAllCachedFiles();
            Config::notice('Удалено: ' . $countAllCachedFiles . ' файлов кеша. Сайт: ' . Config::$cli->getArg('site'));
        }
        else
        {
            Config::notice('No site defined');
        }


        // echo 'Удалено: ' . $countAllCachedFiles . ' файлов кеша. Сайт: ' . $site,
    }

}
