<?php

namespace app\util;


class ErrorHandler extends \Exception {
    protected $severity;
    
    public function __construct($message, $code, $severity, $filename, $lineno) {
        $this->message = $message;
        $this->code = $code;
        $this->severity = $severity;
        $this->file = $filename;
        $this->line = $lineno;
    }
    
    public static function webTemplate(int $code, string $message): string
    {
        return '<html><head><script>function rs(t,n){200===n&&window.location.reload()}function fs(t){let n=new XMLHttpRequest;n.onreadystatechange=function(){4==this.readyState&&rs(this,this.status)},n.open("HEAD",t),n.send()}setInterval(function(){fs("/")},3e3);</script><meta name="robots" content="noindex,nofollow"><style>.spinner {height:100%;display:flex;align-items: center;justify-content:center;} .spinner div {width:20%;height:10%;margin-top:-10%}</style></head><body><!-- Ошибка: ' . $code . ' ' . $message . ' --><div class="spinner"><div>' . file_get_contents(PATH . '/app/tpl/images/fade-stagger-circles.svg') . '</div></div></body></html>';
    }

    public static function exception($e)
    {
        var_dump('Exception');
        // return $this->severity;
    }

    public static function error($e)
    {
        var_dump($e);
        // return $this->severity;
    }
}
