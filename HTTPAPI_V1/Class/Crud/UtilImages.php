<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

class FileUtils
{
    public static function resizeLocalImage($sourceFileName, $dest, $size)
    {
        
        if (!preg_match("/^x?[0-9]+$/", $size) && !preg_match("/^[0-9]+x[0-9]+[fsc]?$/", $size)) {
            throw new Exception("CRUD0603", basename($sourceFileName) , $size);
        }
        
        $tsize = getimagesize($sourceFileName);
        if (!$tsize) {
            throw new Exception("CRUD0601", basename($sourceFileName));
        }
        $maxWidth = $tsize[0];
        $maxHeight = $tsize[1];
        
        if (preg_match("/([0-9]*)x?([0-9]*)/", $size, $reg)) {
            $width = intval($reg[1]);
            $height = intval($reg[2]);
            
            $maxWidthSet = 0;
            $maxHeigthSet = 0;
            if ($width && $maxWidth > 0 && $width > $maxWidth) {
                $maxWidthSet = $maxWidth;
                if ($height) {
                    $size = preg_replace("/^([0-9]+)/", $maxWidthSet, $size);
                }
            }
            if ($height && $maxHeight > 0 && $height > $maxHeight) {
                $maxHeigthSet = $maxHeight;
                if ($width) {
                    $size = preg_replace("/x([0-9]+)/", "x" . $maxHeight, $size);
                }
            }
            
            if (($maxWidthSet && $height && $maxHeigthSet && $width) || ($maxWidthSet && !$height) || ($maxHeigthSet && !$width)) {
                return $sourceFileName;
            }
        }
        
        $size = str_replace(array(
            "f",
            "s"
        ) , array(
            "",
            "!"
        ) , $size);
        
        if (preg_match("/^([0-9]+x[0-9]+)c$/", $size, $reg)) {
            $cmd = sprintf("convert  -resize %s -gravity center -crop %s+0+0 %s %s", escapeshellarg($reg[1] . "^") , escapeshellarg($reg[1]) , escapeshellarg($sourceFileName) , escapeshellarg($dest));
        } else {
            $cmd = sprintf("convert  -resize %s %s %s", escapeshellarg($size) , escapeshellarg($sourceFileName) , escapeshellarg($dest));
        }
        system($cmd);
        if (file_exists($dest)) return $dest;
        throw new Exception("CRUD0602", basename($sourceFileName) , $size);
    }
    
    public static function getVaultPath($vid, $onlyPublic = false)
    {
        $dbaccess = getDbAccess();
        $rcore = pg_connect($dbaccess);
        if ($rcore) {
            if ($onlyPublic) {
                $publicCond = "public_access or";
            } else {
                $publicCond = "";
            }
            $sql = sprintf("select id_dir,name,public_access,id_tmp from vaultdiskstorage where id_file = %s and ($publicCond id_tmp is not null)", pg_escape_literal($vid));
            
            $result = pg_query($sql);
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
    
    public static function downloadFile($filePath, $fileName = "", $mime = "", $inline = true, $cache = true)
    {
        require_once ("WHAT/Lib.Http.php");
        if (!$fileName) {
            $fileName = basename($filePath);
        }
        if (!file_exists($filePath)) {
            throw new Exception("CRUD0600", basename($filePath));
        }
        // Double quote not supported by all browsers - replace by minus
        $name = str_replace('"', '-', $fileName);
        $uName = iconv("UTF-8", "ASCII//TRANSLIT", $name);
        $name = rawurlencode($name);
        if (!$inline) {
            header("Content-Disposition: attachment;filename=\"$uName\";filename*=UTF-8''$name;");
        } else {
            header("Content-Disposition: inline;filename=\"$uName\";filename*=UTF-8''$name;");
        }
        
        if ($cache) {
            $duration = 24 * 3600;
            header("Cache-Control: private, max-age=$duration"); // use cache client (one hour)
            header("Expires: " . gmdate("D, d M Y H:i:s T\n", time() + $duration)); // for mozilla
            
        }
        
        if ($mime) {
            header("Content-type: " . $mime);
        }
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($filePath));
        //ob_clean();
        //flush();
        readfile($filePath);
    }
    
    public static function getMimeImage($fileName)
    {
        
        $fileExtension = substr($fileName, strrpos($fileName, '.') + 1);
        
        switch ($fileExtension) {
            case "jpg":
                $mime = "image/jpeg";
                break;

            default:
                $mime = "image/" . $fileExtension;
        }
        return $mime;
    }
}
