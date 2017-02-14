<?php
namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\Api\HttpRequest;
use Dcp\HttpApi\V1\Api\HttpResponse;

class MiddleWare
{
    /**
     * @var \Dcp\HttpApi\V1\Api\HttpRequest
     */
    protected $request;
    /**
     * @var \Dcp\HttpApi\V1\Api\HttpResponse
     */
    protected $response;
    
    public function __construct(HttpRequest $request, HttpResponse $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
    /**
     * @return \Dcp\HttpApi\V1\Api\HttpResponse
     */
    public function getHttpResponse()
    {
        return $this->response;
    }
    /**
     * @return \Dcp\HttpApi\V1\Api\HttpRequest
     */
    public function getHttpRequest()
    {
        return $this->request;
    }
    /**
     * Create new ressource
     *
     * @return mixed
     */
    public function create()
    {
        return null;
    }
    /**
     * Read a ressource
     *
     * @param string|int $resourceId Resource identifier
     *
     * @return mixed
     */
    public function read($resourceId)
    {
        return null;
    }
    /**
     * Update the ressource
     *
     * @param string|int $resourceId Resource identifier
     *
     * @return mixed
     */
    public function update($resourceId)
    {
        return null;
    }
    /**
     * Delete ressource
     *
     * @param string|int $resourceId Resource identifier
     *
     * @return mixed
     */
    public function delete($resourceId)
    {
        return null;
    }
}
