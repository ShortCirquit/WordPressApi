<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-09-28
 * Time: 11:01 AM
 */

namespace ShortCirquit\WordPressApi;

class ApiRequest
{
    public $method = 'GET';
    public $url = null;
    public $headers = [];
    public $params = [];
    public $curlOptions = [];
    public $body = null;

    public function __construct(array $cfg = [])
    {
        $params = ['method', 'url', 'header', 'params', 'curlOptions', 'body'];
        foreach ($params as $p)
        {
            if (isset($cfg[$p]))
            {
                $this->$p = $cfg[$p];
            }
        }
    }
}
