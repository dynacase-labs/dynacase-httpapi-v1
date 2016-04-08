<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

class Searches extends DocumentCollection
{
    
    protected function prepareSearchDoc()
    {
        $this->_searchDoc = new \SearchDoc();
        $this->_searchDoc->setObjectReturn();
        $this->_searchDoc->addFilter("doctype = 'S'");
    }
    
    public function generateURL($path, $query = null)
    {
        $path = str_replace("documents/", "searches/", $path);
        
        return parent::generateURL($path, $query);
    }
    
    protected function prepareDocumentFormatter($documentList)
    {
        $documentFormatter = parent::prepareDocumentFormatter($documentList);
        $documentFormatter->setGenerateURI(function ($document)
        {
            return URLUtils::generateURL("searches/{$document->initid}/documents/");
        });
        return $documentFormatter;
    }
}
