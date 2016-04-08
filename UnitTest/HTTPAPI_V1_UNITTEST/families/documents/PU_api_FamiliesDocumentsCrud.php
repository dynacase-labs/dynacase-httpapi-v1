<?php
/*
 * @author Anakeen
 * @package Dcp\Pu
*/

namespace Dcp\Pu\HttpApi\V1\Test\Families;

use Dcp\Pu\HttpApi\V1\Test\Documents\TestDocumentCrud;
use Dcp\HttpApi\V1\DocManager\DocManager;
use Dcp\HttpApi\V1\Crud\FamilyDocument as FamilyCrud;
use Dcp\HttpApi\V1\Crud\Exception as DocumentException;

require_once 'HTTPAPI_V1_UNITTEST/PU_TestCaseApi.php';

class TestFamilyDocumentCrud extends TestDocumentCrud
{

    /**
     * Test that unable to create document
     *
     * @dataProvider dataCreateDocument
     */
    public function testCreate()
    {
        $crud = new FamilyCrud();
        try {
            $crud->create();
            $this->assertFalse(true, "An exception must occur");
        } catch (DocumentException $exception) {
            $this->assertEquals(405, $exception->getHttpStatus());
        }
    }

    public function dataCreateDocument()
    {
        return array(array(
            "NO"
        ));
    }

    /**
     * @param string $name
     * @param string $fields
     * @param array $expectedData
     * @dataProvider dataReadDocument
     */
    public function testRead($name, $fields, $expectedData)
    {
        $doc = DocManager::getDocument($name);
        $this->assertTrue($doc !== null, "Document $name not found");

        $crud = new FamilyCrud();
        $crud->setUrlParameters(array("familyId" => "TST_APIBASE"));
        if ($fields !== null) {
            $crud->setDefaultFields($fields);
        }
        $data = $crud->read($name);

        $data = json_decode(json_encode($data), true);

        $expectedData = $this->prepareData($expectedData);
        $this->verifyData($data, $expectedData);
    }

    public function dataReadDocument()
    {
        $document1 = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_TEST_1.json");
        $properties = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_TEST_1.properties.json");
        $propertiesAll = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_TEST_1.properties.all.json");
        $structure = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_TEST_1.structure.json");
        return array(
            array(
                "TST_APIBASE_TEST_1",
                null,
                $document1
            ),
            array(
                "TST_APIBASE_TEST_1",
                "document.properties",
                $properties
            ),
            array(
                "TST_APIBASE_TEST_1",
                "document.properties.all",
                $propertiesAll
            ),
            array(
                "TST_APIBASE_TEST_1",
                "family.structure",
                $structure
            )
        );
    }

    /**
     * @param string $name
     * @param array $updateValues
     * @param array $expectedValues
     *
     * @throws DocumentException
     *
     * @dataProvider dataUpdateDocument
     */
    public function testUpdateDocument($name, $updateValues, $expectedValues)
    {
        $doc = DocManager::getDocument($name);
        $this->assertTrue($doc !== null, "Document $name not found");

        $crud = new FamilyCrud();
        $crud->setUrlParameters(array("familyId" => "TST_APIBASE"));
        $crud->setContentParameters($crud->analyseJSON($updateValues));
        $data = $crud->update($name);

        $data = json_decode(json_encode($data), true);

        $expectedValues = $this->prepareData($expectedValues);
        $this->verifyData($data, $expectedValues);
    }

    public function dataUpdateDocument()
    {
        $updateValues = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_UPDATED.updateValues.json");
        $updatedDocument = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_UPDATED.updated.json");
        return array(
            array(
                "TST_APIBASE_UPDATED",
                $updateValues,
                $updatedDocument
            )
        );
    }

    /**
     * @param string $name
     * @param string $fields
     * @param string $expectedValues
     *
     * @throws DocumentException
     *
     * @dataProvider dataDeleteDocument
     */
    public function testDeleteDocument($name, $fields, $expectedValues)
    {
        $doc = DocManager::getDocument($name);
        $this->assertTrue($doc !== null, "Document $name not found");

        $crud = new FamilyCrud();
        $crud->setUrlParameters(array("familyId" => "TST_APIBASE"));
        if ($fields !== null) {
            $crud->setDefaultFields($fields);
        }
        $data = $crud->delete($name);

        $data = json_decode(json_encode($data), true);

        $expectedValues = $this->prepareData($expectedValues);
        $this->verifyData($data, $expectedValues);
    }

    public function dataDeleteDocument()
    {
        $document = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_TEST.deleted.json");
        return array(
            array(
                "TST_APIBASE_TEST_DELETED",
                "document.properties.doctype,document.properties.name",
                $document
            )
        );
    }



}
