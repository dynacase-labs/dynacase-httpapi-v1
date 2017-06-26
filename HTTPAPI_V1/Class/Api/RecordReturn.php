<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Api;

class RecordReturn implements \JsonSerializable
{
    
    protected $httpStatus = 200;
    protected $httpMessage = "OK";
    protected $returnMode = "json";
    /**
     * @var bool indicate if method succeed
     */
    public $success = true;
    /**
     * @var RecordReturnMessage[] message list
     */
    public $messages = array();
    /**
     * @var \stdClass|string misc data
     */
    public $data = null;
    /**
     * @var \stdClass|string misc custom response
     */
    public $response = null;
    /**
     * @var string system message
     */
    public $exceptionMessage = '';
    /**
     * http headers
     */
    public $headers = array();
    
    protected $httpStatusHeader = "200 OK";
    /**
     * Set the http status code
     *
     * @param int $code HTTP status code like (200, 404, ...)
     * @param string $message simple text message
     */
    public function setHttpStatusCode($code, $message)
    {
        $this->httpMessage = $message;
        $this->httpStatus = (int)$code;
        $this->httpStatusHeader = sprintf("%d %s", $code, $message);
    }
    /**
     * @param string $mode
     */
    public function setReturnMode($mode)
    {
        $this->returnMode = $mode;
    }
    public function setHttpStatusHeader($statusHeader)
    {
        $this->httpStatusHeader = $statusHeader;
    }
    public function setResponse($response)
    {
        $this->response = $response;
    }
    /**
     * Add new message to return structure
     *
     * @param RecordReturnMessage $message
     */
    public function addMessage(RecordReturnMessage $message)
    {
        $this->messages[] = $message;
    }
    /**
     * Add data to return structure
     *
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
    /**
     * Add http headers
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }
    
    public function send()
    {
        switch ($this->returnMode) {
            case "":
            case "json":
                $this->sendJson();
                break;

            case "html":
                $this->sendHtml();
                break;

            default:
                $this->sendRawData();
        }
    }
    /**
     * Send the message
     */
    protected function sendJson()
    {
        header(sprintf('HTTP/1.1 %s', str_replace(array(
            "\n",
            "\r"
        ) , "", $this->httpStatusHeader)));
        $needHtmlResponse = (isset($_GET["alt"]) && $_GET["alt"] === "html");
        
        if ($needHtmlResponse) {
            header('Content-Type: text/html');
        } else {
            header('Content-Type: application/json');
        }
        
        foreach ($this->headers as $key => $currentHeader) {
            header(sprintf("%s: %s", $key, $currentHeader));
        }
        
        if ($needHtmlResponse) {
            printf("<html><body><textarea>%s</textarea></body></html>", htmlspecialchars(json_encode($this)));
        } else {
            print json_encode($this);
        }
    }
    /**
     * Print data directly
     */
    protected function sendHtml()
    {
        $hasError = false;
        header(sprintf('HTTP/1.1 %s', str_replace(array(
            "\n",
            "\r"
        ) , "", $this->httpStatusHeader)));
        
        header('Content-Type: text/html');
        
        foreach ($this->headers as $key => $currentHeader) {
            header(sprintf("%s: %s", $key, $currentHeader));
        }
        
        if ($this->messages) {
            foreach ($this->messages as $message) {
                if ($message->type === $message::ERROR) {
                    if ($hasError === false) {
                        $hasError = true;
                        print "<!DOCTYPE html>\n";
                        print <<< 'HTML'
                    <html><head><title>ERROR</title>
                         <style>
                         html, body {height:100%;} 
                         body {display: flex;  margin: 0;   align-items: center; justify-content: center;background-color:#fafafa;} 
                         .error {color:#a94442;border:solid 0.5em #FB657D;margin:1em;padding:1em}                      
                         </style></head><body>
HTML;
                        
                        
                    }
                    print '<div class="error">';
                    print htmlspecialchars($message->contentText);
                    print $message->contentHtml;
                    print "</div>\n";
                }
            }
            if ($hasError === true) {
                print "</body></html>";
            }
        }
        if ($hasError === false) {
            print $this->data;
        }
    }
    protected function sendRawData()
    {
        $hasError = false;
        header(sprintf('HTTP/1.1 %s', str_replace(array(
            "\n",
            "\r"
        ) , "", $this->httpStatusHeader)));
        
        foreach ($this->headers as $key => $currentHeader) {
            header(sprintf("%s: %s", $key, $currentHeader));
        }
        if ($this->messages) {
            foreach ($this->messages as $message) {
                if ($message->type === $message::ERROR) {
                    $hasError = true;
                    print $message;
                }
            }
        }
        if ($hasError === false) {
            print $this->data;
        }
    }
    public function jsonSerialize()
    {
        if ($this->response === null) {
            $values = array(
                "success" => $this->success,
                "messages" => $this->messages,
                "data" => $this->data
            );
        } else {
            $values = $this->response;
        }
        if (!empty($this->exceptionMessage)) {
            $values["exceptionMessage"] = $this->exceptionMessage;
        }
        
        return $values;
    }
}

