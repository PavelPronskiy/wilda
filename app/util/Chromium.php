<?php

/* Класс Chromium предоставляет методы для работы с chromium. */

namespace app\util;

use app\core\Config;

/**
 * This class describes a cache.
 */
class Chromium
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

    public function __construct()
    {
        
    }


    /**
     * Gets the settings.
     */
    public static function getSettings()
    {
        Config::render((object) [
            'content_type' => 'application/json',
            'body' => json_encode(Config::$chromium),
        ]);
    }


    public static function getAutoCacheHosts(array $sites = [])
    {
        foreach(Config::$hosts as $host)
            foreach($host->site as $site)
                $sites[] = $site;

        Config::render((object) [
            'content_type' => 'application/json',
            'body' => json_encode($sites),
        ]);
    }


    /**
     * Gets the statistics.
     */
    public static function getStats(array $links = [])
    {
        // $summary = RedisController::$instance->hGet('');
        $lastrun_global = RedisController::$instance->get(Config::$config->name . ':' . Config::$config->storage->keys->chromium->lastrun);

        $success_global = 0;
        $broken_global = 0;
        $error_global = 0;

        foreach(Config::getAllHosts() as $idx => $host)
        {
            $host_name = Config::removeProtoUrl($host);
            $links[$host_name] = [];

            $lastrun = RedisController::$instance->hGet(Config::$config->name . ':' . Config::$config->storage->keys->chromium->links . ':' . $host_name, 'lastrun');
            $broken = RedisController::$instance->hGet(Config::$config->name . ':' . Config::$config->storage->keys->chromium->links . ':' . $host_name, 'broken');
            $success = RedisController::$instance->hGet(Config::$config->name . ':' . Config::$config->storage->keys->chromium->links . ':' . $host_name, 'success');
            $error = RedisController::$instance->hGet(Config::$config->name . ':' . Config::$config->storage->keys->chromium->links . ':' . $host_name, 'error');
            $links[$host_name]['lastrun'] = $lastrun ? $lastrun : 0;
            $links[$host_name]['broken'] = $broken ? $broken : 0;
            $links[$host_name]['success'] = $success ? $success : 0;
            $links[$host_name]['error'] = $error ? $error : 0;

            $success_global += $success;
            $broken_global += $broken;
            $error_global += $error;
        }

        return [
            'global' => [
                'lastrun' => $lastrun_global,
                'links' => [
                    'success' => $success_global,
                    'broken' => $broken_global,
                    'error' => $error_global,
                ]
            ],
            'links' => $links
        ];
    }


    /**
     * Pushes to revalidate sites.
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public static function sendToRevalidateSites(array $sites = [])
    {
        if (Config::$cli->getArg('site'))
        {
            foreach (explode(',', Config::$cli->getArg('site')) as $site)
            {
                $sites[] = 'https://' . $site;
            }
            
            self::sendRevalidateSite(
                $sites
            );
        }
        else
        {
            self::sendRevalidateSite(
                Config::getAllHosts()
            );
        }
    }

    /**
     * Pushes a revalidate site.
     *
     * @param      <type>  $data   The data
     */
    public static function sendRevalidateSite($data) : bool
    {
        return RedisController::$instance->publish(Config::$chromium->topic, json_encode([
            'event' => 'autocache',
            'url' => $data
        ]));
    }


    /**
     * Sends an automatic cache enabler.
     *
     * @param      <type>  $data   The data
     *
     * @return     bool    ( description_of_the_return_value )
     */
    public static function sendAutoCacheEnabler($data) : bool
    {
        return RedisController::$instance->publish(Config::$chromium->topic, json_encode([
            'event' => 'autocache-enabler',
            'config' => $data
        ]));
    }


    /**
     * Sends an update automatic cache.
     *
     * @param      <type>  $data   The data
     *
     * @return     bool    ( description_of_the_return_value )
     */
    public static function sendUpdateAutoCache($data) : bool
    {
        return RedisController::$instance->publish(Config::$chromium->topic, json_encode([
            'event' => 'autocache-update',
            'config' => $data
        ]));
    }

    /**
     * { function_description }
     *
     * @param      <type>  $data   The data
     */
    public static function updateRevalidateHours($data, $ret = false) : bool
    {
        $hours = (int) $data;
        if ($hours > 0 && $hours < 13)
        {
            $config = Config::getCustomChromiumConfig();
            foreach($config->cron->schedule as $idx => $schedule)
            {
                if ($schedule->event === 'autocache')
                {
                    $config_exp = explode(' ', $schedule->time);

                    $config_exp[2] = '*/' . $hours;
                    $schedule->time = implode(' ' , $config_exp);

                    $crontab = new Crontab();
                    $crontab->updateAutocacheJob($hours);

                    $config->cron->schedule[$idx] = $schedule;


                    $ret = Config::setCustomChromiumConfig($config);

                    // $ret = self::sendUpdateAutoCache($config);
                }
            }
        }

        return $ret;
    }

}
