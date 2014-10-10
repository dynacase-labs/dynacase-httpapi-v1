<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

use Dcp\HttpApi\V1\DocManager;

class FamilyDocumentCrud extends DocumentCrud
{
    /**
     * @var \DocFam
     */
    protected $_family = null;
    public function __construct($familyId)
    {
        $this->_family = DocManager::getFamily($familyId);
        if ($this->_family === null) {
            $exception = new Exception("API0207", $familyId);
            $exception->setHttpStatus(404, "Family not found");
            throw $exception;
        }
    }
}
