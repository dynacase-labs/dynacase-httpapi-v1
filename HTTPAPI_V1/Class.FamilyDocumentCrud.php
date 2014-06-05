<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

class FamilyDocumentCrud extends DocumentCrud
{
    /**
     * @var \DocFam
     */
    protected $_family = null;
    public function __construct($familyId)
    {
        $this->_family = \Dcp\DocManager::getFamily($familyId);
        if ($this->_family === null) {
            $e = new Exception("API0207", $familyId);
            $e->setHttpStatus(404, "Family not found");
            throw $e;
        }
    }
}
