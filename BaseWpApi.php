<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-09-28
 * Time: 10:23 AM
 */

namespace ShortCirquit\WordPressApi;

class BaseWpApi
{
    public $baseUrl;
    protected $curlOptions;

    protected function get($url, $params = []){
        return $this->send(new ApiRequest([
            'method' => 'GET',
            'url' => $url,
            'params' => $params,
        ]));
    }

    protected function delete($url, $params = []){
        return $this->send(new ApiRequest([
            'method' => 'DELETE',
            'url' => $url,
            'params' => $params,
        ]));
    }

    protected function post($url, $params = [], $body = null){
        return $this->send(new ApiRequest([
            'method' => 'POST',
            'url' => $url,
            'params' => $params,
            'body' => $body,
        ]));
    }

    protected function put($url, $params = [], $body = null){
        return $this->send(new ApiRequest([
            'method' => 'PUT',
            'url' => $url,
            'params' => $params,
            'body' => $body,
        ]));
    }

    protected function requestFilter(ApiRequest $request)
    {
        return $request;
    }

    protected function send(ApiRequest $request)
    {
        $request = $this->requestFilter($request);
        $url = $this->composeUrl($request->url, $request->params);
        $curlResource = curl_init($url);
        $curlOptions = $this->curlOptions;

        if ($request->body != null)
        {
            $curlOptions[CURLOPT_POST] = true;
            $curlOptions[CURLOPT_POSTFIELDS] = $request->body;
        }

        foreach ($request->curlOptions as $option => $value){
            $curlOptions[$option] = $value;
        }

        foreach ($curlOptions as $option => $value) {
            curl_setopt($curlResource, $option, $value);
        }

        curl_setopt($curlResource, CURLOPT_CUSTOMREQUEST, $request->method);
        curl_setopt($curlResource, CURLOPT_HTTPHEADER, $request->headers);

        $response = curl_exec($curlResource);
        $responseHeaders = curl_getinfo($curlResource);
        $errorNumber = curl_errno($curlResource);
        $errorMessage = curl_error($curlResource);

        curl_close($curlResource);

        if ($errorNumber > 0) {
            $message = 'Curl error requesting "' .
                $url . '": #' . $errorNumber .
                ' - ' . $errorMessage;
            throw new WpApiException(500, $message);
        }

        if (strncmp($responseHeaders['http_code'], '20', 2) !== 0) {
            throw new WpApiException($responseHeaders['http_code'], $responseHeaders['http_code'] . ': ' . $response);
        }

        $result = json_decode($response, true);
        if ($result == null)
            parse_str($response, $result);
        return $result;
    }

    private function composeUrl($url, $params = null)
    {
        $result = $this->baseUrl . $url;
        if (!empty($params)){
            $result .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }
        return $result;
    }
}