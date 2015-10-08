<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-09-28
 * Time: 11:01 AM
 */

namespace ShortCirquit\WordPressApi;


use yii\base\Object;

class ApiRequest extends Object
{
    public $method = 'GET';
    public $url = null;
    public $headers = [];
    public $params = [];
    public $curlOptions = [];
    public $body = null;
}