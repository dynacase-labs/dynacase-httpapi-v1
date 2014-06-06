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

require_once 'APITEST/PU_TestCaseApi.php';

class TestFamilyCrud extends TestCaseApi
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
            "PU_api_crudFamily.csv"
        );
    }
    /**
     * @param string $name
     * @param string $fields
     * @param array $expectedValues
     * @dataProvider datagetFamily
     */
    public function testgetFamily($name, $fields, array $expectedValues)
    {
        $doc = \Dcp\DocManager::getFamily($name);
        $this->assertTrue($doc !== null, "Family $name not found");
        
        $dc = new \Dcp\HttpApi\V1\FamilyCrud();
        if ($fields !== null) {
            $dc->setDefaultFields($fields);
        }
        $data = $dc->get($name);
        
        foreach ($expectedValues as $dkey => $expectValue) {
            $keys = explode(".", $dkey);
            $cdata = $data;
            foreach ($keys as $key) {
                if ($expectValue !== null) {
                    $this->assertTrue(isset($cdata[$key]) , sprintf("key \"%s\" not found %s", $key, print_r($cdata, true)));
                    $cdata = $cdata[$key];
                    if (is_object($cdata)) {
                        $cdata = get_object_vars($cdata);
                    } elseif (is_array($cdata)) {
                        
                        foreach ($cdata as $k => $v) {
                            if (is_object($v)) {
                                $cdata[$k] = get_object_vars($v);
                            }
                        }
                    }
                } else {
                    if (isset($cdata[$key])) {
                        $cdata = $cdata[$key];
                    } else {
                        $cdata = null;
                        break;
                    }
                }
            }
            $this->assertEquals($expectValue, $cdata, sprintf("wrong value for $dkey :%s ", print_r($data, true)));
        }
    }
    /**
     * @param string $name
     * @dataProvider dataUpdateFamily
     */
    public function testUpdateFamily($name)
    {
        $dc = new \Dcp\HttpApi\V1\FamilyCrud();
        try {
            $dc->update($name);
            $this->assertFalse(true, "An exception must occur");
        }
        catch(\Dcp\HttpApi\V1\Exception $e) {
            $this->assertEquals(501, $e->getHttpStatus());
        }
    }
    /**
     * @param $famName
     * @param array $setValues
     * @dataProvider dataCreateDocument
     */
    public function testCreateDocument($famName, array $setValues)
    {
        $this->simulatePostRecord($setValues, "POST", $famName);
        $dc = new \Dcp\HttpApi\V1\FamilyCrud();
        
        $data = $dc->create();
        
        foreach ($setValues as $aid => $value) {
            $this->assertFalse(empty($data["document"]["attributes"][$aid]) , sprintf("Undefined %s : Ss", $aid, print_r($data, true)));
            if (is_array($value)) {
                $values = $data["document"]["attributes"][$aid];
                
                foreach ($values as $k => $singleValue) {
                    $this->assertEquals($value[$k], $singleValue->value, "No good value for $aid [$k]");
                }
            } else {
                $this->assertEquals($value, $data["document"]["attributes"][$aid]->value, "No good value for $aid");
            }
        }
    }
    protected function simulatePostRecord(array $values, $method, $ressourceId)
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER["CONTENT_TYPE"] = "application/x-www-form-urlencoded";
        $_GET["id"] = $ressourceId;
        foreach ($values as $k => $v) {
            $_POST[$k] = $v;
        }
    }
    
    public function dataUpdateFamily()
    {
        return array(
            array(
                "TST_APIFAMILY"
            )
        );
    }
    public function dataCreateDocument()
    {
        return array(
            array(
                "TST_APIFAMILY",
                array(
                    "tst_title" => "test n°1",
                    "tst_number" => 56
                )
            ) ,
            array(
                "TST_APIFAMILY",
                array(
                    "tst_title" => "test n°2",
                    "tst_number" => 678,
                    "tst_text" => array(
                        "Un",
                        "Deux"
                    )
                )
            )
        );
    }
    public function datagetFamily()
    {
        return array(
            array(
                "TST_APIFAMILY",
                null,
                array(
                    "document.properties.title" => "Test Base",
                    "document.properties.name" => "TST_APIFAMILY",
                    "document.attributes" => array()
                )
            ) ,
            array(
                "TST_APIFAMILY",
                "family.structure",
                array(
                    "family.structure.tst_tab_info.id" => "tst_tab_info",
                    "family.structure.tst_tab_info.type" => "tab",
                    "family.structure.tst_tab_info.content.tst_fr_info.id" => "tst_fr_info",
                    "family.structure.tst_tab_info.content.tst_fr_info.type" => "frame",
                    "family.structure.tst_tab_info.content.tst_fr_info.visibility" => "W",
                    "family.structure.tst_tab_info.content.tst_fr_info.content.tst_title.id" => "tst_title",
                    "family.structure.tst_tab_info.content.tst_fr_info.content.tst_title.type" => "text",
                    "family.structure.tst_tab_info.content.tst_fr_info.content.tst_number.id" => "tst_number",
                    "family.structure.tst_tab_info.content.tst_fr_info.content.tst_number.type" => "int",
                )
            )
        );
    }
}
