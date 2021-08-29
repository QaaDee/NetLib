<?php

namespace QaaDee\NetLib;

use QaaDee\NetLib\RequestException;
use QaaDee\NetLib\Response;

/**
 * Class Request
 * @package QaaDee\NetLib
 */
class Request
{
    const NOT_CUSTOM_METHOD = [
        'GET', 'POST'
    ];

    const DEFAULT_OPTIONS = [
        CURLOPT_FOLLOWLOCATION => true
    ];

    const REQUIRED_OPTIONS = [
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLINFO_HEADER_OUT => true
    ];

    /**
     * @var string
     */
    protected $method = 'GET';

    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $get;

    /**
     * @var array|string
     */
    protected $post;

    /**
     * @var array
     */
    protected $options;

    /**
     * Request constructor.
     * @param string $method
     * @param string $url
     * @param array $get
     * @param string $post
     * @param array $options
     */
    public function __construct($method = '', $url = '', array $get = [], $post = '', array $options = [])
    {
        $this->method = $method;
        $this->url = $url;
        $this->get = $get;
        $this->post = $post;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return array
     */
    public function getGet(): array
    {
        return $this->get;
    }

    /**
     * @param array $get
     * @return $this
     */
    public function setGet(array $get)
    {
        $this->get = $get;
        return $this;
    }

    /**
     * @return array|string
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * @param array|string $post
     * @return $this
     */
    public function setPost($post)
    {
        $this->post = $post;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return false|resource
     * @throws RequestException
     */
    public function createCurlResource()
    {
        $url = $this->url;
        $post = $this->post;
        $options = self::DEFAULT_OPTIONS + $this->options;

        if (!$url)
            throw new RequestException('nothing to do');

        if ($this->get) {
            if (strpos('?', $url) === false)
                $url .= '?';
            elseif ($url[-1] !== '&')
                $url .= '&';

            $url .= http_build_query($this->get);
        }

        if ($post)
            $options += [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => is_array($post) ? http_build_query($post) : $post
            ];

        if (!in_array($this->method, self::NOT_CUSTOM_METHOD))
            $options[CURLOPT_CUSTOMREQUEST] = $this->method;

        $curlResource = curl_init($url);
        curl_setopt_array($curlResource, $options + self::REQUIRED_OPTIONS);

        return $curlResource;
    }

    /**
     * @param resource $curlResource
     * @param string $rawResponse
     * @return Response
     * @throws RequestException
     */
    public function parseResponse($curlResource, $rawResponse): Response
    {
        $curlError = curl_error($curlResource);
        $curlErrno = curl_errno($curlResource);

        if ($curlError || $curlErrno)
            throw new RequestException($curlError ?? 'Request error', $curlErrno);

        $requestHeader = curl_getinfo($curlResource, CURLINFO_HEADER_OUT);
        $responseHeader = $responseBody = null;

        if (strpos($rawResponse, "\r\n\r\n") !== false) {
            $headerSize = curl_getinfo($curlResource, CURLINFO_HEADER_SIZE);
            $responseHeader = substr($rawResponse, 0, $headerSize);
            $responseBody = substr($rawResponse, $headerSize);

            if ($responseBody[-1] === "\n")
                $responseBody = substr($responseBody, 0, -1);
        } else
            $responseBody = $rawResponse;

        return new Response($requestHeader, '', $responseHeader, $responseBody, curl_getinfo($curlResource));
    }

    /**
     * @return Response
     * @throws RequestException
     */
    public function execute()
    {
        $curlResource = $this->createCurlResource();
        $response = $this->parseResponse($curlResource, curl_exec($curlResource));
        curl_close($curlResource);

        return $response;
    }
}


?>