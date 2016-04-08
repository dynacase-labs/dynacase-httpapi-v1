<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

class Folders extends DocumentCollection
{
    
    protected function prepareSearchDoc()
    {
        $this->_searchDoc = new \SearchDoc();
        $this->_searchDoc->setObjectReturn();
        $this->_searchDoc->addFilter("doctype = 'D'");
    }
    
    public function generateURL($path, $query = null)
    {
        $path = str_replace("documents/", "folders/", $path);
        return parent::generateURL($path, $query);
    }
    
    protected function prepareDocumentFormatter($documentList)
    {
        $documentFormatter = parent::prepareDocumentFormatter($documentList);
        $documentFormatter->setGenerateURI(function ($document)
        {
            return URLUtils::generateURL("folders/{$document->initid}/documents/");
        });
        return $documentFormatter;
    }
    protected function getCollectionProperties()
    {
        return array(
            "title" => ___("The folders", "ddui")
        );
    }
}
