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
    /**
     * @var bool indicate if method succeed
     */
    public $success = true;
    /**
     * @var RecordReturnMessage[] message list
     */
    public $messages = array();
    /**
     * @var \stdClass misc data
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
     * @internal param mixed $data
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }
    /**
     * Send the message
     */
    public function send()
    {
        header(sprintf('HTTP/1.1 %d %s', $this->httpStatus, str_replace(array(
            "\n",
            "\r"
        ) , "", $this->httpMessage)));
        header('Content-Type: application/json');
        foreach ($this->headers as $key => $currentHeader) {
            header(sprintf("%s : %s", $key, $currentHeader));
        }
        
        print json_encode($this);
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



