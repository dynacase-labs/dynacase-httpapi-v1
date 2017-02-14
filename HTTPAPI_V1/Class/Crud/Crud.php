<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\Api\RecordReturnMessage as RecordReturnMessage;

abstract class Crud
{
    const CREATE = "CREATE";
    const READ = "READ";
    const UPDATE = "UPDATE";
    const DELETE = "DELETE";
    
    protected $messages = array();
    protected $path = null;
    /**
     * Request parameters extracted from the URI
     *
     * @var array
     */
    protected $urlParameters = array();
    /**
     * Request parameters extracted from the content of the request
     *
     * @var array
     */
    protected $contentParameters = array();
    
    protected $httpStatus = "";
    
    protected $controlAcl = true;
    
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
     * @param string $method current CRUD method requireds : CREATE/READ/UPDATE/DELETE
     * @param RecordReturnMessage[] $messages list of messages to send
     * @return mixed data of process
     * @throws Exception
     */
    public function execute($method, array & $messages = array() , &$httpStatus = "")
    {
        
        switch ($method) {
            case "CREATE":
                $data = $this->create();
                $httpStatus = "201 Created";
                break;

            case "READ":
                $identifier = isset($this->urlParameters["identifier"]) ? $this->urlParameters["identifier"] : null;
                $data = $this->read($identifier);
                break;

            case "UPDATE":
                $data = $this->update($this->urlParameters["identifier"]);
                break;

            case "DELETE":
                $data = $this->delete($this->urlParameters["identifier"]);
                break;

            default:
                throw new Exception("CRUD0102", $method);
        }
        $crudHttpStatus = $this->getHttpStatus();
        if ($crudHttpStatus) {
            $httpStatus = $crudHttpStatus;
        }
        $messages = $this->getMessages();
        return $data;
    }
    /**
     * @return string
     */
    public function getHttpStatus()
    {
        return $this->httpStatus;
    }
    /**
     * @param string $httpStatus
     */
    public function setHttpStatus($httpStatus)
    {
        $this->httpStatus = $httpStatus;
    }
    /**
     * Add a message to be sended with the response
     *
     * @param RecordReturnMessage $message
     */
    public function addMessage(RecordReturnMessage $message)
    {
        $this->messages[] = $message;
    }
    /**
     * Get all the added messages
     *
     * @return RecordReturnMessage[]
     */
    public function getMessages()
    {
        return $this->messages;
    }
    /**
     * Set the url parameters
     *
     * @param array $parameters
     */
    public function setUrlParameters(array $parameters)
    {
        $this->urlParameters = $parameters;
    }
    /**
     * Set the content parameters of the current request
     *
     * @param array $parameters
     */
    public function setContentParameters(array $parameters)
    {
        $this->contentParameters = $parameters;
    }
    
    public function getEtagInfo()
    {
        // No need to test GET permission here : no data will be sended
        /*if (!$this->checkCrudPermission("GET")) {
            throw new Exception("CRUD0105", "GET");
        }*/
        return null;
    }
    
    public function generateURL($path, $query = null)
    {
        return URLUtils::generateURL($path, $query);
    }
    
    public function analyseJSON($jsonString)
    {
        if ($jsonString) {
            return json_decode($jsonString, true);
        }
        return array();
    }
    /**
     * @param boolean $controlAcl
     */
    public function setControlAcl($controlAcl)
    {
        $this->controlAcl = $controlAcl;
    }
    /**
     * Check the current user have a permission
     *
     * @param $method
     * @return bool
     * @throws Exception
     */
    public function checkCrudPermission($method)
    {
        if (!$this->controlAcl) {
            // No control if access free is set
            return true;
        }
        $dbAccess = getDbAccess();
        $applicationId = null;
        try {
            simpleQuery($dbAccess, "select id from application where name='HTTPAPI_V1';", $applicationId, true, true, true);
        }
        catch(Exception $exception) {
            throw new Exception("CRUD0104", "Unkown application");
        }

          switch ($method) {
              case "CREATE":
                  $aclName="POST";
                  break;

              case "READ":
                  $aclName="GET";
                  break;

              case "UPDATE":
                  $aclName="PUT";
                  break;

              case "DELETE":
                  $aclName="DELETE";
                  break;
              default:
                  $aclName=$method;
          }


        $permission = new \Permission($dbAccess, array(
            \Doc::getSystemUserId() ,
            $applicationId
        ));
        if ($permission->isAffected()) {
            $acl = new \Acl($dbAccess);
            if (!$acl->Set($aclName, $applicationId)) {
                throw new Exception("CRUD0104", "Unkown ACL $aclName");
            } else {
                return ($permission->HasPrivilege($acl->id));
            }
        }
        throw new Exception("CRUD0104", "Unable to initialize ACL $aclName $applicationId");
    }
}
