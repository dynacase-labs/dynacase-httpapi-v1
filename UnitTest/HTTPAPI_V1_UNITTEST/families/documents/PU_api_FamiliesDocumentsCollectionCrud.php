<?php
/*
 * @author Anakeen
 * @package Dcp\Pu
*/

namespace Dcp\Pu\HttpApi\V1\Test\Families;

use Dcp\HttpApi\V1\Crud\FamilyDocumentCollection as FamilyDocumentCollection;
use Dcp\HttpApi\V1\Crud\Exception as DocumentException;

require_once 'HTTPAPI_V1_UNITTEST/PU_TestCaseApi.php';

class TestFamilyDocumentCollection extends \Dcp\Pu\HttpApi\V1\Test\Documents\TestDocumentsCollectionCrud
{
    public function testCreate()
    {
        // Nothing
        
    }
    /**
     * Test that unable to create document
     *
     * @dataProvider dataCreateDocument
     * @param $content
     * @param $values
     * @throws DocumentException
     * @throws \Dcp\HttpApi\V1\DocManager\Exception
     * @throws \Exception
     */
    public function testFamilyCreate($content, $values)
    {
        $crud = new FamilyDocumentCollection();
        $crud->setUrlParameters(array(
            "familyId" => "TST_APIBASE"
        ));
        $crud->setContentParameters($crud->analyseJSON($content));
        $data = $crud->create();
        
        $data = json_decode(json_encode($data) , true);
        
        $expectedValues = $this->prepareData($values);
        $this->verifyData($data, $expectedValues);
    }
    
    public function dataCreateDocument()
    {
        return array(
            array(
                file_get_contents("HTTPAPI_V1_UNITTEST/families/documents/TST_APIBASE.create.json") ,
                file_get_contents("HTTPAPI_V1_UNITTEST/families/documents/TST_API_BASE.created.json")
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
        $crud = new FamilyDocumentCollection();
        $crud->setContentParameters($modifiers);
        $crud->setUrlParameters(array(
            "familyId" => "TST_APIBASE"
        ));
        if (!empty($fields)) {
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
        $collection = file_get_contents("HTTPAPI_V1_UNITTEST/families/documents/get_TST_API_BASE_collection.json");
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
        $crud = new FamilyDocumentCollection();
        $crud->setUrlParameters(array(
            "familyId" => "TST_APIBASE"
        ));
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
        $crud = new FamilyDocumentCollection();
        $crud->setUrlParameters(array(
            "familyId" => "TST_APIBASE"
        ));
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
}
