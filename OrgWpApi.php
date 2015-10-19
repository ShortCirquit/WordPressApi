<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-09-11
 * Time: 9:26 AM
 */

namespace ShortCirquit\WordPressApi;

use yii\log\Logger;

class OrgWpApi extends BaseWpApi
{
    public $blogUrl;
    public $consumerKey;
    public $consumerSecret;
    public $token;
    public $tokenSecret;

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

    public function __construct(array $config = [])
    {
        $vars = ['blogUrl', 'consumerKey', 'consumerSecret', 'token', 'tokenSecret'];
        foreach ($config as $k => $v){
            if (in_array($k, $vars)){
                $this->$k = $v;
            }
        }
        $this->baseUrl = $this->blogUrl;
    }

    public function getConfig()
    {
        return [
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
    public function getCustom($type, $id, $params = []) {return $this->get($this->customBase . $type . "/$id", $params);}
    public function addCustom($type, $data, $params = []) {return $this->post($this->customBase . $type, $params, $data);}
    public function updateCustom($type, $id, $data, $params = []) {return $this->put($this->customBase . $type . "/$id", $params, $data);}
    public function deleteCustom($type, $id, $params = []) {return $this->delete($this->customBase . $type . "/$id", $params);}

    public function listPosts($params = []) {return $this->get($this->postUrl, $params);}
    public function getPost($id, $params = []) {return $this->get($this->postUrl . "/$id", $params);}
    public function addPost($data, $params = []) {return $this->post($this->postUrl, $params, $data);}
    public function updatePost($id, $data, $params = []) {return $this->put($this->postUrl . "/$id", $params, $data);}
    public function deletePost($id, $params = []) {return $this->delete($this->postUrl . "/$id", $params);}

    public function listComments($postId, $params = []) {return $this->get($this->commentsUrl, ['post' => $postId] + $params);}
    public function getComment($id, $params = []) {return $this->get($this->commentsUrl . "/$id", $params);}
    public function addComment($data, $params = []) {return $this->post($this->commentsUrl, $params, $data);}
    public function updateComment($id, $data, $params = []) {return $this->put($this->commentsUrl . "/$id", $params, $data);}
    public function deleteComment($id, $params = []) {return $this->delete($this->commentsUrl . "/$id", $params);}

    public function getSelf() {return $this->get($this->selfUrl, ['_envelope' => 1]);}
    public function listTypes() {return $this->get($this->typeUrl);}

    protected function requestFilter(ApiRequest $req)
    {
        \Yii::getLogger()->log('access token: ' . $this->token, Logger::LEVEL_INFO);
        if ($this->token != null || isset($req->params['oauth_token']) || isset($req->params['oauth_callback']))
        {
            $req->params = $this->signRequest($req->method, $req->url, $req->params);
            $req->headers[] = $this->composeAuthorizationHeader($req->params);
        }
        $req->headers[] = 'Content-type: application/json';
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
        if ($this->token != null)
            $params['oauth_token'] = $this->token;
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

        $signatureKeyParts[] = $this->tokenSecret ?: '';
        $signatureKeyParts = array_map('rawurlencode', $signatureKeyParts);

        return implode('&', $signatureKeyParts);
    }
}

