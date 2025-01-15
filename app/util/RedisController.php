<?php


namespace app\util;

use app\core\Config;


class RedisController {

    public static $instance;

    function __construct() {
        self::redisInstance();
    }

    public static function setCompression(bool $enabled = false) : void
    {
        if ($enabled)
        {
            self::$instance->setOption(\Redis::OPT_COMPRESSION, \Redis::COMPRESSION_ZSTD);
            self::$instance->setOption(\Redis::OPT_COMPRESSION_LEVEL, 9);
        }
        else
        {
            self::$instance->setOption(\Redis::OPT_COMPRESSION, \Redis::COMPRESSION_NONE);
        }
    }

    public static function redisInstance(): void
    {
        try {
            static::$instance = new \Redis();
            static::$instance->connect(
                Config::$config->storage->redis->host,
                Config::$config->storage->redis->port
            );

            RedisController::setCompression(Config::$config->storage->redis->compression);

        }
        catch (\Exception $e)
        {
            die(__LINE__ . ' ' . $e->getMessage());
        }
    }
}