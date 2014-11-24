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
    const familyName = "TST_APIBASE";

    /**
     * import TST_APIBASE family
     * @static
     * @return string
     */
    protected static function getCommonImportFile()
    {
        return array(
            "documents/PU_api_crudDocument_family.csv",
            "documents/PU_api_crudDocument_documents.csv"
        );
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

    public function dataCreateDocument() {
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
    public function testRead($name, $fields, array $expectedData)
    {
        $doc = DocManager::getDocument($name);
        $this->assertTrue($doc !== null, "Document $name not found");

        $documentCrud = new DocumentCrud();
        if ($fields !== null) {
            $documentCrud->setDefaultFields($fields);
        }
        $data = $documentCrud->read($name);

        $data = json_decode(json_encode($data), true);

        $this->verifyData($data, $expectedData);
    }

    public function dataReadDocument()
    {
        $document1 = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_TEST_1.json");
        $document1 = $this->prepareData($document1);
        $document2 = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_TEST_2.json");
        $document2 = $this->prepareData($document2);
        $properties = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_TEST_1.properties.json");
        $properties = $this->prepareData($properties);
        $propertiesAll = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_TEST_1.properties.all.json");
        $propertiesAll = $this->prepareData($propertiesAll);
        $structure = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_TEST_1.structure.json");
        $structure = $this->prepareData($structure);
        return array(
            array(
                $document1["document"]["properties"]["name"],
                null,
                $document1
            ),
            array(
                $document2["document"]["properties"]["name"],
                null,
                $document2
            ),
            array(
                $properties["document"]["properties"]["name"],
                "document.properties",
                $properties
            ),
            array(
                $propertiesAll["document"]["properties"]["name"],
                "document.properties.all",
                $propertiesAll
            ),
            array(
                $propertiesAll["document"]["properties"]["name"],
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
    public function testUpdateDocument($name, $updateValues, array $expectedValues) {
        $doc = DocManager::getDocument($name);
        $this->assertTrue($doc !== null, "Document $name not found");

        $documentCrud = new DocumentCrud();
        $documentCrud->setContentParameters($documentCrud->analyseJSON($updateValues));
        $data = $documentCrud->update($name);

        $data = json_decode(json_encode($data), true);

        $this->verifyData($data, $expectedValues);
    }

    public function dataUpdateDocument()
    {
        $updateValues = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_TEST_1.updateValues.json");
        $updatedDocument = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_TEST_1.updated.json");
        $updatedDocument = $this->prepareData($updatedDocument);
        return array(
            array(
                $updatedDocument["document"]["properties"]["name"],
                $updateValues,
                $updatedDocument
            )
        );
    }

    /**
     * @param string $name
     * @param string $fields
     * @param array $expectedValues
     *
     * @throws DocumentException
     *
     * @dataProvider dataDeleteDocument
     */
    public function testDeleteDocument($name, $fields, array $expectedValues)
    {
        $doc = DocManager::getDocument($name);
        $this->assertTrue($doc !== null, "Document $name not found");

        $documentCrud = new DocumentCrud();
        if ($fields !== null) {
            $documentCrud->setDefaultFields($fields);
        }
        $data = $documentCrud->delete($name);

        $data = json_decode(json_encode($data), true);

        $this->verifyData($data, $expectedValues);
    }

    public function dataDeleteDocument()
    {
        $document = file_get_contents("HTTPAPI_V1_UNITTEST/documents/TST_APIBASE_TEST.deleted.json");
        $document = $this->prepareData($document);
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
        $guestDoc = DocManager::getDocument("USER_GUEST");
        $documentTest1 = DocManager::getDocument("TST_APIBASE_TEST_1");
        $documentTest2 = DocManager::getDocument("TST_APIBASE_TEST_2");

        //Replace variant part
        $data = str_replace('%baseURL%', AnalyzeURL::getBaseURL(), $data);
        $data = str_replace('%test1Initid%', $documentTest1->getPropertyValue('initid'), $data);
        $data = str_replace('%test1Id%', $documentTest1->getPropertyValue('id'), $data);
        $data = str_replace('%test2Initid%', $documentTest2->getPropertyValue('initid'), $data);
        $data = str_replace('%test2Id%', $documentTest2->getPropertyValue('id'), $data);
        $data = str_replace('%anonymousGestId%', $guestDoc->getPropertyValue('id'), $data);
        $data = str_replace('%masterDefaultId%', $adminDoc->getPropertyValue('id'), $data);

        $data = json_decode($data, true);

        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), "Unable to decode the test data");

        return $data;

    }

    protected function verifyData($data, $expectedValues, $keys = "")
    {
        foreach ($expectedValues as $currentKey => $expectedValue) {
            $this->assertArrayHasKey($currentKey, $data, "Unable to find the key $currentKey for $keys [api result : " . var_export($data, true) . " ] // [expected : " . var_export($expectedValues, true) . " ]");
            if (is_array($expectedValue)) {
                $nextKey = $keys . (empty($keys) ? $currentKey : ".$currentKey");
                $this->verifyData($data[$currentKey], $expectedValue, $nextKey);
            } else {
                $this->assertEquals($expectedValue, $data[$currentKey], "wrong value for $currentKey ($keys)  [api result : " . var_export($data[$currentKey], true) . " ] // [expected : " . var_export($expectedValue, true) . " ]");
            }
        }
    }

}
