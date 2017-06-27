<?php
namespace Dcp\HttpApi\V1\Api;

class HttpResponse
{
    
    protected $headers = [];
    protected $body;
    protected $response = null;
    protected $httpStatusHeader;
    /**
     * @var RecordReturnMessage[]
     */
    protected $messages = [];
    protected $stopResponse = false;
    /**
     * Get list of recorded messages
     * @return RecordReturnMessage[]
     */
    public function getMessages()
    {
        return $this->messages;
    }
    /**
     * Add a new message
     *
     * @param RecordReturnMessage $message
     *
     * @return $this
     */
    public function addMessage(RecordReturnMessage $message)
    {
        $this->messages[] = $message;
        return $this;
    }
    /**
     * Get status header
     * @return string
     */
    public function getStatusHeader()
    {
        return $this->httpStatusHeader;
    }
    /**
     * Modify status header (default is "200 OK")
     *
     * @param string $httpStatusHeader
     *
     * @return $this
     */
    public function setStatusHeader($httpStatusHeader)
    {
        $this->httpStatusHeader = $httpStatusHeader;
        return $this;
    }
    /**
     * Send headers to http request
     * @return void
     */
    public function sendHeaders()
    {
        foreach ($this->headers as $h) {
            if (isset($h[1])) {
                header(sprintf("%s: %s", $h[0], $h[1]) , false);
            } else {
                header_remove($h[0]);
            }
        }
    }
    /**
     * Return list of headers to send
     * @return array indexed array (key/value)
     */
    public function getHeaders()
    {
        $headers = [];
        foreach ($this->headers as $h) {
            if (isset($h[1])) {
                $headers[$h[0]] = $h[1];
            }
        }
        return $headers;
    }
    /**
     * Memorize new header to be send to the response
     * Header are sended when the last middleware has been executed
     *
     * @param string $key     header key
     * @param string $value   value of key, if null, header will be removed when send it
     * @param bool   $replace if true replace header with same key
     *
     * @return $this
     * @throws Exception
     */
    public function addHeader($key, $value, $replace = true)
    {
        if (!$key) {
            throw new Exception("API0108");
        }
        if ($replace === true) {
            foreach ($this->headers as $k => $v) {
                if ($k === $key) {
                    unset($this->headers[$k]);
                }
            }
        }
        $this->headers[] = [$key, $value];
        
        return $this;
    }
    /**
     * Return recorded data
     * @return \JsonSerializable|string
     */
    public function getBody()
    {
        return $this->body;
    }
    /**
     * Affect data response
     *
     * @param \JsonSerializable|string $body
     *
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }
    /**
     * Return recorded response
     * @return \JsonSerializable|string|array
     */
    public function getResponse()
    {
        return $this->response;
    }
    /**
     * Affect request response
     * Overhide body response, force a complete custom response
     *
     * @param \JsonSerializable|string|array $response
     *
     * @return $this
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }
    /**
     * Prevent process for next middleware
     * Current response will be sent
     * @return $this
     */
    public function sendResponse()
    {
        $this->stopResponse = true;
        return $this;
    }
    /**
     * If true indicated that next middleware processes will be ignored
     * @return bool
     */
    public function responseIsStopped()
    {
        return $this->stopResponse;
    }
}
