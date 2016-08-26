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
    protected $_document = null;  /**
     * @var \DocFam
     */
    protected $_family = null;
    protected $size;
    protected $imageFileName;
    protected $inline=true;
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
        $size = isset($this->urlParameters["size"]) ? $this->urlParameters["size"] : null;
        $fileInfo=$this->getFileInfo($resourceId);
        $destination=$this->getDestinationCacheImage($fileInfo->id_file, $size);
        if (file_exists($destination)) {
            $outFile=$destination;
        } else {
            $outFile = UtilImage::resizeLocalImage($fileInfo->path, $destination, $size);
        }

        $fileName=sprintf("%s-%s", $size, $fileInfo->name);
        $fileExtension = $this->urlParameters["extension"];
        if ($fileExtension) {
            $fileName=substr($fileName, 0,strrpos($fileName, '.'));
            $fileName.=$fileExtension;
        }

        UtilImage::downloadFile($outFile, $fileName, $this->inline);
    }

    protected function getDestinationCacheImage($localimage, $size)
    {

        if (empty($this->urlParameters["extension"])) {
            $fileExtension=".png";
        } else {
            $fileExtension=$this->urlParameters["extension"];
        }
        $basedest = sprintf("%s/%s/%s-vid-%s%s", DEFAULT_PUBDIR, self::CACHEIMGDIR, $size, str_replace("/", "_", $localimage) , $fileExtension);

        return $basedest;
    }


}
