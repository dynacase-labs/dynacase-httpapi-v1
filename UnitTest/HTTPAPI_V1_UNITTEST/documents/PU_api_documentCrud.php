<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package Dcp\Pu
*/

namespace Dcp\Pu\HttpApi\V1\Test\Documents;

use Dcp\Pu\HttpApi\V1\Test\TestCaseApi;
use Dcp\HttpApi\V1\Api\AnalyzeURL;
use Dcp\HttpApi\V1\DocManager\DocManager;
use Dcp\HttpApi\V1\Crud\Document as DocumentCrud;
use Dcp\HttpApi\V1\Crud\Exception as DocumentException;

require_once 'HTTPAPI_V1_UNITTEST/PU_TestCaseApi.php';

class TestDocumentCrud extends TestCaseApi
{

    /**
     * import TST_APIBASE family
     * @static
     * @return string
     */
    protected static function getCommonImportFile()
    {
        $import = array();
        $import[] = "PU_api_crudDocument_documents.csv";
        return $import;
    }


    /**
     * Test that unable to create document
     *
     * @dataProvider dataCreateDocument
     */
    public function testCreate()
    {
        $documentCrud = new DocumentCrud();
        try {
            $documentCrud->create();
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

        $documentCrud = new DocumentCrud();
        if ($fields !== null) {
            $documentCrud->setDefaultFields($fields);
        }
        $data = $documentCrud->read($name);

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

        $documentCrud = new DocumentCrud();
        $documentCrud->setContentParameters($documentCrud->analyseJSON($updateValues));
        $data = $documentCrud->update($name);

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

        $documentCrud = new DocumentCrud();
        if ($fields !== null) {
            $documentCrud->setDefaultFields($fields);
        }
        $data = $documentCrud->delete($name);

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

    public function prepareData($data)
    {
        //Get RefDoc

        $adminDoc = DocManager::getDocument("USER_ADMIN");
        $this->assertNotNull($adminDoc, "Unable to find admin doc");
        $guestDoc = DocManager::getDocument("USER_GUEST");
        $this->assertNotNull($guestDoc, "Unable to find guest doc");
        $documentTest1 = DocManager::getDocument("TST_APIBASE_TEST_1");
        $this->assertNotNull($documentTest1, "Unable to find document 1");
        $updated = DocManager::getDocument("TST_APIBASE_UPDATED");
        $this->assertNotNull($updated, "Unable to find document updated");

        //Replace variant part
        $data = str_replace('%baseURL%', AnalyzeURL::getBaseURL(), $data);
        $data = str_replace('%test1Initid%', $documentTest1->getPropertyValue('initid'), $data);
        $data = str_replace('%test1Id%', $documentTest1->getPropertyValue('id'), $data);
        $data = str_replace('%updatedInitid%', $updated->getPropertyValue('initid'), $data);
        $data = str_replace('%updatedId%', $updated->getPropertyValue('id'), $data);
        $data = str_replace('%anonymousGestId%', $guestDoc->getPropertyValue('id'), $data);
        $data = str_replace('%masterDefaultId%', $adminDoc->getPropertyValue('id'), $data);

        $data = json_decode($data, true);

        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), "Unable to decode the test data");

        return $data;

    }


}
