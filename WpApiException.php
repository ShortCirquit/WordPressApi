<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-09-28
 * Time: 11:13 AM
 */

namespace ShortCirquit\WordPressApi;


class WpApiException extends \Exception
{
    public function __construct($code, $message)
    {
        $this->code = $code;
        $this->message = $message;
    }
}
