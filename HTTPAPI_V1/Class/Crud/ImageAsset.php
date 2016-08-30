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
     * Download resized image
     *
     * @param string $resourceId image filename
     * @throws Exception
     * @return mixed
     */
    public function read($resourceId)
    {
        $this->imageFileName = urldecode($resourceId);
        
        $location = $this->getSourceImage();
        
        if ($this->size !== null) {
            $dest = $this->getDestinationCacheImage($this->imageFileName, $this->size);
            
            if (!file_exists($dest)) {
                $outFile = FileUtils::resizeLocalImage($location, $dest, $this->size);
            } else {
                $outFile = $dest;
            }
        } else {
            $tsize = getimagesize($location);
            if (!$tsize) {
                throw new Exception("CRUD0601", $resourceId);
            }
            // original file
            $outFile = $location;
        }
        
        FileUtils::downloadFile($outFile, "", FileUtils::getMimeImage($outFile));
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
        $basedest = sprintf("%s/%s/Images_%s-%s", DEFAULT_PUBDIR, self::CACHEIMGDIR, $size, str_replace("/", "_", $localimage));
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
}
