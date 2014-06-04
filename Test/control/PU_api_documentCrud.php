<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\Pu\Api;
/**
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package Dcp\Pu
 */

require_once 'PU_testcase_dcp_commonfamily.php';

class TestDocumentCrud extends \Dcp\Pu\TestCaseDcpCommonFamily
{
    const familyName = "TSTAPI_DOCCRUD";
    /**
     * import TST_DOCENUM family
     * @static
     * @return string
     */
    protected static function getCommonImportFile()
    {
        return array(
            "PU_api_crudDocument.csv"
        );
    }
    /**
     * @param $name
     * @param $expectedValues
     * @dataProvider datagetDocument
     */
    public function testGetDocument($name, $expectedValues)
    {
        $doc = \Dcp\DocManager::getDocument($name);
        $this->assertTrue($doc !== null, "Document $name not found");
    }
    
    public function datagetDocument()
    {
        return array(
            array(
                "TST_APIB1",
                array(
                    "tst_title" => "Un élément",
                    "tst_number" => 23
                )
            )
        );
    }
}
