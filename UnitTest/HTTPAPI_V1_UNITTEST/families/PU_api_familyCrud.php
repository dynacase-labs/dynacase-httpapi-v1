<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\Pu\HttpApi\V1\Test\Families;

use Dcp\HttpApi\V1\Api\AnalyzeURL;
use Dcp\HttpApi\V1\Crud\Exception as DocumentException;
use Dcp\HttpApi\V1\DocManager\DocManager;
use Dcp\HttpApi\V1\Crud\Family as Family;
use Dcp\Pu\HttpApi\V1\Test\Documents\TestDocumentCrud;

require_once 'HTTPAPI_V1_UNITTEST/PU_TestCaseApi.php';

class TestFamilyCrud extends TestDocumentCrud
{
    /**
     * Test that unable to create document
     *
     * @dataProvider dataCreateDocument
     */
    public function testCreate()
    {
        $crud = new Family();
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
     * @throws DocumentException
     * @dataProvider dataReadDocument
     */
    public function testRead($name, $fields, $expectedData)
    {
        $doc = DocManager::getDocument($name);
        $this->assertTrue($doc !== null, "Document $name not found");

        $crud = new Family();
        $data = $crud->read($name);

        $data = json_decode(json_encode($data), true);

        $expectedData = $this->prepareData($expectedData);
        $this->verifyData($data, $expectedData);
    }

    public function dataReadDocument()
    {
        $collection = file_get_contents("HTTPAPI_V1_UNITTEST/families/TST_APIBASE.json");
        return array(
            array(
                "TST_APIBASE",
                null,
                $collection
            )
        );
    }

    /**
     * Test that unable to update document
     *
     * @dataProvider dataUpdateDocument
     * @param string $name
     * @param array $updateValues
     * @param array $expectedValues
     */
    public function testUpdateDocument($name, $updateValues, $expectedValues)
    {
        $crud = new Family();
        try {
            $crud->update($name);
            $this->assertFalse(true, "An exception must occur");
        } catch (DocumentException $exception) {
            $this->assertEquals(405, $exception->getHttpStatus());
        }
    }

    public function dataUpdateDocument()
    {
        return array(array(
            "TST_APIBASE",
            null,
            array()
        ));
    }

    /**
     * Test that unable to update document
     *
     * @dataProvider dataDeleteDocument
     * @param string $name
     * @param string $fields
     * @param array $expectedValues
     */
    public function testDeleteDocument($name, $fields, $expectedValues)
    {
        $crud = new Family();
        try {
            $crud->delete(null);
            $this->assertFalse(true, "An exception must occur");
        } catch (DocumentException $exception) {
            $this->assertEquals(405, $exception->getHttpStatus());
        }
    }

    public function dataDeleteDocument()
    {
        return array(array(
            null,
            null,
            array()
        ));
    }

    public function prepareData($data) {
        //Get RefDoc
        $familyDoc = DocManager::getDocument("TST_APIBASE");
        $this->assertNotNull($familyDoc, "Unable to find family TST_APIBASE doc");

        //Replace variant part
        $data = str_replace('%baseURL%', AnalyzeURL::getBaseURL(), $data);
        $data = str_replace('%initId%', $familyDoc->getPropertyValue('initid'), $data);
        $data = str_replace('%id%', $familyDoc->getPropertyValue('id'), $data);

        $data = json_decode($data, true);

        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), "Unable to decode the test data");

        return $data;
    }
}
