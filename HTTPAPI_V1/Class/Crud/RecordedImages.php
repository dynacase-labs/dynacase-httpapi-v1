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
        $vaultId = $this->imageFileName;
        $location = FileUtils::getVaultPath($vaultId, true);
        
        if (!$location || !file_exists($location)) {
            throw new Exception("CRUD0600", $vaultId);
        }
        
        if (!$this->size && !empty($this->urlParameters["extension"]) && basename($location) !== sprintf("%s%s", $vaultId, $this->urlParameters["extension"])) {
            throw new Exception("CRUD0604", $vaultId, $this->urlParameters["extension"]);
        }
        
        return $location;
    }
    
    protected function getDestinationCacheImage($localimage, $size)
    {
        
        $fileExtension = $this->urlParameters["extension"];
        $basedest = sprintf("%s/%s/%s-vid%s%s", DEFAULT_PUBDIR, self::CACHEIMGDIR, $size, str_replace("/", "_", $localimage) , $fileExtension);
        
        return $basedest;
    }
}
