<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

class RecordedImage extends ImageAsset
{
    protected function getSourceImage()
    {
        $location = $this->getVaultPath($this->imageFileName);
        
        if (!$location || !file_exists($location)) {
            throw new Exception("CRUD0600", $this->imageFileName);
        }
        
        if (basename($location) !== sprintf("%s%s", $this->imageFileName, $this->urlParameters["extension"])) {
            throw new Exception("CRUD0604", $this->imageFileName, $this->urlParameters["extension"]);
        }
        
        return $location;
    }
    
    protected function getDestinationCacheImage($localimage, $size)
    {
        
        $fileExtension = $this->urlParameters["extension"];
        $basedest = sprintf("%s/%s/%s-vid%s%s", DEFAULT_PUBDIR, self::CACHEIMGDIR, $size, str_replace("/", "_", $localimage) , $fileExtension);
        
        return $basedest;
    }
    
    protected function getVaultPath($vid)
    {
        $dbaccess = getDbAccess();
        $rcore = pg_connect($dbaccess);
        if ($rcore) {
            $result = pg_query(sprintf("select id_dir,name,public_access from vaultdiskstorage where id_file = %s and (public_access or id_tmp is null)", pg_escape_literal($vid)));
            if ($result) {
                $row = pg_fetch_assoc($result);
                if ($row) {
                    $iddir = $row["id_dir"];
                    $name = $row["name"];
                    $tmpSessId = $row["id_tmp"];
                    
                    if ($tmpSessId) {
                        // Verify if tmp file is produced by current user session
                        if (!isset($_COOKIE[\Session::PARAMNAME]) || $tmpSessId !== $_COOKIE[\Session::PARAMNAME]) {
                            return false;
                        }
                    }
                    
                    $ext = '';
                    if (preg_match('/\.([^\.]*)$/', $name, $reg)) {
                        $ext = $reg[1];
                    }
                    
                    $result = pg_query(sprintf("SELECT l_path,id_fs from vaultdiskdirstorage where id_dir = %d", $iddir));
                    $row = pg_fetch_assoc($result);
                    $lpath = $row["l_path"];
                    $idfs = $row["id_fs"];
                    $result = pg_query(sprintf("SELECT r_path from vaultdiskfsstorage where id_fs = %d", $idfs));
                    $row = pg_fetch_assoc($result);
                    $rpath = $row["r_path"];
                    
                    $localimg = "$rpath/$lpath/$vid.$ext";
                    if (file_exists($localimg)) {
                        return $localimg;
                    }
                }
            }
        }
        return false;
    }
}
