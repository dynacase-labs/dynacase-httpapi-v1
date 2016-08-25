<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

class ImageAsset extends Crud
{
    
    const CACHEIMGDIR = "var/cache/image/";
    protected $size;
    protected $imageFileName;
    //region CRUD part
    
    
    /**
     * Gettag ressource
     *
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function read($resourceId)
    {
        $this->imageFileName = urldecode($resourceId);
        
        $location = $this->getSourceImage();
        
        $tsize = getimagesize($location);
        if (!$tsize) {
            throw new Exception("CRUD0601", $resourceId);
        }
        
        if ($this->size !== null) {
            $location = $this->resizeLocalImage($location, $this->size, $tsize[0], $tsize[1]);
        }
        require_once ("WHAT/Lib.Http.php");
        Http_DownloadFile($location, basename($location) , "", true, true);
    }
    /**
     * Create new tag ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot create image");
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
        $exception->setHttpStatus("405", "You cannot change image");
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
        $exception->setHttpStatus("405", "You cannot delete image");
        throw $exception;
    }
    public function getEtagInfo()
    {
        return null;
    }
    /**
     * Set the family of the current request
     *
     * @param array $array
     * @throws Exception
     */
    public function setUrlParameters(Array $array)
    {
        parent::setUrlParameters($array);
        $this->size = isset($this->urlParameters["size"]) ? $this->urlParameters["size"] : null;
    }
    
    protected function getDestinationCacheImage($localimage, $size)
    {
        $basedest = sprintf("%s/%s/%s-%s", DEFAULT_PUBDIR, self::CACHEIMGDIR, $size, str_replace("/", "_", $localimage));
        
        return $basedest;
    }
    /**
     * Get images from "Images" folder
     * @return string
     * @throws Exception
     */
    protected function getSourceImage()
    {
        $location = sprintf("Images/%s", $this->imageFileName);
        if (!file_exists($location)) {
            throw new Exception("CRUD0600", $this->imageFileName);
        }
        return $location;
    }
    
    protected function resizeLocalImage($location, $size, $maxWidth = - 1, $maxHeight = - 1)
    {
        
        if (!preg_match("/^x?[0-9]+$/", $size) && !preg_match("/^[0-9]+x[0-9]+[fsc]?$/", $size)) {
            throw new Exception("CRUD0603", $this->imageFileName, $size);
        }
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
                return $location;
            }
        }
        
        $dest = $this->getDestinationCacheImage($this->imageFileName, $size);
        if (file_exists($dest)) {
            return $dest;
        }
        
        $size = str_replace(array(
            "f",
            "s"
        ) , array(
            "",
            "!"
        ) , $size);
        
        if (preg_match("/^([0-9]+x[0-9]+)c$/", $this->size, $reg)) {
            $cmd = sprintf("convert  -resize %s^ -gravity center -extent %s %s %s", escapeshellarg($reg[1]) , escapeshellarg($reg[1]) , escapeshellarg($location) , escapeshellarg($dest));
        } else {
            $cmd = sprintf("convert  -resize %s %s %s", escapeshellarg($size) , escapeshellarg($location) , escapeshellarg($dest));
        }
        system($cmd);
        if (file_exists($dest)) return $dest;
        
        throw new Exception("CRUD0602", $this->imageFileName, $this->size);
    }
}
