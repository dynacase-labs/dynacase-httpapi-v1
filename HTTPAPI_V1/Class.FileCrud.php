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
            $message = new RecordReturnMessage();
            $message->contentText = ___("No file transfered", "api");
            $message->type = $message::ERROR;
            $message->code = "nofile";
            $message->data["uploadLimit"] = ini_get("upload_max_filesize");
            $this->addMessage($message);
        } else {
            $file = current($_FILES);
            include_once ('FDL/Lib.Vault.php');
            $err = vault_store($file["tmp_name"], $vaultid, $file["name"]);
            if ($err != '') {
                $e = new Exception("API0300", $err);
                throw $e;
            }
            $info = vault_properties($vaultid);
            if (!is_object($info) || !is_a($info, 'VaultFileInfo')) {
                
                $e = new Exception("API0301", $file["name"]);
                throw $e;
            }
            
            $iconFile = getIconMimeFile($info->mime_s);
            $iconSize = 20;
            // if ($iconFile) $this->icon = $doc->getIcon($iconFile, $info->mime_s);44
            $thumbnailUrl = '';
            if (strpos($info->mime_s, "image/") === 0) {
                $thumbnailUrl
            }
            
            return array(
                "file" => array(
                    "id" => $info->id_file,
                    "mime" => $info->mime_s,
                    "size" => $info->size,
                    "thumbnailUrl" => $thumbnailUrl,
                    "reference" => sprintf("%s|%s|%s", $info->mime_s, $info->id_file, $info->name) ,
                    "cdate" => $info->cdate,
                    "downloadUrl" => "?xxx",
                    "iconUrl" => sprintf("resizeimg.php?img=Images/%s&size=%d", urlencode($iconFile) , $iconSize) ,
                    "fileName" => $info->name
                )
            );
        }
        return "";
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
