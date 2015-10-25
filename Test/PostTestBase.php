<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-17
 * Time: 5:58 PM
 */

namespace ShortCirquit\WordPressApi\Test;


abstract class PostTestBase extends \PHPUnit_Framework_TestCase
{
    public function testCreatePosts()
    {
        $p1 = $this->createPost(ApiUtils::makePost('title1', 'content1'));
        $p2 = $this->createPost(ApiUtils::makePost('title2', 'content2'));
        $this->assertNotEquals($p1->id, $p2->id, 'Two created posts should not have the same ID');

        return [$p1, $p2];
    }

    /**
     * @depends testCreatePosts
     */
    public function testListPosts($input)
    {
        $posts = $this->listPosts();
        $this->assertGreaterThanOrEqual(
            count($input), count($posts), 'listing posts should at least return the number of posts we added'
        );

        $postMap = [];
        foreach ($posts as $p)
        {
            $postMap[$p->id] = $p;
        }

        foreach ($input as $p)
        {
            $this->assertArrayHasKey($p->id, $postMap);

            /**
             * @var PostTestModel $post
             */
            $post = $postMap[$p->id];
            $this->assertEquals($p->title, $post->title);
            $this->assertEquals($p->content, $post->content);
            $this->assertEquals(0, $post->likes);
            $this->assertEquals(false, $post->hasLiked);
        }

        return $input;
    }

    /**
     * @depends testListPosts
     */
    public function testGetPost($input)
    {
        foreach ($input as $p)
        {
            /**
             * @var PostTestModel $post
             */
            $post = $this->getPost($p->id);
            $this->assertEquals($p->title, $post->title);
            $this->assertEquals($p->content, $post->content);
            $this->assertEquals(0, $post->likes);
            $this->assertEquals(false, $post->hasLiked);
        }

        return $input;
    }

    /**
     * @depends testGetPost
     */
    public function testUpdatePost($input)
    {
        foreach ($input as $p)
        {
            $post = $this->getPost($p->id);
            $this->assertEquals($p->title, $post->title);
            $this->assertEquals($p->content, $post->content);

            $p->title .= "test test";
            $p->content .= "test test";
            $post->title = $p->title;
            $post->content = $p->content;

            $post = $this->updatePost($post);
            $this->assertEquals($p->title, $post->title);
            $this->assertEquals($p->content, $post->content);

            $post = $this->getPost($p->id);
            $this->assertEquals($p->title, $post->title);
            $this->assertEquals($p->content, $post->content);
        }

        return $input;
    }

    /**
     * @depends testUpdatePost
     */
    public function testDeletePosts($input)
    {
        foreach ($input as $p)
        {
            /**
             * @var PostTestModel $post
             */
            $post = $this->deletePost($p->id);
            $this->assertEquals($p->title, $post->title);
            $this->assertEquals($p->content, $post->content);

            $post = $this->getPost($p->id);
            $this->assertEquals('trash', $post->status);
        }

        return $input;
    }

    /**
     * @param PostTestModel $post
     * @return PostTestModel
     */
    protected abstract function createPost(PostTestModel $post);

    /**
     * @return PostTestModel[]
     */
    protected abstract function listPosts();

    /**
     * @param $id
     * @return PostTestModel
     */
    protected abstract function getPost($id);

    /**
     * @param PostTestModel $post
     * @return PostTestModel
     */
    protected abstract function updatePost(PostTestModel $post);

    /**
     * @param $id
     * @return PostTestModel
     */
    protected abstract function deletePost($id);
}
