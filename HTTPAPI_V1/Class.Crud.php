<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

abstract class Crud
{
    /**
     * @var RecordReturnMessage[]
     */
    protected $messages = array();
    /**
     * @param \Dcp\HttpApi\V1\RecordReturnMessage $message
     */
    public function addMessage(RecordReturnMessage $message)
    {
        $this->messages[] = $message;
    }
    /**
     * @return \Dcp\HttpApi\V1\RecordReturnMessage[]
     */
    public function getMessages()
    {
        return $this->messages;
    }
    protected static function getHttpMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }
    
    protected function getRessourceIdentifier()
    {
        return $_GET["id"];
    }
    /**
     * Update the ressource
     * @param string $resourceId Resource identifier
     * @return mixed
     */
    abstract public function update($resourceId);
    /**
     * Create new ressource
     * @return mixed
     */
    abstract public function create();
    /**
     * Get ressource
     * @param string $resourceId Resource identifier
     * @return mixed
     */
    abstract public function get($resourceId);
    /**
     * Delete ressource
     * @param string $resourceId Resource identifier
     * @return mixed
     */
    abstract public function delete($resourceId);
    /**
     * @param array $messages list of messages to send
     * @return mixed data of process
     * @throws Exception
     */
    public function execute(array & $messages = array())
    {
        $method = self::getHttpMethod();
        
        switch ($method) {
            case "PUT":
                $data = $this->update($this->getRessourceIdentifier());
                break;

            case "POST":
                $data = $this->create();
                break;

            case "GET":
                $data = $this->get($this->getRessourceIdentifier());
                break;

            case "DELETE":
                $data = $this->delete($this->getRessourceIdentifier());
                break;

            default:
                throw new Exception("API0102", $method);
        }
        $messages = $this->getMessages();
        return $data;
    }
}
