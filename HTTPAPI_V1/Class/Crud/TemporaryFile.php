<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\VaultManager;

class TemporaryFile extends Crud
{
    //region CRUD part
    
    /**
     * Create new ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        if (count($_FILES) === 0) {
            $exception = new Exception("CRUD0302", "");
            $exception->setUserMessage(sprintf(___("File not recorded, File size transfert limited to %d Mb", "HTTPAPI_V1") , $this->getUploadLimit() / 1024 / 1024));
            throw $exception;
        }
        $file = current($_FILES);
        
        if ($file["error"]) {
            throw new Exception("CRUD0303", $this->getUploadErrorMessage($file["error"]));
        }
        
        include_once ('FDL/Lib.Vault.php');
        try {
            $vaultid = VaultManager::storeTemporaryFile($file["tmp_name"], $file["name"]);
            $info = VaultManager::getFileInfo($vaultid);
            if ($info === null) {
                $exception = new Exception("CRUD0301", $file["name"]);
                throw $exception;
            }
        }
        catch(\Dcp\Exception $exception) {
            $newException = new Exception("CRUD0300", $exception->getDcpMessage());
            switch ($exception->getDcpCode()) {
                case "VAULT0002":
                    $newException->setUserMessage(___("Cannot store file because vault size limit is reached", "HTTPAPI_V1"));
                    break;

                default:
                    $newException->setUserMessage($exception->getDcpMessage());
            }
            
            throw $newException;
        }
        
        $iconFile = getIconMimeFile($info->mime_s);
        $iconSize = 20;
        $thumbSize = 48;
        // if ($iconFile) $this->icon = $doc->getIcon($iconFile, $info->mime_s);44
        $rootPath = \Dcp\HttpApi\V1\Api\Router::getHttpApiParameter("REST_BASE_URL");
        $thumbnailUrl = '';
        if (strpos($info->mime_s, "image/") === 0) {
            // try to get thumbnail url
            $thumbnailUrl = sprintf("%simages/recorded/sizes/%s/%s.png", $rootPath, $thumbSize, $info->id_file);
        }
        
        $url = sprintf("%sfiles/recorded/temporary/%s.%s", $rootPath, $info->id_file, getFileExtension($file["name"]));
        
        return array(
            "file" => array(
                "id" => $info->id_file,
                "mime" => $info->mime_s,
                "size" => $info->size,
                "thumbnailUrl" => $thumbnailUrl,
                "reference" => sprintf("%s|%s|%s", $info->mime_s, $info->id_file, $info->name) ,
                "cdate" => $info->cdate,
                "downloadUrl" => $url,
                "iconUrl" => sprintf("%simages/assets/sizes/%s/%s", $rootPath, $iconSize, urlencode($iconFile)) ,
                "fileName" => $info->name
            )
        );
    }
    protected function getUploadErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                $message = sprintf("The uploaded file exceeds the upload_max_filesize (%s) directive in php.ini", ini_get("upload_max_filesize"));
                break;

            case UPLOAD_ERR_FORM_SIZE:
                $message = sprintf("The uploaded file exceeds the MAX_FILE_SIZE (%s) directive that was specified in the HTML form", ini_get("max_file_size"));
                break;

            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;

            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;

            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;

            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;

            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;

            default:
                $message = "Unknown upload error";
                break;
        }
        return $message;
    }
    /**
     * Get ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function read($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot read a temporary file");
        throw $exception;
    }
    /**
     * Update the ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function update($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot update a temporary file");
        throw $exception;
    }
    /**
     * Delete ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function delete($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("501", "Not yet implemented");
        throw $exception;
    }
    //endregion CRUD part
    
    /**
     * Analyze the current php conf to get the upload limit
     *
     * @return string
     */
    public static function getUploadLimit()
    {
        /**
         * Converts shorthands like "2M” or "512K” to bytes
         *
         * @param $size
         * @return mixed
         */
        $normalize = function ($size)
        {
            if (preg_match('/^([\d\.]+)([KMG])$/i', $size, $match)) {
                $pos = array_search($match[2], array(
                    "K",
                    "M",
                    "G"
                ));
                if ($pos !== false) {
                    $size = $match[1] * pow(1024, $pos + 1);
                }
            }
            return $size;
        };
        $max_upload = $normalize(ini_get('upload_max_filesize'));
        
        $max_post = (ini_get('post_max_size') == 0) ? function ()
        {
            throw new Exception('Check Your php.ini settings');
        } : $normalize(ini_get('post_max_size'));
        
        $memory_limit = (ini_get('memory_limit') == - 1) ? $max_post : $normalize(ini_get('memory_limit'));
        
        if ($memory_limit < $max_post || $memory_limit < $max_upload) return $memory_limit;
        
        if ($max_post < $max_upload) return $max_post;
        
        $maxFileSize = min($max_upload, $max_post, $memory_limit);
        return $maxFileSize;
    }
}
