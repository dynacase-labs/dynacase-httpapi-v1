<?php
namespace Dcp\HttpApi\V1\Api;

use Dcp\HttpApi\V1\Crud\Crud;

class HttpRequest
{
    protected $method = "";
    protected $headers = [];
    
    protected $parameters = [];
    protected $uri;
    
    public function __construct($crudMethod, $parameters)
    {
        $this->headers = $this->getHttpHeader();
        
        if (empty($_SERVER["REQUEST_SCHEME"])) {
            $_SERVER["REQUEST_SCHEME"] = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
        }
        
        $this->uri = sprintf("%s://%s:%s%s", $_SERVER["REQUEST_SCHEME"], $_SERVER["SERVER_NAME"], $_SERVER["SERVER_PORT"], $_SERVER["REQUEST_URI"]);
        
        $this->method = $crudMethod;
        $this->parameters = $parameters;
    }
    /**
     * Return GET, POST, PUT , DELETE
     * @return string
     */
    public function getMethod()
    {
        switch ($this->method) {
            case Crud::READ:
                return "GET";
            case Crud::CREATE:
                return "POST";
            case Crud::DELETE:
                return "DELETE";
            case Crud::UPDATE:
                return "PUT";
        }
        return $_SERVER["REQUEST_METHOD"];
    }
    
    public function getCrudMethod()
    {
        return $this->method;
    }
    
    public function setCrudMethod($method)
    {
        if (!in_array($method, [Crud::READ, Crud::CREATE, Crud::DELETE, Crud::UPDATE])) {
            throw new \Dcp\HttpApi\V1\Crud\Exception("CRUD0109", $method);
        }
        
        $this->method = $method;
    }
    /**
     * Return list of request parameters
     * Based on $_GET or $_POST depends on method request
     * @return array
     */
    public function getQuery()
    {
        return $this->parameters;
    }
    /**
     * Return elements of request url
     * @see parse_url
     * @return array
     */
    public function getUri()
    {
        return parse_url($this->uri);
    }
    /**
     * Return list of http header request
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    /**
     * @return array
     */
    protected function getHttpHeader()
    {
        if (!function_exists('getallheaders')) {
            // Nginx server
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) === 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))) ] = $value;
                }
            }
            return $headers;
        } else {
            return getallheaders();
        }
    }
}
