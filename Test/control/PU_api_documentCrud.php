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
    public function testGetDocument($name, $fields, $expectedValues)
    {
        $doc = \Dcp\DocManager::getDocument($name);
        $this->assertTrue($doc !== null, "Document $name not found");
        
        $dc = new \Dcp\HttpApi\V1\DocumentCrud();
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
    
    public function datagetDocument()
    {
        return array(
            array(
                "TST_APIB1",
                null,
                array(
                    "document.attributes.tst_title.value" => "Un élément",
                    "document.attributes.tst_title.displayValue" => "<strong>Un élément</strong>",
                    "document.attributes.tst_number.value" => 23,
                    "document.attributes.tst_number.displayValue" => "023",
                    "document.properties.title" => "Un élément",
                    "document.properties.name" => "TST_APIB1",
                    "document.properties.fromname" => "TST_APIBASE",
                    "document.properties.fromtitle" => "Test Base"
                )
            ) ,
            array(
                "TST_APIB2",
                null,
                array(
                    "document.attributes.tst_title.value" => "Un deuxième élément",
                    "document.attributes.tst_title.displayValue" => "<strong>Un deuxième élément</strong>",
                    "document.attributes.tst_number.value" => 0,
                    "document.attributes.tst_number.displayValue" => "000",
                    "document.attributes.tst_money" => array(
                        array(
                            "value" => 23.03,
                            "displayValue" => "23,03 €"
                        ) ,
                        array(
                            "value" => 14.5,
                            "displayValue" => "14,5 €"
                        )
                    ) ,
                    "document.properties.title" => "Un deuxième élément",
                    "document.properties.name" => "TST_APIB2",
                    "document.properties.fromname" => "TST_APIBASE",
                    "document.properties.fromtitle" => "Test Base"
                )
            ) ,
            array(
                "TST_APIB1",
                "document.attributes",
                array(
                    "document.attributes.tst_title.value" => "Un élément",
                    "document.attributes.tst_title.displayValue" => "<strong>Un élément</strong>",
                    "document.attributes.tst_number.value" => 23,
                    "document.attributes.tst_number.displayValue" => "023",
                    "document.properties" => null
                )
            ) ,
            array(
                "TST_APIB1",
                "",
                array(
                    "document.attributes" => null,
                    "document.properties" => null
                )
            ) ,
            array(
                "TST_APIB1",
                "document.property.name,document.property.title",
                array(
                    "document.properties.title" => "Un élément",
                    "document.properties.name" => "TST_APIB1",
                    "document.properties.id" => null
                )
            ) ,
            array(
                "TST_APIB2",
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
            ) ,
        );
    }
}
