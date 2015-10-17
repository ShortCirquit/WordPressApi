<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-12
 * Time: 3:24 PM
 */

namespace ShortCirquit\WordPressApi\Test;


class OrgApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrgWpApi
     */
    protected $api;

    public function setUp()
    {
        $file = __DIR__ . '/config_org.json';
        if (!file_exists($file)){
            $this->api = new OrgWpApi();
            $cfg = $this->api->getConfig();
            $cfg['token'] = null;
            file_put_contents($file, json_encode($cfg, JSON_PRETTY_PRINT));
            $this->fail('No configuration exists. Create blank: ' . $file);
        }

        $cfg = json_decode(file_get_contents($file), true);
        $this->api = new OrgWpApi($cfg);
    }

}
