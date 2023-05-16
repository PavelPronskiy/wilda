<?php

namespace app\core;

use app\core\Config;


class Access
{
    function __construct()
    {

    }

    /**
     * Функция проверяет, установлены ли файлы cookie пользователя и пароля, и перебирает массив
     * доступа в классе Config.
     * 
     * @return bool Возвращается логическое значение.
     */
    public static function loginUserPassword(object $post) : void
    {
        if (
            isset($post->login) &&
            isset($post->password) &&
            !empty($post->login) &&
            !empty($post->password)
        )
            foreach (Config::$access as $access)
                if (
                    (string) $post->login === (string) $access->login &&
                    (string) $post->password === (string) $access->password
                )
                {
                    $_SESSION[ 'LOGIN' ] = $access->login;
                    $_SESSION[ 'PASSWORD' ] = $access->password;
                    $_SESSION[ 'SIGN' ] = md5($access->login . $access->password);

                    Config::render((object) [ 
                        'content_type' => 'application/json',
                        'body' => json_encode((object) [ 
                            'format' => 'json',
                            'message' => 'Успешный вход!',
                            'valid' => true
                        ])
                    ]);
                }


        Config::render((object) [ 
            'content_type' => 'application/json',
            'error' => true,
            'code' => 400,
            'body' => json_encode((object) [ 
                'format' => 'json',
                'message' => 'Неверный логин или пароль!',
                'valid' => false
            ])
        ]);
    }

    public static function check() : bool
    {
        if (
            isset($_SESSION[ 'SIGN' ]) &&
            isset($_SESSION[ 'LOGIN' ]) &&
            isset($_SESSION[ 'PASSWORD' ])
        )
            if (md5($_SESSION[ 'LOGIN' ] . $_SESSION[ 'PASSWORD' ]) === $_SESSION[ 'SIGN' ])
                return true;

        return false;
    }


    /**
     * Эта функция перенаправляет пользователя на новый URL-адрес, используя код состояния 301.
     * 
     * @param string path Параметр пути — это строка, представляющая URL-адрес, на который будет
     * перенаправлен пользователь.
     */
    public static function redirect(string $path): void
    {
        header("HTTP/1.1 301 Moved Permanently");
        header('Location: ' . $path);
        die;
    }


    /**
     * Функция выводит пользователя из системы, уничтожая сеанс и перенаправляя его на домашнюю страницу.
     */
    public static function logout()
    {
        session_destroy();
        self::redirect('/');
    }


}