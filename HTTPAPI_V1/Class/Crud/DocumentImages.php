<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

class DocumentImage extends DocumentFile
{
    
    const CACHEIMGDIR = "var/cache/file/";
    /**
     * @var \Doc
     */
    protected $_document = null;
    
    protected $inline = true;
    /**
     * @var \vaultFileInfo
     */
    protected $fileInfo = null;
    //region CRUD part
    
    
    /**
     * Download resized image from document image attribute
     *
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function read($resourceId)
    {
        $size = $this->urlParameters["size"];
        if (!$this->fileInfo) {
            $this->fileInfo = $this->getFileInfo($resourceId);
        }
        $destination = $this->getDestinationCacheImage($this->fileInfo->id_file, $size);
        
        if (file_exists($destination)) {
            $outFile = $destination;
        } else {
            $outFile = FileUtils::resizeLocalImage($this->fileInfo->path, $destination, $size);
        }
        
        $fileName = sprintf("%s-%s", $size, $this->fileInfo->name);
        $fileExtension = $this->urlParameters["extension"];
        $mime = "image/png";
        if ($fileExtension) {
            $fileName = substr($fileName, 0, strrpos($fileName, '.'));
            $fileName.= $fileExtension;
            switch ($fileExtension) {
                case ".jpg":
                    $mime = "image/jpeg";
                    break;

                default:
                    $mime = "image/" . substr($fileExtension, 1);
            }
        }
        \Dcp\HttpApi\V1\Etag\Manager::setEtagHeaders();
        FileUtils::downloadFile($outFile, $fileName, $mime, $this->inline, false);
        exit;
    }
    
    protected function getDestinationCacheImage($localimage, $size)
    {
        if (empty($this->urlParameters["extension"])) {
            $fileExtension = ".png";
        } else {
            $fileExtension = $this->urlParameters["extension"];
        }
        $basedest = sprintf("%s/%s/%s-vid-%s%s", DEFAULT_PUBDIR, self::CACHEIMGDIR, $size, str_replace("/", "_", $localimage) , $fileExtension);
        
        return $basedest;
    }
    
    public function getEtagInfo()
    {
        $this->fileInfo = $this->getFileInfo($this->urlParameters["identifier"]);
        if ($this->fileInfo) {
            
            return $this->fileInfo->mdate;
        }
        return null;
    }
}
