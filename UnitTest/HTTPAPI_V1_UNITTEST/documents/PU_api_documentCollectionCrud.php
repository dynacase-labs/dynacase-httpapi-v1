<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package Dcp\Pu
*/

namespace Dcp\Pu\HttpApi\V1\Test\Documents;

use Dcp\HttpApi\V1\Crud\DocumentCollection as DocumentCollection;
use Dcp\HttpApi\V1\Crud\Exception as DocumentException;
use Dcp\HttpApi\V1\Crud\Exception;
use Dcp\HttpApi\V1\DocManager\DocManager;

require_once 'HTTPAPI_V1_UNITTEST/PU_TestCaseApi.php';

class TestDocumentsCollectionCrud extends TestDocumentCrud
{
    /**
     * Test that unable to create document
     *
     * @dataProvider dataCreateDocument
     */
    public function testCreate()
    {
        $crud = new DocumentCollection();
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
    public function testRead($fields, $expectedData)
    {
        $crud = new DocumentCollection();
        if ($fields !== null) {
            $crud->setDefaultFields($fields);
        }
        $data = $crud->read(null);

        $data = json_decode(json_encode($data), true);

        $expectedData = $this->prepareData($expectedData);
        $this->verifyData($data, $expectedData);
    }

    public function dataReadDocument()
    {
        $collection = file_get_contents("HTTPAPI_V1_UNITTEST/documents/get_collection.json");
        return array(
            array(
                null,
                $collection
            )
        );
    }

    /**
     * Test that unable to update document
     *
     * @dataProvider dataUpdateDocument
     */
    public function testUpdateDocument($name, $updateValues , $expectedValues)
    {
        $crud = new DocumentCollection();
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
    public function testDeleteDocument($name, $fields, $expectedValues)
    {
        $crud = new DocumentCollection();
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
