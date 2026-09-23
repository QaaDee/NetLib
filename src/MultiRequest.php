<?php

namespace QaaDee\NetLib;

/**
 * Class MultiRequest
 * @package QaaDee\NetLib
 */
class MultiRequest
{
    /**
     * @var callable
     */
    protected $onRequestEnd;

    /**
     * @var \CurlMultiHandle|resource
     */
    protected $multiCurlHandle;

    /**
     * @var int
     */
    protected $isRunning;

    /**
     * @var array<string, Request>
     */
    protected $runningRequests = [];

    /**
     * @var array<string, \CurlHandle|resource>
     */
    protected $runningCurlResources = [];

    /**
     * @param callable|null $onRequestEnd
     */
    public function __construct(?callable $onRequestEnd = null)
    {
        $this->multiCurlHandle = curl_multi_init();

        $this->setOnRequestEnd($onRequestEnd);
    }

    /**
     * @param callable|null $onRequestEnd
     * @return $this
     */
    public function setOnRequestEnd(?callable $onRequestEnd)
    {
        $this->onRequestEnd = $onRequestEnd;
        return $this;
    }

    /**
     * @param Request $request
     * @return $this
     * @throws RequestException
     */
    public function addRequest(Request $request): self
    {
        $curlResource = $request->createCurlResource();

        $this->runningRequests[spl_object_hash($curlResource)] = $request;
        $this->runningCurlResources[spl_object_hash($curlResource)] = $curlResource;

        curl_multi_add_handle($this->multiCurlHandle, $curlResource);

        return $this;
    }

    /**
     * @return int
     */
    public function getCountRunningRequests()
    {
        return count($this->runningRequests);
    }

    /**
     * @return void
     * @throws RequestException
     */
    protected function recalculate()
    {
        while ($curlResourceInfo = curl_multi_info_read($this->multiCurlHandle, $query)) {
            ['handle' => $curlResource] = $curlResourceInfo;

            $curlResourceHash = spl_object_hash($curlResource);

            $request = $this->runningRequests[$curlResourceHash];
            $response = $request->parseResponse(
                $curlResource,
                $this->multiCurlHandle
            );

            curl_multi_remove_handle($this->multiCurlHandle, $curlResource);
            curl_close($curlResource);
            unset($this->runningRequests[$curlResourceHash], $this->runningCurlResources[$curlResourceHash]);

            if ($this->onRequestEnd) {
                try {
                    call_user_func($this->onRequestEnd, $request, $response);
                } catch (\Throwable $throwable) {
//                    print_r($throwable->__toString());
                }
            }
        }
    }

    /**
     * @param bool $isBlocked
     * @return void
     * @throws RequestException
     */
    public function execute(bool $isBlocked = true)
    {
        do {
            curl_multi_exec($this->multiCurlHandle, $this->isRunning);
            $this->recalculate();
        } while ($this->isRunning && $isBlocked);
    }

    public function __destruct()
    {
        curl_multi_close($this->multiCurlHandle);
    }
}


?>