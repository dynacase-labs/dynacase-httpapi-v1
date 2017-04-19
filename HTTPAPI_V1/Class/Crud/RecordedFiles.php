<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\VaultManager;

class RecordedFile extends Crud
{
    /**
     * Download resized image
     *
     * @param string $resourceId image filename
     * @throws Exception
     * @return mixed
     */
    public function read($resourceId)
    {
        $vaultId = urldecode($resourceId);
        $fileInfo = VaultManager::getFileInfo($vaultId);
        
        if (!$fileInfo) {
            $e = new Exception("CRUD0617", $vaultId);
            $e->setHttpStatus(404, "File not found");
            throw $e;
        }
        
        if (!$fileInfo->id_tmp) {
            throw new Exception("CRUD0616");
        }
        
        FileUtils::downloadFile($fileInfo->path, $fileInfo->name, $fileInfo->mime_s, false);
        exit;
    }
    /**
     * Create new image ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot create file");
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
        $exception->setHttpStatus("405", "You cannot change file");
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
        $exception->setHttpStatus("405", "You cannot delete file");
        throw $exception;
    }
    public function getEtagInfo()
    {
        return null;
    }
}
