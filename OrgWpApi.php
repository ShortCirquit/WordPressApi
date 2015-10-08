<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-09-11
 * Time: 9:26 AM
 */

namespace ShortCirquit\WordPressApi;

class OrgWpApi extends BaseWpApi
{
    public $type;
    public $consumerKey;
    public $consumerSecret;
    public $accessToken;
    public $accessTokenSecret;

    protected $curlOptions = [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_USERAGENT => 'LinkoScope WP-API OAuth 1.0 Client',
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ];

    private $requestUrl =   '/oauth1/request';
    private $authorizeUrl = '/oauth1/authorize';
    private $accessUrl =    '/oauth1/access';
    private $postUrl =      '/wp-json/wp/v2/posts';
    private $typeUrl =      '/wp-json/wp/v2/types';
    private $selfUrl =      '/wp-json/wp/v2/users/me';
    private $commentsUrl =  '/wp-json/wp/v2/comments';
    private $customBase = '/wp-json/wp/v2/';

    public function __construct(array $config)
    {
        $this->type = $config['type'];
        $this->baseUrl = $config['blogUrl'];
        $this->consumerKey = $config['consumerKey'];
        $this->consumerSecret = $config['consumerSecret'];
        $this->accessToken = isset($config['accessToken']) ? $config['accessToken'] : null;
        $this->accessTokenSecret = isset($config['accessTokenSecret']) ? $config['accessTokenSecret'] : null;
    }

    public function getConfig()
    {
        return [
            'type' => 'org',
            'consumerKey' => $this->consumerKey,
            'consumerSecret' => $this->consumerSecret,
            'blogUrl' => $this->baseUrl,
        ];
    }

    public function getAuthorizeUrl($returnUrl){
        $response = $this->get($this->requestUrl, ['oauth_callback' => $returnUrl]);
        $params = ['oauth_callback' => $returnUrl, 'oauth_token' => $response['oauth_token'],];
        return $this->baseUrl . $this->authorizeUrl . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public function getAccessToken($token, $verifier){
        $defaultParams = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_token' => $token,
            'oauth_verifier' => $verifier,
        ];
        return $this->get($this->accessUrl, $defaultParams);
    }

    public function listCustom($type, $params = []) {return $this->get($this->customBase . $type, $params);}
    public function getCustom($type, $id) {return $this->get($this->customBase . $type . "/$id");}
    public function addCustom($type, $data) {return $this->post($this->customBase . $type, [], $data);}
    public function updateCustom($type, $id, $data) {return $this->put($this->customBase . $type . "/$id", [], $data);}
    public function deleteCustom($type, $id) {return $this->delete($this->customBase . $type . "/$id");}

    public function listPosts($params = []) {return $this->get($this->postUrl, $params);}
    public function getPost($id) {return $this->get($this->postUrl . "/$id");}
    public function addPost($data) {return $this->post($this->postUrl, [], $data);}
    public function updatePost($id, $data) {return $this->put($this->postUrl . "/$id", [], $data);}
    public function deletePost($id) {return $this->delete($this->postUrl . "/$id");}

    public function listComments($postId, $params = []) {return $this->get($this->commentsUrl, ['post' => $postId] + $params);}
    public function getComment($id) {return $this->get($this->commentsUrl . "/$id");}
    public function addComment($data) {return $this->post($this->commentsUrl, [], $data);}
    public function updateComment($id, $data) {return $this->put($this->commentsUrl . "/$id", [], $data);}
    public function deleteComment($id) {return $this->delete($this->commentsUrl . "/$id");}

    public function getSelf() {return $this->get($this->selfUrl, ['_envelope' => 1]);}
    public function listTypes() {return $this->get($this->typeUrl);}

    protected function requestFilter(ApiRequest $req)
    {
        $req->params = $this->signRequest($req->method, $req->url, $req->params);
        $req->headers[] = 'Content-type: application/json';
        $req->headers[] = $this->composeAuthorizationHeader($req->params);
        if ($req->body != null)
            $req->body = json_encode($req->body);
        return $req;
    }

    private function composeAuthorizationHeader(array $params)
    {
        $header = 'Authorization: OAuth';
        $headerParams = [];

        foreach ($params as $key => $value) {
            if (substr_compare($key, 'oauth', 0, 5)) {
                continue;
            }
            $headerParams[] = rawurlencode($key) . '="' . rawurlencode($value) . '"';
        }
        if (!empty($headerParams)) {
            $header .= ' ' . implode(', ', $headerParams);
        }

        return $header;
    }

    private function signRequest($method, $url, array $params)
    {
        $params = array_merge($params, [
            'oauth_version' => '1.0',
            'oauth_nonce' => md5(microtime() . mt_rand()),
            'oauth_timestamp' => time(),
            'oauth_consumer_key' => $this->consumerKey,
        ]);
        if ($this->accessToken != null)
            $params['oauth_token'] = $this->accessToken;
        $params['oauth_signature_method'] = 'HMAC-SHA1';
        $signatureBaseString = $this->composeSignatureBaseString($method, $this->baseUrl . $url, $params);
        $signatureKey = $this->composeSignatureKey();
        $params['oauth_signature'] = base64_encode(hash_hmac('sha1', $signatureBaseString, $signatureKey, true));

        return $params;
    }

    private function composeSignatureBaseString($method, $url, array $params)
    {
        unset($params['oauth_signature']);
        uksort($params, 'strcmp'); // Parameters are sorted by name, using lexicographical byte value ordering. Ref: Spec: 9.1.1
        $parts = [
            strtoupper($method),
            $url,
            http_build_query($params, '', '&', PHP_QUERY_RFC3986)
        ];
        $parts[2] = str_replace('%5B', '[', $parts[2]);
        $parts[2] = str_replace('%5D', ']', $parts[2]);
        $parts = array_map('rawurlencode', $parts);

        return implode('&', $parts);
    }

    private function composeSignatureKey()
    {
        $signatureKeyParts = [
            $this->consumerSecret
        ];

        $signatureKeyParts[] = $this->accessTokenSecret ?: '';
        $signatureKeyParts = array_map('rawurlencode', $signatureKeyParts);

        return implode('&', $signatureKeyParts);
    }
}

