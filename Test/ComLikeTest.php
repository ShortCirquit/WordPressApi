<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-18
 * Time: 8:25 AM
 */

namespace ShortCirquit\WordPressApi\Test;

use ShortCirquit\WordPressApi\ComWpApi;

class ComLikeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ComWpApi
     */
    protected $api;

    public function setUp(){
        $this->api = ApiUtils::getComApi();
    }

    public function testLikePost()
    {
        $post = ApiUtils::makePost('Like test', 'like test');
        $p = ApiUtils::postToCom($post);
        $p = $this->api->addPost($p);
        $post = ApiUtils::comToPost($p);
        $id = $post->id;

        $this->assertEquals(0, $post->likes);
        $this->assertFalse($post->hasLiked);

        $this->likePost($id);
        $post = $this->getPost($id);
        $this->assertEquals(1, $post->likes);
        $this->assertTrue($post->hasLiked);

        $this->unlikePost($id);
        $post = $this->getPost($id);
        $this->assertEquals(0, $post->likes);
        $this->assertFalse($post->hasLiked);

        $this->api->deletePost($id);
    }

    private function getPost($id){
        return ApiUtils::comToPost($this->api->getPost($id));
    }

    private function likePost($id){
        $p = $this->api->likePost($id);
        $this->assertArrayHasKey('like_count', $p);
        $this->assertArrayHasKey('i_like', $p);
        return $p['like_count'];
    }

    private function unlikePost($id){
        $p = $this->api->unlikePost($id);
        $this->assertArrayHasKey('like_count', $p);
        $this->assertArrayHasKey('i_like', $p);
        return $p['like_count'];
    }
}
