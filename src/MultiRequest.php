<?php

namespace QaaDee\NetLib;

/**
 * Class MultiRequest
 * @package QaaDee\NetLib
 */
class MultiRequest
{
    /**
     * @var Request[]
     */
    protected $requests;

    /**
     * @var callable
     */
    protected $callbackRequestEnd;

    /**
     * @var resource
     */
    protected $currentMultiCurl;

    /**
     * @var Request[]
     */
    protected $currentRequests = [];

    /**
     * @var resource[]
     */
    protected $currentCurlResources = [];

    /**
     * @var int
     */
    protected $currentMultiCurlRunning = 0;

    /**
     * MultiRequest constructor.
     * @param Request[] $requests
     */
    public function __construct(array $requests = [])
    {
        $this->requests = $requests;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function addRequest(Request $request)
    {
        $this->requests[] = $request;
        return $this;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function removeRequest(Request $request)
    {
        $index = array_search($request, $this->requests);

        if ($index !== false)
            unset($this->requests[$index]);

        return $this;
    }

    /**
     * @return void
     */
    public function clearRequests()
    {
        $this->requests = [];
    }

    /**
     * @param callable|null $callbackRequestEnd(\QaaDee\NetLib\Request $request, \QaaDee\NetLib\Response $response)
     * @return $this
     */
    public function setCallbackRequestEnd(?callable $callbackRequestEnd)
    {
        $this->callbackRequestEnd = $callbackRequestEnd;
        return $this;
    }

    /**
     * @throws MultiRequestException
     */
    protected function preparing()
    {
        $this->currentMultiCurl = curl_multi_init();

        foreach ($this->requests as $request) {
            if (!$request instanceof Request)
                continue;

            $curlResource = $request->createCurlResource();
            $this->currentRequests[(int)$curlResource] = $request;
            $this->currentCurlResources[(int)$curlResource] = $curlResource;

            curl_multi_add_handle($this->currentMultiCurl, $curlResource);
        }

        if (!$this->currentRequests) {
            if ($this->currentMultiCurl)
                curl_multi_close($this->currentMultiCurl);

            throw new MultiRequestException('nothing to do');
        }
    }

    /**
     * @throws RequestException
     */
    protected function recalculate()
    {
        foreach ($this->currentRequests as $curlResourceId => $request) {
            $curlResource = $this->currentCurlResources[$curlResourceId];
            $httpStatusCode = curl_getinfo($curlResource, CURLINFO_HTTP_CODE);

            if ($httpStatusCode < 200)
                continue;

            if ($this->callbackRequestEnd) {
                $response = $request->parseResponse(
                    $curlResource,
                    curl_multi_getcontent($curlResource)
                );

                try {
                    call_user_func($this->callbackRequestEnd, $request, $response);
                } catch (\Throwable $throwable) {

                }
            }

            curl_multi_remove_handle($this->currentMultiCurl, $curlResource);

            unset($this->currentRequests[$curlResourceId], $this->currentCurlResources[$curlResourceId]);
        }
    }

    /**
     * @param bool $notBlock
     * @throws MultiRequestException
     * @throws RequestException
     */
    public function execute($notBlock = false)
    {
        if (!$this->currentMultiCurl)
            $this->preparing();

        do {
            curl_multi_exec($this->currentMultiCurl, $this->currentMultiCurlRunning);

            $this->recalculate();

            if ($notBlock)
                break;
        } while ($this->currentMultiCurlRunning);

        if (!$this->currentCurlResources) {
            curl_multi_close($this->currentMultiCurl);
            $this->currentRequests = $this->currentCurlResources = [];
        }
    }
}


?>