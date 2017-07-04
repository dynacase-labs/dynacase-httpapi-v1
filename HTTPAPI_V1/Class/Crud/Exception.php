<?php
/*
 * @author Anakeen
 * @package FDL
*/


namespace {

    use Dcp\HttpApi\V1\Crud\MiddleWare;

    class ErrorCodeCrud
    {
        /**
         * @errorCode The ressource is not implemented
         */
        const CRUD0101 = 'Method "%s" not implemented';
        /**
         * @errorCode The ressource is not implemented
         */
        const CRUD0102 = 'Method "%s" not implemented';
        /**
         * @errorCode The ressource is not avalaible
         */
        const CRUD0103 = 'Action "%s" is not usable';
        /**
         * @errorCode Unable to check permission
         */
        const CRUD0104 = 'Unable to check permission (%s)';
        /**
         * @errorCode The user don't have the permission %s
         */
        const CRUD0105 = "The user don't have the permission %s";
        /**
         * @errorCode The class describe in json is not found
         */
        const CRUD0106 = 'Middleware class "%s" not found';
        /**
         * @errorCode The class describe in json is mandatory
         */
        const CRUD0107 = 'Middleware class is undefined in "%s" description';
        /**
         * @errorCode The class describe in json must inherit from Middleware class
         * @see MiddleWare
         */
        const CRUD0108= 'Class "%s" is not a middeware class';
        /**
         * @errorCode Only Create, Read, Update, Delete are allowed
         * @see MiddleWare
         */
        const CRUD0109= 'Invalid crud method "%s"';
        /**
         * @errorCode The ressource is not found
         */
        const CRUD0200 = 'Document "%s" not found';
        /**
         * @errorCode The ressource cannot be get
         */
        const CRUD0201 = 'Document "%s" access deny : %s';
        /**
         * @errorCode The fieds partial response indicate a wrong property
         */
        const CRUD0202 = 'Document property fields "%s" not known';
        /**
         * @errorCode The family is not found
         */
        const CRUD0203 = 'Family "%s" not found';
        /**
         * @errorCode The ressource cannot be get
         */
        const CRUD0204 = 'Document creation "%s" access deny';
        /**
         * @errorCode An attribute cannot be set
         */
        const CRUD0205 = 'Creation document "%s" fail - attribute "%s": "%s"';
        /**
         * @errorCode The document cannot be recorded
         */
        const CRUD0206 = 'Creation document "%s" fail  : "%s"';
        /**
         * @errorCode The family ressource is not found
         */
        const CRUD0207 = 'Family resource"%s" not found';
        /**
         * @errorCode Content-type said json and content is not a json
         */
        const CRUD0208 = 'Record fail. Content page is not a json : "%s" //  example : {"document":{"attributes":{"attributeId" : {"value" : "newValue"}}}}';
        /**
         * @errorCode Content-type said json and content must be contains {document:{attributes:[]}
         */
        const CRUD0209 = 'Record fail. Json object not contains attributes : "%s" // example : {"document":{"attributes":{"attributeId" : {"value" : "newValue"}}}}';
        /**
         * @errorCode Content-type said json and content must be contains {document:{attributes:[["x":{value:"a"}]}
         */
        const CRUD0210 = 'Record fail. Json object attributes no contain "value" fields : "%s" // example : {"document":{"attributes":{"attributeId" : {"value" : "newValue"}}}}';
        /**
         * @errorCode An attribute cannot be set
         */
        const CRUD0211 = 'Update document "%s" fail - attribute "%s": "%s"';
        /**
         * @errorCode The document cannot be recorded
         */
        const CRUD0212 = 'Update document "%s" fail  : "%s"';
        /**
         * @errorCode The family cannot be updated
         */
        const CRUD0213 = 'Update family "%s" is not possible';
        /**
         * @errorCode The fieds partial response indicate a wrong key
         */
        const CRUD0214 = 'Document fields "%s" not known';
        /**
         * @errorCode The document cannot be deleted
         */
        const CRUD0215 = 'Delete Document "%s" fail : "%s" ';
        /**
         * @errorCode The document cannot be deleted
         */
        const CRUD0216 = 'Delete deny for document "%s" fail : "%s" ';
        /**
         * @errorCode Content-type said json and content must be contains {document:{attributes:[["x":{value:"a"}]}
         */
        const CRUD0217 = 'Record fail. Json object attributes "%s" (multiple) no contain "value" fields : "%s"';
        /**
         * @errorCode Document attribute "%s" not known
         */
        const CRUD0218 = 'Document attribute "%s" not known';
        /**
         * @errorCode Document "%s" deleted
         */
        const CRUD0219 = 'Document "%s" deleted';
        /**
         * @errorCode Document "%s" deleted
         */
        const CRUD0220 = 'Document "%s" is not a document of the family "%s"';
        /**
         * @errorCode The ressource is not found
         */
        const CRUD0221 = 'The revision "%s" of document "%s" is not found';
        /**
         * @errorCode Redirect : the canonical URL is not here
         */
        const CRUD0222 = 'Redirect : the canonical URL is not here';
        /**
         * @errorCode THe user tag not exists
         */
        const CRUD0223 = 'Tag "%s" not exists';
        /**
         * @errorCode The user tag is not created
         */
        const CRUD0224 = 'Cannot create tag "%s"  : %s';
        /**
         * @errorCode The user tag cannot be created
         */
        const CRUD0225 = 'Tag "%s" already exists';
        /**
         * @errorCode The user tag cannot be created
         */
        const CRUD0226 = 'Cannot delete tag "%s"  : %s';
        /**
         * @errorCode A workflow must be set to get state list
         */
        const CRUD0227 = 'No associated workflow for document "%s"';
        /**
         * @errorCode The state is not defined in the workflow
         */
        const CRUD0228 = 'State "%s" is not available for  workflow "%s" (%d)';
        /**
         * @errorCode The transition is not defined in the workflow
         */
        const CRUD0229 = 'transition "%s" is not available for  workflow "%s" (%d)';
        /**
         * @errorCode Need transition acl
         */
        const CRUD0230 = 'Cannot use transition "%s" ';
        /**
         * @errorCode Lock is not granted
         */
        const CRUD0231 = 'Cannot lock document : "%s" ';
        /**
         * @errorCode UnLock is not granted
         */
        const CRUD0232 = 'Cannot unlock document : "%s" ';
        /**
         * @errorCode Temporary UnLock is not granted if a permanent lock is set
         */
        const CRUD0233 = 'Cannot unlock temporary lock. A permanent lock is set  : "%s" ';
        /**
         * @errorCode Permanent UnLock is not possible only temporary lock is set
         */
        const CRUD0234 = 'Cannot unlock permanent lock. A temporary lock is set (use DELETE locks/ to delete all locks) : "%s" ';

        /**
         * @errorCode The transition is not valid in the workflow
         */
        const CRUD0235 = 'Destination state is not defined for workflow "%s" (%d)';
        /**
         * @errorCode The restoration elements are not valid
         */
        const CRUD0236 = 'The restoration must be initialized with {"document" : { "properties" : { "status" : "alive" } } }';
        /**
         * @errorCode Document "%s" deleted
         */
        const CRUD0237 = 'Document "%s" is not deleted';
        /**
         * @errorCode The file cannot be saved to vaulft
         */
        const CRUD0300 = 'File Record fail.  : "%s"';
        /**
         * @errorCode The file is not found in vault
         */
        const CRUD0301 = 'No file information for "%s" file';
        /**
         * @errorCode Could append when max_file_upload limit is reached
         */
        const CRUD0302 = 'No file transferred';
        /**
         * @errorCode Uploaded file cannot be retrieved
         */
        const CRUD0303 = 'Error recording file : %s';
        /**
         * @errorCode The enum attribute is not a part of family structure
         */
        const CRUD0400 = 'Enum "%s" not exists in family "%s"';
        /**
         * @errorCode The attribute is not an enum
         */
        const CRUD0401 = 'Attribute "%s" is not an enum (type "%s") in family "%s"';
        /**
         * @errorCode Only operators startsWith and contains are allowed
         */
        const CRUD0402 = 'Filter operateur "%s" not available. Availables are "%s"';
        /**
         * @errorCode invalid sortBy operator
         */
        const CRUD0403 = 'sortBy operateur "%s" not available. Availables are "%s"';
        /**
         * @errorCode Only operators startsWith and contains are allowed
         */
        const CRUD0500 = 'Unable to format, the input is not in kown type';
        /**
         * @errorCode The sort direction must be desc or asc (not %s)
         */
        const CRUD0501 = 'The sort direction must be desc or asc (not "%s")';
        /**
         * @errorCode The sort direction must be desc or asc (not %s)
         */
        const CRUD0502 = 'The required field must be an attribute with value or a property (not "%s")';
        /**
         * @errorCode The collection must be a search
         */
        const CRUD0503 = 'The collection must be a search';
        /**
         * @errorCode The collection must be a folder
         */
        const CRUD0504 = 'The collection must be a folder';
        /**
         * @errorCode Unable to restore the document
         */
        const CRUD0505 = 'Unable to restore the document, error : %s';
        /**
         * @errorCode The sort direction must be desc or asc (not %s)
         */
        const CRUD0506 = 'The order must be an attribute or a property (not "%s")';
        /**
         * @errorCode The required attribute query must have value
         */
        const CRUD0507 = 'The required field must not be a structured attribute : not "%s" (%s) - type : "%s"';
        /**
         * @errorCode Visibility is "I" for this attribute
         */
        const CRUD0508 = 'The required field "%s" (%s) has protected access';
        /**
         * @errorCode The image file must be in "Images" directory
         */
        const CRUD0600 = 'Asset Image file "%s" not found';
        /**
         * @errorCode The image file must be an real image
         */
        const CRUD0601 = 'Asset Image file "%s" is not an image';
        /**
         * @errorCode The image file cannot be converted
         */
        const CRUD0602 = 'Cannot resize image "%s" for "%s" size';
        /**
         * @errorCode The size must be a number or 2 numbers separate by x
         */
        const CRUD0603 = 'Cannot resize image "%s" : incorrect size "%s"';
        /**
         * @errorCode The file extension asked must be correct
         */
        const CRUD0604 = 'Cannot get original recorded image "%s" : extension "%s" not correct';
        /**
         * @errorCode The attribute set in url is not part of document
         */
        const CRUD0605 = 'Cannot download image : Attribut "%s" of document "%s" not exists';
        /**
         * @errorCode The attribute as an "I" visibility
         */
        const CRUD0606 = 'Access denied to download image : Attribut "%s" of document "%s" is protected';
        /**
         * @errorCode The attribute value is empty
         */
        const CRUD0607 = 'No image in attribute "%s" (index "%s") in document "%s"';
        /**
         * @errorCode The vault id set in attribute not exists
         */
        const CRUD0608 = 'Image id not exists in attribute "%s" (index "%s") in document "%s"';
        /**
         * @errorCode The attribute value is malformed
         */
        const CRUD0609 = 'Incorrect value in file/image in attribute "%s" (index "%s") in document "%s"';
        /**
         * @errorCode The index must be greater or equal to 0
         */
        const CRUD0610 = 'Incorrect index "%s" attribute "%s" is multiple in document "%s"';
        /**
         * @errorCode The index for a single value must be -1
         */
        const CRUD0611 = 'Incorrect index "%s" (must be -1) attribute "%s" is not multiple in document "%s"';
        /**
         * @errorCode The index to select attribute value is -1 for single value or >=0 for multiple values
         */
        const CRUD0612 = 'Incorrect index "%s" (must be >= -1) attribute "%s" in document "%s"';
        /**
         * @errorCode The file to download is not found
         */
        const CRUD0613 = 'Cannot find file "%s" for download';
        /**
         * @errorCode The attribute must reference a file
         */
        const CRUD0614 = 'Attribute "%s" is not a file or image attribute in document "%s"';
        /**
         * @errorCode Error when try to create temporary archive
         */
        const CRUD0615 = 'Cannot create zip archive for attribute "%s" in document "%s"';
        /**
         * @errorCode The file is not a temporary file
         */
        const CRUD0616 = 'Access denied to download file : not a temporary file';
        /**
         * @errorCode The reference file is unknow
         */
        const CRUD0617 = 'Temporary file "%s"  not found';
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