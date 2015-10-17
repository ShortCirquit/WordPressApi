<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-12
 * Time: 10:43 AM
 */

namespace ShortCirquit\WordPressApi\Test;

use ShortCirquit\WordPressApi\ComWpApi;

/**
 * Class ApiComTest
 *
 * @package ShortCirquit\WordPressApi\Test
 */
class ComApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ComWpApi
     */
    protected $api;

    public function setUp(){
        $file = __DIR__ . '/config_com.json';
        if (!file_exists($file)){
            $this->api = new ComWpApi();
            $cfg = $this->api->getConfig();
            $cfg['token'] = null;
            file_put_contents($file, json_encode($cfg, JSON_PRETTY_PRINT));
            $this->fail('No configuration exists. Create blank: ' . $file);
        }

        $cfg = json_decode(file_get_contents($file), true);
        $this->api = new ComWpApi($cfg);
        $this->assertNotNull($this->api->clientId, 'Configuration is blank');
    }


    public function testConnection()
    {
        $this->assertNotNull($this->api->getAuthorizeUrl(), 'Authorize URL not created.');
    }

    public function testCreatePosts(){
        $p1 = $this->createPost('title1', 'content1');
        $p2 = $this->createPost('title2', 'content2');
        $this->assertNotEquals($p1['ID'], $p2['ID'], 'Two created posts should not have the same ID');
        return [$p1, $p2];
    }

    /**
     * @depends testCreatePosts
     */
    public function testGetPosts(array $posts){
        $resp = $this->api->listPosts();
        $this->assertArrayHasKey('found', $resp);
        $this->assertArrayHasKey('posts', $resp);
        $this->assertGreaterThanOrEqual(count($posts), $resp['found']);

        $ids = array_map(function($p){return $p['ID'];}, $resp['posts']);
        foreach($posts as $p){
            $this->assertContains($p['ID'], $ids);
        }

        foreach ($posts as $p){
            $post = $this->api->getPost($p['ID']);
            $this->assertArrayHasKey('ID', $post);
            $this->assertArrayHasKey('title', $post);
            $this->assertArrayHasKey('content', $post);
            $this->assertArrayHasKey('author', $post);

            $this->assertStringStartsWith('UnitTest: content', $post['content']);
            $this->assertStringStartsWith('UnitTest: title', $post['title']);
        }

        return $posts;
    }

    /**
     * @depends testGetPosts
     */
    public function testDeletePosts(array $posts){
        foreach ($posts as $p){
            $id = $p['ID'];
            $post = $this->api->getPost($id);
            $this->assertStringStartsWith('UnitTest: ', $post['title']);
            $this->assertStringStartsWith('UnitTest: ', $post['content']);
            $post = $this->api->deletePost($id);
            $this->assertEquals('trash', $post['status']);
            $post = $this->api->getPost($id);
            $this->assertEquals('trash', $post['status']);
        }
    }

    private function createPost($title, $content){
        $title = "UnitTest: $title";
        $content = "UnitTest: $content";
        $post = $this->api->addPost([
            'content' => $content,
            'title' => $title,
        ]);

        $this->assertNotNull($post);
        $this->assertNotNull($post['ID']);

        $post = $this->api->getPost($post['ID']);
        $this->assertEquals($title, $post['title']);
        $this->assertEquals($content, $post['content']);
        return $post;
    }
}
