<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace {
    
    class ErrorCodeApi
    {
        /**
         * @errorCode When an system exception occurs
         */
        const API0001 = 'System exception';
        /**
         * @errorCode A method for a resource is not implemented
         */
        const API0002 = 'Method "%s" not implemented';
        /**
         * @errorCode Content type must be application/x-www-form-urlencoded or application/json
         */
        const API0003 = 'Content type "%s" not supported';
        /**
         * @errorCode in case of incorrect url
         */
        const API0004 = 'The URL %s is not compatible with the ressources of the API';
        /**
         * @errorCode in case of accept unknown
         */
        const API0005 = 'Unable to return the type %s';
        /**
         * @errorCode when extract return type from http header
         */
        const API0006 = 'Unable to return the type from http headers %s';
        /**
         * for beautifier
         */
        private function _bo()
        {
            if (true) return;
        }
    }
}
namespace Dcp\HttpApi\V1\Api {
    class Exception extends \Dcp\Exception
    {
        
        protected $httpStatus = 400;
        protected $httpMessage = "Dcp Exception";
        protected $data = null;
        protected $userMessage = '';
        protected $uri = "";
        protected $headers = array();
        /**
         * @param string $userMessage
         */
        public function setUserMessage($userMessage)
        {
            $this->userMessage = $userMessage;
        }
        /**
         * @return string
         */
        public function getUserMessage()
        {
            return $this->userMessage;
        }
        /**
         * @return null
         */
        public function getData()
        {
            return $this->data;
        }
        /**
         * Add
         *
         * @param null $data
         */
        public function setData($data)
        {
            $this->data = $data;
        }
        /**
         * Return the http message
         *
         * @return string
         */
        public function getHttpMessage()
        {
            return $this->httpMessage;
        }
        /**
         * Return the http status
         *
         * @return int
         */
        public function getHttpStatus()
        {
            return $this->httpStatus;
        }
        /**
         *
         * @param int $httpStatus
         * @param string $httpMessage
         */
        public function setHttpStatus($httpStatus, $httpMessage = "")
        {
            $this->httpStatus = $httpStatus;
            $this->httpMessage = $httpMessage;
        }
        /**
         * Add an URI indication
         *
         * @param $uri
         */
        public function setURI($uri)
        {
            $this->uri = $uri;
        }
        /**
         * Return the URI indication
         *
         */
        public function getURI()
        {
            return $this->uri;
        }
        /**
         * Add an header
         *
         * @param $key
         * @param $value
         * @internal param $uri
         */
        public function addHeader($key, $value)
        {
            $this->headers[$key] = $value;
        }
        /**
         * Return the URI indication
         *
         */
        public function getHeaders()
        {
            return $this->headers;
        }
        /**
         * for beautifier
         */
        private function _bo()
        {
            if (true) return;
        }
    }
}
