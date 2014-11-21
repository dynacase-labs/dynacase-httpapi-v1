<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
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

    protected function prepareDocumentFormatter($documentList) {
        $documentFormatter = parent::prepareDocumentFormatter($documentList);
        $documentFormatter->setGenerateURI(function($document) {
            return URLUtils::generateURL("searches/{$document->initid}/");
        });
        return $documentFormatter;
    }
} 