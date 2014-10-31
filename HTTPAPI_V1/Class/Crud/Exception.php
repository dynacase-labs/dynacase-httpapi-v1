<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/


namespace {

    class CrudCodeApi
    {
        /**
         * @errorCode The ressource is not implemented
         */
        const API0102 = 'Method "%s" not implemented';
        /**
         * @errorCode The ressource is not avalaible
         */
        const API0103 = 'Action "%s" is not usable';
        /**
         * @errorCode The ressource is not found
         */
        const API0200 = 'Document "%s" not found';
        /**
         * @errorCode The ressource cannot be get
         */
        const API0201 = 'Document "%s" access deny : %s';
        /**
         * @errorCode The fieds partial response indicate a wrong property
         */
        const API0202 = 'Document property fields "%s" not known';
        /**
         * @errorCode The family is not found
         */
        const API0203 = 'Family "%s" not found';
        /**
         * @errorCode The ressource cannot be get
         */
        const API0204 = 'Document creation "%s" access deny';
        /**
         * @errorCode An attribute cannot be set
         */
        const API0205 = 'Creation document "%s" fail - attribute "%s": "%s"';
        /**
         * @errorCode The document cannot be recorded
         */
        const API0206 = 'Creation document "%s" fail  : "%s"';
        /**
         * @errorCode The family ressource is not found
         */
        const API0207 = 'Family resource"%s" not found';
        /**
         * @errorCode Content-type said json and content is not a json
         */
        const API0208 = 'Record fail. Content page is not a json : "%s" //  example : {"document":{"attributes":{"attributeId" : {"value" : "newValue"}}}}';
        /**
         * @errorCode Content-type said json and content must be contains {document:{attributes:[]}
         */
        const API0209 = 'Record fail. Json object not contains attributes : "%s" // example : {"document":{"attributes":{"attributeId" : {"value" : "newValue"}}}}';
        /**
         * @errorCode Content-type said json and content must be contains {document:{attributes:[["x":{value:"a"}]}
         */
        const API0210 = 'Record fail. Json object attributes no contain "value" fields : "%s" // example : {"document":{"attributes":{"attributeId" : {"value" : "newValue"}}}}';
        /**
         * @errorCode An attribute cannot be set
         */
        const API0211 = 'Update document "%s" fail - attribute "%s": "%s"';
        /**
         * @errorCode The document cannot be recorded
         */
        const API0212 = 'Update document "%s" fail  : "%s"';
        /**
         * @errorCode The family cannot be updated
         */
        const API0213 = 'Update family "%s" is not possible';
        /**
         * @errorCode The fieds partial response indicate a wrong key
         */
        const API0214 = 'Document fields "%s" not known';
        /**
         * @errorCode The document cannot be deleted
         */
        const API0215 = 'Delete Document "%s" fail : "%s" ';
        /**
         * @errorCode The document cannot be deleted
         */
        const API0216 = 'Delete deny for document "%s" fail : "%s" ';
        /**
         * @errorCode Content-type said json and content must be contains {document:{attributes:[["x":{value:"a"}]}
         */
        const API0217 = 'Record fail. Json object attributes "%s" (multiple) no contain "value" fields : "%s"';
        /**
         * @errorCode Document attribute "%s" not known
         */
        const API0218 = 'Document attribute "%s" not known';
        /**
         * @errorCode Document "%s" deleted
         */
        const API0219 = 'Document "%s" deleted';
        /**
         * @errorCode Document "%s" deleted
         */
        const API0220 = 'Document "%s" is not a document of the family "%s"';
        /**
         * @errorCode The ressource is not found
         */
        const API0221 = 'The revision "%d" of document "%s" is not found';
        /**
         * @errorCode The file cannot be saved to vaulft
         */
        const API0300 = 'File Record fail.  : "%s"';
        /**
         * @errorCode The file is not found in vault
         */
        const API0301 = 'No file information for "%s" file';
        /**
         * @errorCode Could append when max_file_upload limit is reached
         */
        const API0302 = 'No file transferred';
        /**
         * @errorCode The enum attribute is not a part of family structure
         */
        const API0400 = 'Enum "%s" not exists in family "%s"';
        /**
         * @errorCode The attribute is not an enum
         */
        const API0401 = 'Attribute "%s" is not an enum (type "%s") in family "%s"';
        /**
         * @errorCode Only operators startsWith and contains are allowed
         */
        const API0402 = 'Filter operateur "%s" not available. Availables are "%s"';

    }
}

namespace Dcp\HttpApi\V1\Crud {
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

    }
}