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

    public function __construct(array $config)
    {
        $this->clientId = $config['clientId'];
        $this->clientSecret = $config['clientSecret'];
        $this->redirectUrl = $config['redirectUrl'];
        $this->blogId = isset($config['blogId']) ? $config['blogId'] : null;
        $this->blogUrl = isset($config['blogUrl']) ? $config['blogUrl'] : null;
        $this->token = isset($config['accessToken']) ? $config['accessToken'] : null;
        $this->baseUrl = $this->wpBase;
    }

    public function getConfig(){
        return [
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUrl' => $this->redirectUrl,
            'blogId' => $this->blogId,
            'blogUrl' => $this->blogUrl,
        ];
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

    public function listPosts($params = []) {return $this->listItems('posts');}
    public function getPost($id) {return $this->getItem('posts', $id);}
    public function addPost($data) {return $this->newItem('posts', $data);}
    public function updatePost($id, $data) {return $this->updateItem('posts', $id, $data);}
    public function deletePost($id) {return $this->deleteItem('posts', $id);}
    public function likePost($id){return $this->likeItem('posts', $id);}
    public function unlikePost($id){return $this->unlikeItem('posts', $id);}

    public function listComments($postId) {return $this->getReplies($postId);}
    public function getComment($id) {return $this->getItem('comments', $id);}
    public function addComment($id, $data) {return $this->addReply($id, $data);}
    public function updateComment($id, $data) {return $this->updateItem('comments', $id, $data);}
    public function deleteComment($id) {return $this->deleteItem('comments', $id);}
    public function likeComment($id){return $this->likeItem('comments', $id);}
    public function unlikeComment($id){return $this->unlikeItem('comments', $id);}

    public function getSelf() {return $this->get($this->selfUrl);}
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

    private function listItems($type){
        $url = $this->formatUrl($this->listFormat, $type);
        return $this->get($url, ['context' => 'edit']);
    }

    private function getItem($type, $id){
        $url = $this->formatUrl($this->getFormat, $type, $id);
        return $this->get($url, ['context' => 'edit']);
    }

    private function newItem($type, $data){
        $url = $this->formatUrl($this->addFormat, $type);
        return $this->post($url, [], $data);
    }

    private function getReplies($id){
        $url = $this->formatUrl($this->repliesFormat, 'posts', $id);
        return $this->get($url, ['context' => 'edit']);
    }

    private function addReply($id, $data){
        $url = $this->formatUrl($this->newReplyFormat, 'posts', $id);
        return $this->post($url, [], $data);
    }

    private function updateItem($type, $id, $data){
        $url = $this->formatUrl($this->updateFormat, $type, $id);
        return $this->post($url, [], $data);
    }

    private function deleteItem($type, $id){
        $url = $this->formatUrl($this->deleteFormat, $type, $id);
        return $this->post($url);
    }

    private function likeItem($type, $id){
        $url = $this->formatUrl($this->likeFormat, $type, $id);
        return $this->post($url);
    }

    private function unlikeItem($type, $id){
        $url = $this->formatUrl($this->unlikeFormat, $type, $id);
        return $this->post($url);
    }

    private function formatUrl($fmt, $type = '', $id = ''){
        $fmt = str_replace("{blogId}", $this->blogId, $fmt);
        $fmt = str_replace("{type}", $type, $fmt);
        $fmt = str_replace("{id}", $id, $fmt);
        return $fmt;
    }
}
