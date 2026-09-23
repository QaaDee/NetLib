<?php

namespace QaaDee\NetLib;

/**
 * Class Response
 * @package QaaDee\NetLib
 */
class Response
{
    const FORMAT_AUTO = 'auto';
    const FORMAT_JSON = 'json';
    const FORMAT_XML = 'xml';
    const FORMAT_TXT = 'txt';

    const CONTENT_TYPE2FORMAT = [
        'application/json' => self::FORMAT_JSON,
        'application/xml' => self::FORMAT_XML,
        'application/xhtml+xml' => self::FORMAT_XML,
        'text/xml' => self::FORMAT_XML
    ];

    /**
     * @var string
     */
    protected $rawRequestHeader, $rawRequestBody, $rawResponseHeader, $rawResponseBody;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var string
     */
    protected $contentType = null;

    /**
     * @var array
     */
    protected $rawCurlInfo;

    /**
     * @var string
     */
    protected $error;

    /**
     * @var integer
     */
    protected $errno;

    /**
     * Response constructor.
     *
     * @param $requestHeader
     * @param $requestBody
     * @param $responseHeader
     * @param $responseBody
     * @param array $curlInfo
     */
    public function __construct($requestHeader, $requestBody, $responseHeader, $responseBody, array $curlInfo = [], $error = null, $errno = null)
    {
        $this->rawRequestHeader = $requestHeader;
        $this->rawRequestBody = $requestBody;
        $this->rawResponseHeader = $responseHeader;
        $this->rawResponseBody = $responseBody;
        $this->rawCurlInfo = $curlInfo;

        $this->error = $error;
        $this->errno = $errno;

        $this->parseHeaders();
    }

    /**
     *
     */
    protected function parseHeaders()
    {
        $rawHeaderLines = array_diff(
            explode(PHP_EOL, $this->rawResponseHeader),
            [null]
        );
        unset($rawHeaderLines[0]);

        foreach ($rawHeaderLines as $rawHeaderLine) {
            $item = explode(': ', $rawHeaderLine, 2);

            if (count($item) !== 2)
                continue;

            list($name, $value) = $item;

            $name = mb_strtolower($name);

            if ($name === 'content-type')
                $this->contentType = current(explode('; ', $value));

            $this->headers[$name] = $value;
        }
    }

    /**
     * @return string
     */
    public function getRawRequestHeader(): string
    {
        return $this->rawRequestHeader;
    }

    /**
     * @return string
     */
    public function getRawRequestBody(): string
    {
        return $this->rawRequestBody;
    }

    /**
     * @return string
     */
    public function getRawResponseHeader(): string
    {
        return $this->rawResponseHeader;
    }

    /**
     * @return string
     */
    public function getRawResponseBody(): string
    {
        return $this->rawResponseBody;
    }

    /**
     * @return array
     */
    public function getRawCurlInfo(): array
    {
        return $this->rawCurlInfo;
    }

    /**
     * @return array [
     *      "name" => "value"
     * ]
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    public function getError(): mixed
    {
        return $this->error;
    }

    public function getErrno(): mixed
    {
        return $this->errno;
    }

    /**
     * @param string $format
     * @return \SimpleXMLElement|string|\stdClass
     */
    public function getFormatResponse($format = self::FORMAT_AUTO)
    {
        if ($format === self::FORMAT_AUTO) {
            if (isset(self::CONTENT_TYPE2FORMAT[$this->contentType]))
                $format = self::CONTENT_TYPE2FORMAT[$this->contentType];
        }

        $result = $this->rawResponseBody;

        if ($format === self::FORMAT_JSON)
            $result = json_decode($result);
        elseif ($format === self::FORMAT_XML)
            $result = simplexml_load_string($result);

        return $result;
    }
}


?>