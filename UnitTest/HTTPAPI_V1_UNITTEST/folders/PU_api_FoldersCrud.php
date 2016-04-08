<?php
/*
 * @author Anakeen
 * @package Dcp\Pu
*/

namespace Dcp\Pu\HttpApi\V1\Test\Folders;

use Dcp\HttpApi\V1\Crud\Folders as Folders;
use Dcp\HttpApi\V1\Crud\Exception as DocumentException;
use Dcp\Pu\HttpApi\V1\Test\Documents\TestDocumentsCollectionCrud;

require_once 'HTTPAPI_V1_UNITTEST/PU_TestCaseApi.php';

class TestFoldersCrud extends TestDocumentsCollectionCrud
{
    /**
     * Test that unable to create document
     *
     * @dataProvider dataCreateDocument
     */
    public function testCreate()
    {
        $crud = new Folders();
        try {
            $crud->create();
            $this->assertFalse(true, "An exception must occur");
        }
        catch(DocumentException $exception) {
            $this->assertEquals(405, $exception->getHttpStatus());
        }
    }
    
    public function dataCreateDocument()
    {
        return array(
            array(
                "NO"
            )
        );
    }
    /**
     * @param array $fields
     * @param array $expectedData
     * @dataProvider dataReadDocument
     */
    public function testRead($modifiers, $fields, $expectedData)
    {
        $crud = new Folders();
        $crud->setContentParameters($modifiers);
        if ($fields !== null) {
            $fieldsString = "";
            foreach ($fields as $currentFields) {
                $fieldsString.= "document.properties.$currentFields,";
            }
            $crud->setDefaultFields($fieldsString);
        }
        $data = $crud->read(null);
        
        $data = json_decode(json_encode($data) , true);
        
        $expectedData = $this->prepareData($expectedData);
        $this->verifyData($data, $expectedData);
        $this->checkProperties($data["documents"], $fields);
    }
    
    protected function checkProperties(Array $documents, array $propertiesName = array())
    {
        foreach ($documents as $currentDocument) {
            $this->assertArrayHasKey("properties", $currentDocument, "Unable to find the properties for" . var_export($currentDocument, true));
            $this->assertArrayHasKey("uri", $currentDocument, "Unable to find the uri for" . var_export($currentDocument, true));
            if (!empty($propertiesName)) {
                foreach ($propertiesName as $currentPropertyName) {
                    $this->assertArrayHasKey($currentPropertyName, $currentDocument["properties"], "Unable to find the properties $currentPropertyName for" . var_export($currentDocument, true));
                }
            }
        }
    }
    
    public function dataReadDocument()
    {
        $collection = file_get_contents("HTTPAPI_V1_UNITTEST/folders/folders.json");
        return array(
            array(
                array() ,
                array() ,
                $collection
            ) ,
            array(
                array() ,
                array(
                    "adate",
                    "owner",
                    "doctype"
                ) ,
                $collection
            ) ,
            array(
                array(
                    "orderBy" => "adate",
                    "slice" => "1",
                    "offset" => "1"
                ) ,
                array() ,
                file_get_contents("HTTPAPI_V1_UNITTEST/folders/folders.custom.json")
            )
        );
    }
    /**
     * Test that unable to update document
     *
     * @dataProvider dataUpdateDocument
     */
    public function testUpdateDocument($name, $updateValues, $expectedValues)
    {
        $crud = new Folders();
        try {
            $crud->update(null);
            $this->assertFalse(true, "An exception must occur");
        }
        catch(DocumentException $exception) {
            $this->assertEquals(405, $exception->getHttpStatus());
        }
    }
    
    public function dataUpdateDocument()
    {
        return array(
            array(
                null,
                null,
                array()
            )
        );
    }
    /**
     * Test that unable to update document
     *
     * @dataProvider dataDeleteDocument
     */
    public function testDeleteDocument($name, $fields, $expectedValues)
    {
        $crud = new Folders();
        try {
            $crud->delete(null);
            $this->assertFalse(true, "An exception must occur");
        }
        catch(DocumentException $exception) {
            $this->assertEquals(405, $exception->getHttpStatus());
        }
    }
    
    public function dataDeleteDocument()
    {
        return array(
            array(
                null,
                null,
                array()
            )
        );
    }
    public function prepareData($data)
    {
        $nbSearch = new \SearchDoc();
        $nbSearch->addFilter("doctype = 'D'");
        $nbSearch = $nbSearch->onlyCount();
        if ($nbSearch > 10) {
            $nbSearch = 10;
        }
        $data = str_replace('%nbFolder%', $nbSearch, $data);
        return parent::prepareData($data);
    }
}
