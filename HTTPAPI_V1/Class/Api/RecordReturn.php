<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
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
            case "json":
                return $this->sendJson();
            case "html":
                return $this->sendHtml();
            default:
                return $this->sendJson();
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
                        print "<html><body>\n"; #a94442
                        print "<style>.error {color:#a94442;text-align:center;border:solid 1px #FB657D;margin:0 30%;padding:1em}\n";
                        print "body {background-color:#f2dede;}\n";
                        print "</style>\n";
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
    
    public function jsonSerialize()
    {
        $values = array(
            "success" => $this->success,
            "messages" => $this->messages,
            "data" => $this->data
        );
        if (!empty($this->exceptionMessage)) {
            $values["exceptionMessage"] = $this->exceptionMessage;
        }
        
        return $values;
    }
}

