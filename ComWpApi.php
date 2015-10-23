<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-09-11
 * Time: 9:26 AM
 */

namespace ShortCirquit\WordPressApi;

class ComWpApi extends BaseWpApi {
    public $clientId;
    public $clientSecret;
    public $redirectUrl;
    public $blogId;
    public $blogUrl;
    public $token;

    public $curlOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'LinkoScope WordPress.com Client',
        CURLOPT_SSL_VERIFYPEER => false,
    ];

    private $requestUrl = '/oauth2/token';
    private $authorizeUrl = '/oauth2/authorize';
    private $wpBase = 'https://public-api.wordpress.com';
    private $selfUrl = '/rest/v1.1/me';

    private $listFormat       = '/rest/v1.1/sites/{blogId}/{type}';
    private $getFormat        = '/rest/v1.1/sites/{blogId}/{type}/{id}';
    private $addFormat        = '/rest/v1.1/sites/{blogId}/{type}/new';
    private $updateFormat     = '/rest/v1.1/sites/{blogId}/{type}/{id}';
    private $deleteFormat     = '/rest/v1.1/sites/{blogId}/{type}/{id}/delete';
    private $likeFormat       = '/rest/v1.1/sites/{blogId}/{type}/{id}/likes/new';
    private $unlikeFormat     = '/rest/v1.1/sites/{blogId}/{type}/{id}/likes/mine/delete';
    private $repliesFormat   = '/rest/v1.1/sites/{blogId}/{type}/{id}/replies/';
    private $newReplyFormat = '/rest/v1.1/sites/{blogId}/{type}/{id}/replies/new';

    public function __construct(array $config = [])
    {
        $vars = ['clientId', 'clientSecret', 'redirectUrl', 'blogId', 'blogUrl', 'token'];
        foreach ($config as $k => $v){
            if (in_array($k, $vars)){
                $this->$k = $v;
            }
        }
        $this->baseUrl = $this->wpBase;
    }

    public function getAuthorizeUrl(){
        $url = $this->wpBase . $this->authorizeUrl;
        $url .= "?client_id=$this->clientId&redirect_uri=$this->redirectUrl&response_type=code";
        $url .= $this->blogId !== null ? "&blog=$this->blogId" : '';
        return $url;
    }

    public function getToken($code)
    {
        return $this->post($this->requestUrl, [], array(
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ));
    }

    public function listPosts($params = []) {return $this->listItems('posts', $params);}
    public function getPost($id, $params = []) {return $this->getItem('posts', $id, $params);}
    public function addPost($data, $params = []) {return $this->newItem('posts', $data, $params);}
    public function updatePost($id, $data, $params = []) {return $this->updateItem('posts', $id, $data, $params);}
    public function deletePost($id, $params = []) {return $this->deleteItem('posts', $id, $params);}
    public function likePost($id, $params = []){return $this->likeItem('posts', $id, $params);}
    public function unlikePost($id, $params = []){return $this->unlikeItem('posts', $id, $params);}

    public function listComments($postId, $params = []) {return $this->getReplies($postId, $params);}
    public function getComment($id, $params = []) {return $this->getItem('comments', $id, $params);}
    public function addComment($id, $data, $params = []) {return $this->addReply($id, $data, $params);}
    public function updateComment($id, $data, $params = []) {return $this->updateItem('comments', $id, $data, $params);}
    public function deleteComment($id, $params = []) {return $this->deleteItem('comments', $id, $params);}
    public function likeComment($id, $params = []){return $this->likeItem('comments', $id, $params);}
    public function unlikeComment($id, $params = []){return $this->unlikeItem('comments', $id, $params);}

    public function getSelf() {return $this->get($this->selfUrl);}
    public function getUsers($params = []){return $this->listItems('users', $params);}
    public function getUser($id, $params = []) {return $this->getItem('users', $id, $params);}
    public function listTypes() {return [];}

    protected function requestFilter(ApiRequest $request) {
        $request->headers[] = "Authorization: Bearer $this->token";

        if ($request->body != null)
        {
            if (preg_match('/token$/', $request->url) == 0)
            {
                $request->headers[] = 'Content-type: application/json';
                $request->body = json_encode($request->body);
            }
        }
        return $request;
    }

    private function listItems($type, $params = []){
        $url = $this->formatUrl($this->listFormat, $type);
        return $this->get($url, $params);
    }

    private function getItem($type, $id, $params = []){
        $url = $this->formatUrl($this->getFormat, $type, $id);
        return $this->get($url, $params);
    }

    private function newItem($type, $data, $params = []){
        $url = $this->formatUrl($this->addFormat, $type);
        return $this->post($url, $params, $data);
    }

    private function getReplies($id, $params = []){
        $url = $this->formatUrl($this->repliesFormat, 'posts', $id);
        return $this->get($url, $params);
    }

    private function addReply($id, $data, $params = []){
        $url = $this->formatUrl($this->newReplyFormat, 'posts', $id);
        return $this->post($url, $params, $data);
    }

    private function updateItem($type, $id, $data, $params = []){
        $url = $this->formatUrl($this->updateFormat, $type, $id);
        return $this->post($url, $params, $data);
    }

    private function deleteItem($type, $id, $params = []){
        $url = $this->formatUrl($this->deleteFormat, $type, $id);
        return $this->post($url, $params);
    }

    private function likeItem($type, $id, $params = []){
        $url = $this->formatUrl($this->likeFormat, $type, $id);
        return $this->post($url, $params);
    }

    private function unlikeItem($type, $id, $params = []){
        $url = $this->formatUrl($this->unlikeFormat, $type, $id);
        return $this->post($url, $params);
    }

    private function formatUrl($fmt, $type = '', $id = ''){
        $fmt = str_replace("{blogId}", $this->blogId, $fmt);
        $fmt = str_replace("{type}", $type, $fmt);
        $fmt = str_replace("{id}", $id, $fmt);
        return $fmt;
    }
}
