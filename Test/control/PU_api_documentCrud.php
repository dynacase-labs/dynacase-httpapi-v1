<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package Dcp\Pu
*/

namespace Dcp\Pu\Api;

require_once 'APITEST/PU_TestCaseApi.php';

class TestDocumentCrud extends TestCaseApi
{
    const familyName = "TST_APIBASE";
    /**
     * import TST_APIBASE family
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
     * @param string $name
     * @param string $fields
     * @param array $expectedValues
     * @dataProvider datagetDocument
     */
    public function testGetDocument($name, $fields, array $expectedValues)
    {
        $doc = \Dcp\HttpApi\V1\DocManager::getDocument($name);
        $this->assertTrue($doc !== null, "Document $name not found");
        
        $dc = new \Dcp\HttpApi\V1\DocumentCrud();
        if ($fields !== null) {
            $dc->setDefaultFields($fields);
        }
        $data = $dc->get($name);
        
        $this->verifyData($data, $expectedValues, $doc);
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
                    "document.properties.fromtitle" => "Test Base",
                    "document.uri" => "api/v1/documents/{id}.json"
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
                            "displayValue" => "23.03 €"
                        ) ,
                        array(
                            "value" => 14.5,
                            "displayValue" => "14.5 €"
                        )
                    ) ,
                    "document.properties.title" => "Un deuxième élément",
                    "document.properties.name" => "TST_APIB2",
                    "document.properties.fromname" => "TST_APIBASE",
                    "document.properties.fromtitle" => "Test Base",
                    "document.uri" => "api/v1/documents/{id}.json"
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
                    "document.properties" => null,
                    "document.uri" => "api/v1/documents/{id}.json"
                )
            ) ,
            array(
                "TST_APIB1",
                "",
                array(
                    "document.attributes" => null,
                    "document.properties" => null,
                    "document.uri" => "api/v1/documents/{id}.json"
                )
            ) ,
            array(
                "TST_APIB1",
                "document.property.name,document.property.title",
                array(
                    "document.properties.title" => "Un élément",
                    "document.properties.name" => "TST_APIB1",
                    "document.properties.id" => null,
                    "document.uri" => "api/v1/documents/{id}.json"
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
                    "document.uri" => "api/v1/documents/{id}.json"
                )
            )
        );
    }
    
    protected function verifyData($data, $expectedValues, \Doc $document)
    {
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
            if (is_string($expectValue)) {
                $expectValue = str_replace('{id}', $document->id, $expectValue);
            }
            $this->assertEquals($expectValue, $cdata, sprintf("wrong value for $dkey :%s ", print_r($data, true)));
        }
    }
    /**
     * @param string $name
     * @param array $setValues
     * @param array $expectedValues
     * @dataProvider dataSetDocument
     */
    public function testSetDocument($name, array $setValues, array $expectedValues)
    {
        $doc = \Dcp\HttpApi\V1\DocManager::getDocument($name);
        $this->assertTrue($doc !== null, "Document $name not found");
        
        $this->simulatePostRecord($setValues, "POST");
        $dc = new \Dcp\HttpApi\V1\DocumentCrud();
        
        $data = $dc->update($name);
        
        foreach ($setValues as $aid => $value) {
            $this->assertFalse(empty($data["document"]["attributes"][$aid]) , sprintf("Undefined %s : Ss", $aid, print_r($data, true)));
            if (is_array($value)) {
                $values = $data["document"]["attributes"][$aid];
                foreach ($values as $k => $singleValue) {
                    $this->assertEquals($value[$k], $singleValue->value);
                }
            } else {
                $this->assertEquals($value, $data["document"]["attributes"][$aid]->value);
            }
        }
        $this->verifyData($data, $expectedValues, $doc);
    }
    
    public function dataSetDocument()
    {
        return array(
            array(
                "TST_APIB1",
                array(
                    "tst_title" => "test n°1",
                    "tst_number" => 56
                ) ,
                array(
                    "document.attributes.tst_title.value" => "test n°1",
                    "document.attributes.tst_title.displayValue" => "<strong>test n°1</strong>",
                    "document.attributes.tst_number.value" => 56,
                    "document.attributes.tst_number.displayValue" => "056",
                    "document.properties.title" => "test n°1",
                    "document.uri" => "api/v1/documents/{id}.json"
                )
            ) ,
            array(
                "TST_APIB2",
                array(
                    "tst_title" => "test n°2",
                    "tst_number" => 678,
                    "tst_text" => array(
                        "Un",
                        "Deux"
                    )
                ) ,
                array(
                    "document.attributes.tst_title.value" => "test n°2",
                    "document.attributes.tst_title.displayValue" => "<strong>test n°2</strong>",
                    "document.attributes.tst_number.value" => 678,
                    "document.attributes.tst_number.displayValue" => "678",
                    "document.properties.title" => "test n°2",
                    "document.uri" => "api/v1/documents/{id}.json",
                    "document.attributes.tst_text" => array(
                        array(
                            "value" => "Un",
                            "displayValue" => "Un"
                        ) ,
                        array(
                            "value" => "Deux",
                            "displayValue" => "Deux"
                        )
                    ) ,
                )
            )
        );
    }
    /**
     * @param $famName
     * @param array $setValues
     * @dataProvider dataCreateDocument
     */
    public function testCreateDocument(
    /** @noinspection PhpUnusedParameterInspection */
    $famName, array $setValues)
    {
        $this->simulatePostRecord($setValues, "POST");
        $dc = new \Dcp\HttpApi\V1\DocumentCrud();
        try {
            $dc->create();
            $this->assertFalse(true, "An exception must occur");
        }
        catch(\Dcp\HttpApi\V1\Exception $e) {
            $this->assertEquals(501, $e->getHttpStatus());
        }
    }
    
    public function dataCreateDocument()
    {
        return array(
            array(
                "TST_APIBASE",
                array(
                    "tst_title" => "test",
                    "tst_number" => 56
                )
            )
        );
    }
    /**
     * @param $name
     * @param array $expectedValues
     * @dataProvider dataDeleteDocument
     */
    public function testdeleteDocument($name, array $expectedValues)
    {
        self::resetDocumentCache();
        $doc = \Dcp\HttpApi\V1\DocManager::getDocument($name);
        $this->assertTrue($doc !== null, "Document $name not found");
        $this->assertTrue($doc->isAlive() , "Document $name is already deleted");
        
        $dc = new \Dcp\HttpApi\V1\DocumentCrud();
        
        $data = $dc->delete($name);
        $this->verifyData($data, $expectedValues, $doc);
        
        $dc = new \Dcp\HttpApi\V1\DocumentCrud();
        try {
            $dc->get($name);
            $this->assertFalse(true, "An exception must occur");
        }
        catch(\Dcp\HttpApi\V1\Exception $e) {
            $this->assertEquals(404, $e->getHttpStatus());
        }
    }
    public function dataDeleteDocument()
    {
        return array(
            array(
                "TST_APIB1",
                array(
                    "document.attributes.tst_title.value" => "Un élément",
                    "document.attributes.tst_title.displayValue" => "<strong>Un élément</strong>",
                    "document.properties.title" => "Un élément",
                    "document.properties.locked" => - 1,
                    "document.properties.doctype" => "Z",
                    "document.uri" => "api/v1/trash/{id}.json"
                )
            )
        );
    }
    protected function simulatePostRecord(array $values, $method)
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER["CONTENT_TYPE"] = "application/x-www-form-urlencoded";
        foreach ($values as $k => $v) {
            $_POST[$k] = $v;
        }
    }
}
