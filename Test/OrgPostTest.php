<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-18
 * Time: 10:13 AM
 */

namespace ShortCirquit\WordPressApi\Test;

use ShortCirquit\WordPressApi\OrgWpApi;

class OrgPostTest extends PostTestBase
{
    /**
     * @var OrgWpApi
     */
    protected $api;

    public function setUp(){
        $this->api = ApiUtils::getOrgApi();
    }

    public function testConnection()
    {
        $this->assertNotNull($this->api->getSelf(), 'Failed to get use profile.');
    }

    protected function createPost(PostTestModel $post){
        $p = $this->api->addPost(ApiUtils::postToOrg($post));
        return ApiUtils::orgToPost($p);
    }

    protected function listPosts(){
        $posts = $this->api->listPosts(['context' => 'edit']);
        return array_map(function($p){return ApiUtils::orgToPost($p);}, $posts);
    }

    protected function getPost($id){
        $p = $this->api->getPost($id, ['context' => 'edit']);
        return ApiUtils::orgToPost($p);
    }

    protected function updatePost(PostTestModel $post){
        $p = $this->api->updatePost($post->id, ApiUtils::postToOrg($post));
        return ApiUtils::orgToPost($p);
    }

    protected function deletePost($id){
        $p = $this->api->deletePost($id);
        $this->assertEquals('publish', $p['status']);
        return ApiUtils::orgToPost($p);
    }
}
