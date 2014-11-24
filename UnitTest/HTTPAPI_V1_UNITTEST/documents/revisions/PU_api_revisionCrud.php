<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package Dcp\Pu
*/

namespace Dcp\Pu\HttpApi\V1\Test\Documents;

use Dcp\HttpApi\V1\Crud\Revision as Revision;
use Dcp\HttpApi\V1\Crud\Exception as DocumentException;
use Dcp\HttpApi\V1\DocManager\DocManager;

require_once 'HTTPAPI_V1_UNITTEST/PU_TestCaseApi.php';

class TestRevision extends TestDocumentCrud
{
    /**
     * Test that unable to create document
     *
     * @dataProvider dataCreateDocument
     */
    public function testCreate()
    {
        $crud = new Revision();
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
     * @param string $fields
     * @param array $expectedData
     * @dataProvider dataReadDocument
     */
    public function testRead($name, $fields, array $expectedData)
    {
        $doc = DocManager::getDocument($name);
        $this->assertTrue($doc !== null, "Document $name not found");

        $crud = new Revision();
        $data = $crud->read($name);

        $data = json_decode(json_encode($data), true);

        $this->verifyData($data, $expectedData);
    }

    public function dataReadDocument()
    {
        $collection = file_get_contents("HTTPAPI_V1_UNITTEST/documents/revisions/revision.json");
        $collection = $this->prepareData($collection);
        return array(
            array(
                "TST_APIBASE_TEST_1",
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
    public function testUpdateDocument($name, $updateValues , array $expectedValues)
    {
        $crud = new Revision();
        try {
            $crud->update(null);
            $this->assertFalse(true, "An exception must occur");
        } catch (DocumentException $exception) {
            $this->assertEquals(405, $exception->getHttpStatus());
        }
    }

    public function dataUpdateDocument()
    {
        return array(array(
            null,
            null,
            array()
        ));
    }

    /**
     * Test that unable to update document
     *
     * @dataProvider dataDeleteDocument
     */
    public function testDeleteDocument($name, $fields, array $expectedValues)
    {
        $crud = new Revision();
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




}
