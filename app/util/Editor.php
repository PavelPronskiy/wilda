<?php

namespace app\util;

use app\core\Access;
use app\core\Config;

/**
 * Editor methods
 */
class Editor
{
    /**
     * Это функция-конструктор, которая обрабатывает запросы, связанные с настройками конфигурации, и
     * отображает страницу HTML-редактора.
     */
    public function __construct()
    {
        session_start();
        self::route(
            (array) json_decode(
                file_get_contents('php://input')
            )
        );
    }

    /**
     * Эта функция проверяет, есть ли у пользователя доступ и маршруты к соответствующей странице
     * конфигурации или аутентификации.
     *
     * приложения. Скорее всего, он будет включать такую информацию, как запрошенный URL-адрес, метод
     * HTTP и любые параметры или данные, связанные с запросом.
     * @param array input Входной параметр — это массив, содержащий данные, связанные с маршрутизацией
     */
    public static function route(array $input): void
    {
        $POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $POST = (object) $_POST;

        if (isset($POST->login))
        {
            Access::loginUserPassword($POST);
        }

        if (Access::check())
        {
            self::routeConfig($input);
        }
        else
        {
            Config::render((object) [
                'content_type' => 'text/html',
                'body'         => self::shortcodes(
                    file_get_contents(
                        dirname(__DIR__) . '/' .
                        Config::$editor->path .
                        '/auth.html'
                    )
                ),
            ]);
        }
    }

    /**
     * Эта функция обрабатывает маршрутизацию и настройку приложения PHP, включая сохранение и
     * извлечение данных конфигурации.
     *
     * «config», «revision», «save» и «data».
     * @param array input Массив входных параметров для функции. Он может содержать следующие ключи:
     */
    public static function routeConfig(array $input): void
    {
        if (isset($input) && count($input) > 0)
        {
            if (isset($input['config']) && $input['config'])
            {
                if (isset($input['revision']) && !empty($input['revision']))
                {
                    $config_rev      = Cache::getConfigRevision($input['revision']);
                    $config_all_revs = Cache::getAllKeysConfigRevisions();

                    // var_dump($config_all_revs['revisions']);
                    if (($key = array_search($input['revision'], $config_all_revs['revisions'])) !== false)
                    {
                        unset($config_all_revs['revisions'][$key]);
                        Cache::delConfigRevision($input['revision']);
                        Config::setHostsConfig($config_rev);
                    }

                    Config::render((object) [
                        'content_type' => 'application/json',
                        'body'         => json_encode(
                            array_merge(
                                (array) $config_all_revs,
                                (array) $config_rev
                            )
                        ),
                    ]);
                }
                else
                {
                    Config::render((object) [
                        'content_type' => 'application/json',
                        'body'         => json_encode(
                            array_merge(
                                (array) Cache::getAllKeysConfigRevisions(),
                                (array) Config::getHostsConfig()
                            )
                        ),
                    ]);
                }
            }

            // Сохранение изменений в hosts.json
            if (isset($input['save']) && isset($input['data']) && $input['save'])
            {
                if (Config::validateConfig($input['data']))
                {
                    $hosts = json_decode($input['data']);

                    Cache::setConfigRevision($hosts);
                    Config::setHostsConfig($hosts);

                    $result = (object) [
                        'status'  => true,
                        'message' => 'Сохранено',
                    ];
                }
                else
                {
                    $result = (object) [
                        'status'  => false,
                        'message' => 'Ошибка синтаксиса! Не сохранено.',
                    ];
                }

                Config::render((object) [
                    'content_type' => 'application/json',
                    'body'         => json_encode($result),
                ]);
            }
            
            // Сохранение настроек кеширования
            if (isset($input['cache-settings-save']) && isset($input['data']) && $input['cache-settings-save'])
            {
                $input['data'] = (array) $input['data'];
                if (Chromium::updateRevalidateHours($input['data']['cache-revalidate-hours']))
                {
                    $result = (object) [
                        'status'  => true,
                        'message' => Config::getLangTranslationMessage(1004),
                    ];
                }
                else
                {
                    $result = (object) [
                        'status'  => false,
                        'message' => Config::getLangTranslationMessage(1005),
                    ];
                }

                Config::render((object) [
                    'content_type' => 'application/json',
                    'body'         => json_encode($result),
                ]);
            }

            // Отправка ревалидации кеша для определённого сайта
            if (isset($input['revalidate-cache']) && isset($input['data']) && $input['revalidate-cache'])
            {
                if (Chromium::sendRevalidateSite([
                    $input['data']
                ]))
                {
                    $result = (object) [
                        'status'  => true,
                        'message' => Config::getLangTranslationMessage(1007),
                    ];
                }
                else
                {
                    $result = (object) [
                        'status'  => false,
                        'message' => Config::getLangTranslationMessage(1008),
                    ];
                }

                Config::render((object) [
                    'content_type' => 'application/json',
                    'body'         => json_encode($result),
                ]);
            }

            // Включение/отключение автокеша
            if (isset($input['cron-cache-enabler']) && isset($input['data']) && $input['cron-cache-enabler'])
            {
                if (Cache::autoCacheEnabler([
                    $input['data']
                ]))
                {
                    $result = (object) [
                        'status'  => true,
                        'message' => Config::getLangTranslationMessage(1004),
                    ];
                }
                else
                {
                    $result = (object) [
                        'status'  => false,
                        'message' => Config::getLangTranslationMessage(1005),
                    ];
                }

                Config::render((object) [
                    'content_type' => 'application/json',
                    'body'         => json_encode($result),
                ]);
            }
        }

        Config::render((object) [
            'content_type' => 'text/html',
            'body'         => self::shortcodes(
                file_get_contents(
                    dirname(__DIR__) . '/' .
                    Config::$editor->path .
                    '/editor.html'
                )
            ),
        ]);

    }

    /**
     * Эта функция PHP заменяет строку "{GLOBAL_CONFIG}" версией глобальной конфигурации в формате JSON,
     * полученной из класса Config.
     *
     * Функция «shortcodes» принимает этот HTML-код в качестве входных данных и заменяет строку
     * «{GLOBAL_CONFIG}» константой JavaScript, которая содержит глобальные параметры конфигурации
     * приложения. Константа создается вызовом
     * которое задает значение "GLOBAL_CONFIG" равным закодированному в JSON значению глобальной
     * конфигурации, полученной из класса Config.
     * @param  html          Параметр «html» — это строка, представляющая HTML-код, который необходимо изменить.
     * @return строка, которая заменяет заполнитель "{GLOBAL_CONFIG}" объявлением константы JavaScript,
     */
    public static function shortcodes(string $html)
    {
        $html = str_replace('{HOSTS_CONFIG}', 'const HOSTS_CONFIG=' . json_encode(Config::getHostsConfig()->hosts), (string) $html);
        $html = str_replace('{GLOBAL_CONFIG}', 'const GLOBAL_CONFIG=' . json_encode(Config::getGlobalConfig()), (string) $html);
        $html = str_replace('{CHROMIUM_CONFIG}', 'const CHROMIUM_CONFIG=' . json_encode(Config::$chromium), (string) $html);
        $html = str_replace('{CHROMIUM_STATS}', 'const CHROMIUM_STATS=' . json_encode(Chromium::getStats()), (string) $html);

        return $html;
    }
}
