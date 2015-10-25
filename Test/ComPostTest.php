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
class ComPostTest extends PostTestBase
{
    /**
     * @var ComWpApi
     */
    protected $api;

    public function setUp()
    {
        $this->api = ApiUtils::getComApi();
    }

    public function testConnection()
    {
        $this->assertNotNull($this->api->getAuthorizeUrl(), 'Authorize URL not created.');
    }

    protected function createPost(PostTestModel $post)
    {
        $p = $this->api->addPost(ApiUtils::postToCom($post), ['context' => 'edit']);

        return ApiUtils::comToPost($p);
    }

    protected function listPosts()
    {
        $posts = $this->api->listPosts(['context' => 'edit']);
        $this->assertArrayHasKey('found', $posts);
        $this->assertArrayHasKey('posts', $posts);

        return array_map(function ($p) { return ApiUtils::comToPost($p); }, $posts['posts']);
    }

    protected function getPost($id)
    {
        return ApiUtils::comToPost($this->api->getPost($id, ['context' => 'edit']));
    }

    protected function updatePost(PostTestModel $post)
    {
        $p = $this->api->updatePost($post->id, ApiUtils::postToCom($post), ['context' => 'edit']);

        return ApiUtils::comToPost($p);
    }

    protected function deletePost($id)
    {
        $p = $this->api->deletePost($id, ['context' => 'edit']);
        $this->assertEquals('trash', $p['status']);

        return ApiUtils::comToPost($p);
    }
}
