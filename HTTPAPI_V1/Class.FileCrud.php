<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

class FileCrud extends Crud
{
    /**
     * Update the ressource
     * @param string $resourceId Resource identifier
     * @return mixed
     */
    public function update($resourceId)
    {
        $e = new Exception("API0002", __METHOD__);
        $e->setHttpStatus("501", "Not implemented");
        throw $e;
    }
    /**
     * Create new ressource
     * @return mixed
     */
    public function create()
    {
        // print_r(__METHOD__);
        if (count($_FILES) === 0) {
            
            $e = new Exception("API0302", "");
            $e->setUserMessage(___("File not recorded, Size limit reached", "api"));
            throw $e;
        }
        $file = current($_FILES);
        include_once ('FDL/Lib.Vault.php');
        try {
            $vaultid = \Dcp\VaultManager::storeTemporaryFile($file["tmp_name"], $file["name"]);
            $info = \Dcp\VaultManager::getFileInfo($vaultid);
            if ($info === null) {
                $e = new Exception("API0301", $file["name"]);
                throw $e;
            }
        }
        catch(\Dcp\Exception $e) {
            $e = new Exception("API0300", $e->getDcpMessage());
            $e->setUserMessage($e->getDcpMessage());
            throw $e;
        }
        
        $iconFile = getIconMimeFile($info->mime_s);
        $iconSize = 20;
        $thumbSize = 48;
        // if ($iconFile) $this->icon = $doc->getIcon($iconFile, $info->mime_s);44
        $thumbnailUrl = '';
        if (strpos($info->mime_s, "image/") === 0) {
            // try to get thumbnail url
            $thumbnailUrl = sprintf("resizeimg.php?img=vaultid=%d&size=%d", $info->id_file, $thumbSize);
        }
        
        $url = sprintf("file/%s/%d/%s/%s/%s?cache=no&inline=no", 0, $info->id_file, "-", $index = - 1, rawurlencode($info->name));
        
        return array(
            "file" => array(
                "id" => $info->id_file,
                "mime" => $info->mime_s,
                "size" => $info->size,
                "thumbnailUrl" => $thumbnailUrl,
                "reference" => sprintf("%s|%s|%s", $info->mime_s, $info->id_file, $info->name) ,
                "cdate" => $info->cdate,
                "downloadUrl" => $url,
                "iconUrl" => sprintf("resizeimg.php?img=Images/%s&size=%d", urlencode($iconFile) , $iconSize) ,
                "fileName" => $info->name
            )
        );
    }
    /**
     * Get ressource
     * @param string $resourceId Resource identifier
     * @return mixed
     */
    public function get($resourceId)
    {
        $e = new Exception("API0002", __METHOD__);
        $e->setHttpStatus("501", "Not implemented");
        throw $e;
    }
    /**
     * Delete ressource
     * @param string $resourceId Resource identifier
     * @return mixed
     */
    public function delete($resourceId)
    {
        $e = new Exception("API0002", __METHOD__);
        $e->setHttpStatus("501", "Not implemented");
        throw $e;
    }
}
