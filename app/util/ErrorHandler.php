<?php

namespace app\util;

use app\core\Config;


class ErrorHandler extends \Exception {
    protected $severity;
    
    public function __construct(string $message = '', int $code) {
        $this->message = $message;
        $this->code = $code;
        // $this->severity = $severity;
        // $this->file = $filename;
        // $this->line = $lineno;
    }
    

    /**
     * { function_description }
     *
     * @param      int|string  $code          The code
     * @param      string      $code_message  The code message
     *
     * @return     string      ( description_of_the_return_value )
     */
    public static function webTemplateReloader(
        int $code,
        string $code_message = '',
        string $trace = ''
    ): string
    {
        $translated_message = Config::getLangTranslationMessage($code);
        return '<html><head><title>' . $translated_message['title'] . '</title><script>function rs(t,n){200===n&&window.location.reload()}function fs(t){let n=new XMLHttpRequest;n.onreadystatechange=function(){4==this.readyState&&rs(this,this.status)},n.open("HEAD",t),n.send()}setInterval(function(){fs("/")},3e3);</script><meta name="robots" content="noindex,nofollow"><style>.spinner {height:100%;display:flex;align-items: center;justify-content:center;} .spinner div {width:20%;height:10%;margin-top:-10%}</style></head><body><!-- Ошибка: ' . $code . ' ' . $translated_message['text'] . ' --><div class="spinner"><div>' . file_get_contents(PATH . '/app/tpl/images/fade-stagger-circles.svg') . '</div></div></body></html>';
    }


    /**
     * { function_description }
     *
     * @param      int|string  $code          The code
     * @param      string      $code_message  The code message
     *
     * @return     string      ( description_of_the_return_value )
     */
    public static function webTemplatePageMessage(
        int $code,
        string $code_message = '',
        string $trace = ''
    ): string
    {
        $message = Config::getLangTranslationMessage($code);
        $b64_trace = base64_encode($trace);

        if (isset($message['title']) && isset($message['text']))
        {
            return '<html><head><meta name="robots" content="noindex,nofollow"><title>' . $message['title'] . '</title><style>.fieldset {word-wrap: break-word;white-space: pre-wrap;border:1px solid black;padding:10px;background-color:#ccc;width:70%}</style></head><body><div><h1>Ошибка: ' . $code . '</h1><strong>' . $message['text'] . '.</strong></div><div class="fieldset-wrap"><pre class="fieldset">' . $b64_trace . '</pre></div></body></html>';
        }
        else
        {
            return '<html><head><meta name="robots" content="noindex,nofollow"><title>' . $code_message . '</title><style>.fieldset {word-wrap: break-word;white-space: pre-wrap;border:1px solid black;padding:10px;background-color:#ccc;width:70%}</style></head><body><div><h1>Ошибка: ' . $code . '</h1><strong>' . $code_message . '.</strong></div><div class="fieldset-wrap"><pre class="fieldset">' . $b64_trace . '</pre></div></body></html>';
        }
    }

    /**
     * { function_description }
     *
     * @param      <type>  $e      { parameter_description }
     */
    public static function exception($e)
    {
        $msg = (object) [
            'no_cache'     => true,
            'error'        => true,
            'content_type' => 'text/html',
        ];

        switch ($e->getCode())
        {
            case 4004:
            case 404:
            case 0:
                $msg->body = self::webTemplatePageMessage(
                    $e->getCode(),
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
            break;
            default:
                $msg->body = self::webTemplateReloader(
                    $e->getCode(),
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
            break;
        }

        Config::render($msg);
    }


    /**
     * { function_description }
     *
     * @param      <type>  $e      { parameter_description }
     */
    public static function error($e)
    {
        // Config::render((object) [
        //     'content_type' => 'text/html',
        //     'body' => self::webTemplate(500, $e->getMessage()),
        // ]);
        Config::render((object) [
            'content_type' => 'application/json',
            'body' => json_encode([
                "code" => $e->getCode(),
                "message" => $e->getMessage(),
            ]),
        ]);
    }
}
