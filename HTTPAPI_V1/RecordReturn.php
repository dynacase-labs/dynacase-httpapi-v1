<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

class RecordReturn
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
     * Send the message
     */
    public function send()
    {
        header(sprintf('HTTP/1.0 %d %s', $this->httpStatus, str_replace(array(
            "\n",
            "\r"
        ) , "", $this->httpMessage)));
        header('Content-Type: application/json');
        
        print json_encode($this);
    }
}

class RecordReturnMessage
{
    const ERROR = "error";
    const MESSAGE = "message";
    const NOTIFICATION = "notification";
    const WARNING = "warning";
    const NOTICE = "notice";
    
    public $type = self::MESSAGE;
    public $contentText = '';
    public $contentHtml = '';
    public $code = '';
    public $uri = '';
    public $data = null;
}

