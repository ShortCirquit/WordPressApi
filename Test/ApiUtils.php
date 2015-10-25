<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-18
 * Time: 8:26 AM
 */

namespace ShortCirquit\WordPressApi\Test;

use ShortCirquit\WordPressApi\ComWpApi;
use ShortCirquit\WordPressApi\OrgWpApi;

/**
 * Class ApiUtils
 *
 * Contains common utilities used by unit tests
 *
 * @package ShortCirquit\WordPressApi\Test
 */
class ApiUtils
{
    private static $file = __DIR__ . '/config.json';

    /**
     * Create ComWpApi instance
     *
     * @return ComWpApi
     */
    public static function getComApi(){
        $cfg = json_decode(file_get_contents(ApiUtils::$file), true);
        return new ComWpApi($cfg['com']);
    }

    /**
     * @return OrgWpApi
     */
    public static function getOrgApi(){
        $cfg = json_decode(file_get_contents(ApiUtils::$file), true);
        return new OrgWpApi($cfg['org']);
    }

    /**
     * Converts a post from the COM API to the PostTestModel
     *
     * @param $p
     * @return PostTestModel
     */
    public static function comToPost($p){
        $post = new PostTestModel();
        $post->id = $p['ID'];
        $post->date = $p['date'];
        $post->title = $p['title'];
        $post->content = $p['content'];
        $post->likes = $p['like_count'];
        $post->hasLiked = $p['i_like'] == 1 ? true : false;
        $post->status = $p['status'];
        return $post;
    }

    /**
     * Converts PostTestMode to the COM API format
     *
     * @param PostTestModel $post
     * @return array
     */
    public static function postToCom(PostTestModel $post){
        $data = [];
        if ($post->id != null) $data['ID'] = $post->id;
        if ($post->date != null) $data['date'] = $post->date;
        if ($post->title != null) $data['title'] = $post->title;
        if ($post->content != null) $data['content'] = $post->content;
        if ($post->status != null) $data['status'] = $post->status;
        return $data;
    }

    /**
     *
     *
     * @param $p
     * @return PostTestModel
     */
    public static function orgToPost($p){
        $post = new PostTestModel();
        $post->id = $p['id'];
        $post->date = $p['date'];
        $post->title = $p['title']['raw'];
        $post->content = $p['content']['raw'];
        $post->status = $p['status'];
        return $post;
    }

    /**
     * @param PostTestModel $post
     * @return array
     */
    public static function postToOrg(PostTestModel $post){
        $data = [];
        if ($post->id != null) $data['id'] = $post->id;
        if ($post->date != null) $data['date'] = $post->date;
        if ($post->title != null) $data['title'] = $post->title;
        if ($post->content != null) $data['content'] = $post->content;
        if ($post->status != null) $data['status'] = $post->status;
        return $data;
    }

    /**
     * @param $title
     * @param $content
     * @return PostTestModel
     */
    public static function makePost($title, $content){
        $p = new PostTestModel();
        $p->title = 'Unit Test Title: ' . $title;
        $p->content = 'Unit Test Content: ' . $content;
        $p->status = 'publish';
        return $p;
    }
}
