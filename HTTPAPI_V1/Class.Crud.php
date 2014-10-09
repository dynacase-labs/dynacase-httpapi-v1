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

    public function __construct()
    {

    }
    //region CRUD part
    /**
     * Create new ressource
     * @return mixed
     */
    abstract public function create();

    /**
     * Read a ressource
     * @param string|int $resourceId Resource identifier
     * @return mixed
     */
    abstract public function read($resourceId);

    /**
     * Update the ressource
     * @param string|int $resourceId Resource identifier
     * @return mixed
     */
    abstract public function update($resourceId);

    /**
     * Delete ressource
     * @param string|int $resourceId Resource identifier
     * @return mixed
     */
    abstract public function delete($resourceId);
    //endregion
    /**
     * Execute the request
     * Find the CRUD action to execute and execute it
     *
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
                $data = $this->read($this->getRessourceIdentifier());
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

    /**
     * Add a message to be sended with the response
     *
     * @param \Dcp\HttpApi\V1\RecordReturnMessage $message
     */
    public function addMessage(RecordReturnMessage $message)
    {
        $this->messages[] = $message;
    }

    /**
     * Get all the added messages
     *
     * @return \Dcp\HttpApi\V1\RecordReturnMessage[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Get the current method
     *
     * @return string
     */
    protected static function getHttpMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Get the current id of the ressource
     *
     * @return null
     */
    protected function getRessourceIdentifier()
    {
        if (isset($_GET["id"])) {
            return $_GET["id"];
        }
        return null;
    }
}
