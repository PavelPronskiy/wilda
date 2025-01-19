<?php

namespace app\module;

use app\core\Config;
use app\core\Tags;

/**
 * Tilda Controller
 */
class Tilda extends Tags
{
    public function __construct()
    {
        // var_dump('123');
    }

    /**
     * [changeAHrefLinks description]
     * @return [type] [description]
     */
    public static function changeAHrefLinks(): void
    {
        foreach (self::$dom->getElementsByTagName('a') as $tag)
        {
            if (str_contains($tag->getAttribute('href'), Config::getProjectName()))
            {
                $proto_url = Config::forceProto($tag->getAttribute('href'));
                $parse_url = parse_url($proto_url);
                $tag->setAttribute(
                    'href',
                    str_replace($parse_url['host'], Config::getSiteName(), $proto_url)
                );
            }
            elseif ($tag->getAttribute('href') == Config::$domain->project . Config::$route->path)
            {
                $tag->setAttribute(
                    'href',
                    Config::$route->url
                );
            }
        }
    }

    /**
     * { function_description }
     */
    public static function changeFavicon(): void
    {
        if (Config::$favicon->enabled)
        {
            foreach (self::$dom->getElementsByTagName('link') as $elem)
            {
                if (stripos($elem->getAttribute('rel'), 'shortcut icon') === 0)
                {
                    if (file_exists(Config::$favicon->path . '/' . Config::getSiteName() . '.ico'))
                    {
                        $elem->setAttribute('href', Config::$domain->site . '/' . Config::$favicon->path . '/' . Config::getSiteName() . '.ico');
                    }
                    else
                    {
                        $elem->setAttribute('href', Config::$domain->site . '/' . Config::$favicon->path . '/default.ico');
                    }
                }
            }
        }
    }

    /**
     * [changeImgTags description]
     * @return [type] [description]
     */
    public static function changeImgTags(): void
    {

        /**
         * tag <style>
         */
        foreach (self::$dom->getElementsByTagName('style') as $style)
        {
            switch (Config::$config->images)
            {
                case 'relative':
                    preg_match('/static\.tildacdn\.(info|com)/', $style->textContent, $matched);
                    if (count($matched) > 0)
                    {
                        $style->textContent = preg_replace_callback(
                            "/background\-image\:\s?url\(\'(.*)\'\)/",
                            // "/background\-image\: url\(\'(.*)\'\)/",
                            function ($matches)
                            {
                                if (isset($matches[1]))
                                {
                                    return "background-image: url('" . Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($matches[1]), 'images') . "')";
                                }
                            },
                            $style->textContent
                        );
                    }
                    break;
            }
        }
    }

    /**
     * [changeScriptTags description]
     * @return [type] [description]
     */
    public static function changeScriptTags(): void
    {
        $script_tags = self::$dom->getElementsByTagName('script');
        foreach ($script_tags as $index => $script)
        {

            switch (Config::$config->scripts)
            {
                case 'relative':
                    if ($script->getAttribute('id') === 'sentry')
                    {
                        $script->parentNode->removeChild($script);
                    }

                    $src      = $script->getAttribute('src');
                    $data_url = $script->getAttribute('data-url');

                    if (!empty($src))
                    {
                        $script->setAttribute('src', Config::QUERY_PARAM_JS . self::getRelativePath(self::parseURL($src), 'scripts'));
                    }

                    if (!empty($data_url))
                    {
                        $script->setAttribute('data-url', Config::QUERY_PARAM_JS . self::getRelativePath(self::parseURL($data_url), 'scripts'));
                    }

                    /**
                     * tag <script>
                     */
                    preg_match('/static\.tildacdn\.(info|com)/', $script->textContent, $matched);
                    if (count($matched) > 0)
                    {
                        $script->textContent = preg_replace_callback(
                            "/s\.src = \'(.*)\'/",
                            function ($matches)
                            {
                                if (isset($matches[1]))
                                {
                                    return "s.src = '" . Config::QUERY_PARAM_JS . self::getRelativePath(self::parseURL($matches[1]), 'scripts') . "'";
                                }

                            },
                            $script->textContent
                        );
                    }
                    break;
            }

            // remove tilda tracker
            preg_match('/window\.mainTracker.*/', $script->textContent, $matched);
            if (count($matched) > 0)
            {
                $script->parentNode->removeChild($script);
            }
        }
    }

    /**
     * [changeSubmitSuccessMessage description]
     * @return [type] [description]
     */
    public static function changeSubmitSuccessMessage()
    {
        if (Config::$mail->enabled)
        {
            foreach (self::getElementsByClass(self::$dom, 'div', 'js-successbox') as $elem)
            {
                $elem->setAttribute('data-success-message', Config::$mail->success);
            }
        }
    }

    /**
     * @param  string  $content
     * @return mixed
     */
    public static function css(string $content): string
    {
        preg_match('/static\.tildacdn\.(com|info)/', $content, $matched);
        if (count($matched) > 0)
        {
            $content = preg_replace_callback(
                "/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i",
                function ($matches)
                {
                    if (isset($matches[3]))
                    {
                        // var_dump($matches[3]);
                        return "url('" . Config::QUERY_PARAM_FONT . self::getRelativePath(self::parseURL($matches[3]), 'fonts') . "')";
                    }
                },
                $content
            );
            // var_dump($matched);
        }

        // $content = str_replace('', '', $content);

        return $content;
    }


    /**
     * Change HTML page elements
     */
    public static function changeHTMLElements(): void
    {
        // var_dump(Config::$mail);
        $xpath = new \DOMXPath(self::$dom);
        foreach ($xpath->query("//div[@data-tilda-root-zone]") as $item)
        {
            for ( $k=0; $k < $item->attributes->length; $k++)
            {
                if ($item->attributes->item($k)->nodeName === 'data-tilda-root-zone')
                {
                    $item->removeAttributeNode(
                        $item->attributes->item($k)
                    );
                }
            }
        }
    }

    /**
     * @param string $html
     */
    public static function html(string $html): string
    {
        self::initialize(
            self::preProcessHTML($html)
        );

        parent::changeDomElements();
        self::changeHTMLElements();
        self::changeAHrefLinks();
        self::changeScriptTags();
        self::removeTildaCopy();
        self::removeCounters();
        self::changeSubmitSuccessMessage();
        self::changeFavicon();
        self::changeImgTags();

        return self::postProcessHTML();
    }

    /**
     * @param  $body
     * @return mixed
     */
    public static function javascript($body)
    {
        if (Config::$mail->enabled)
        {
            $body = str_replace([
                'forms.tildacdn.com',
                'forms2.tildacdn.com'
            ], Config::getSiteName(), $body);
        }

        return $body;
    }

    /**
     * [removeCounters description]
     * @return [type] [description]
     */
    public static function removeCounters(): void
    {
        foreach (self::$dom->getElementsByTagName('script') as $script)
        {
            if (strlen($script->textContent) == 564)
            {
                $script->parentNode->removeChild($script);
            }
        }
    }

    /**
     * [removeTildaCopy description]
     * @return [type] [description]
     */
    public static function removeTildaCopy(): void
    {
        $tildacopy = self::$dom->getElementById('tildacopy');
        if ($tildacopy)
        {
            $tildacopy->parentNode->removeChild($tildacopy);
        }
    }

    /**
     * { function_description }
     *
     * @param  string $content The content
     * @return string ( description_of_the_return_value )
     */
    public static function robots(string $content): string
    {
        $project = str_replace(self::$proto, '', Config::$domain->project);
        $site    = str_replace(self::$proto, '', Config::$domain->site);

        // change host
        if (preg_match('/project/', $project))
        {
            $content = preg_replace(
                '/Host:.*/',
                'Host: ' . Config::$route->domain,
                $content
            );
        }
        else
        {
            $content = str_replace(
                $project,
                $site,
                $content
            );
        }

        // remove disallow directives
        $content = str_replace(
            'Disallow: /',
            '',
            $content
        );

        // replace sitemap
        $content = str_replace(
            (string) Config::getProjectName(),
            (string) Config::getSiteName(),
            $content
        );

        $content = str_replace(
            self::$proto[0],
            self::$proto[1],
            $content
        );

        return $content;
    }

    /**
     * { function_description }
     *
     * @param  string $content The content
     * @return string ( description_of_the_return_value )
     */
    public static function sitemap(string $content): string
    {
        // var_dump($content);
        return str_replace(
            self::$proto[0],
            self::$proto[1],
            str_replace(
                (string) Config::getProjectName(),
                (string) Config::getSiteName(),
                $content
            )
        );
    }
}
