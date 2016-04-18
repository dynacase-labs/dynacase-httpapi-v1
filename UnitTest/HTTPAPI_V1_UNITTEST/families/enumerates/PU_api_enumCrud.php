<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\Pu\HttpApi\V1\Test\Families;

use Dcp\HttpApi\V1\Api\AnalyzeURL;
use Dcp\HttpApi\V1\Crud\Exception as DocumentException;
use Dcp\HttpApi\V1\DocManager\DocManager;
use Dcp\HttpApi\V1\Crud\Enumerates as Enumerates;
use Dcp\HttpApi\V1\Crud\Exception as CrudException;
use Dcp\Pu\HttpApi\V1\Test\Documents\TestDocumentCrud;

require_once 'HTTPAPI_V1_UNITTEST/PU_TestCaseApi.php';

class TestFamilyEnumerateCrud extends TestDocumentCrud
{
    /**
     * Test that unable to create document
     *
     * @dataProvider dataCreateDocument
     */
    public function testCreate()
    {
        $crud = new Enumerates();
        $crud->setUrlParameters(array(
            "familyId" => "TST_APIBASE"
        ));
        try {
            $crud->create();
            $this->assertFalse(true, "An exception must occur");
        }
        catch(DocumentException $exception) {
            $this->assertEquals(501, $exception->getHttpStatus());
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
     * @param string $name
     * @param array $expectedData
     * @throws DocumentException
     * @dataProvider dataReadDocument
     */
    public function testRead($name, $fields, $expectedData)
    {
        $crud = new Enumerates();
        $crud->setUrlParameters(array(
            "familyId" => "TST_APIBASE"
        ));
        $data = $crud->read($name);

        $data = json_decode(json_encode($data) , true);

        $expectedData = $this->prepareData($expectedData);
        $this->verifyData($data, $expectedData);
    }

    public function dataReadDocument()
    {
        return array(
            array(
                "",
                null,
                file_get_contents("HTTPAPI_V1_UNITTEST/families/enumerates/enumerates.json")
            ) ,
            array(
                "tst_apibase_enum_array",
                null,
                file_get_contents("HTTPAPI_V1_UNITTEST/families/enumerates/enumerate_tst_apibase_enum_array.json")
            )
        );
    }

    /**
     * @dataProvider datasortBy
     *
     * @param $name
     * @param $sortBy
     * @param $expectedData
     */
    public function testSortBy($name, $sortBy, $expectedData)
    {
        $crud = new Enumerates();
        if(!is_null($sortBy))
        {
            $crud->setContentParameters(
                array(
                    "orderBy" => $sortBy
                )
            );
        }
        $crud->setUrlParameters(
            array(
                "familyId" => "TST_APIBASE"
            )
        );
        $data = $crud->read($name);

        $data = json_decode(json_encode($data), true);

        $expectedData = $this->prepareData($expectedData);
        $this->verifyData($data, $expectedData);
    }

    public function dataSortBy()
    {
        return array(
            array(
                "TST_APIBASE_ENUM",
                null,
                file_get_contents(
                    "HTTPAPI_V1_UNITTEST/families/enumerates/enumerate_sortBy_null.json"
                )
            ),
            array(
                "TST_APIBASE_ENUM",
                "none",
                file_get_contents(
                    "HTTPAPI_V1_UNITTEST/families/enumerates/enumerate_sortBy_none.json"
                )
            ),
            array(
                "TST_APIBASE_ENUM",
                "key",
                file_get_contents(
                    "HTTPAPI_V1_UNITTEST/families/enumerates/enumerate_sortBy_key.json"
                )
            ),
            array(
                "TST_APIBASE_ENUM",
                "label",
                file_get_contents(
                    "HTTPAPI_V1_UNITTEST/families/enumerates/enumerate_sortBy_label.json"
                )
            )
        );
    }

    /**
     * @dataProvider dataSortByInvalid
     *
     * @param $sortBy
     * @param $expectedExceptionCode
     */
    public function testSortByInvalid($sortBy, $expectedStatus, $expectedExceptionCode)
    {
        $crud = new Enumerates();
        try {
            if (!is_null($sortBy)) {
                $crud->setContentParameters(
                    array(
                        "orderBy" => $sortBy
                    )
                );
                $this->assertFalse(true, "An exception must occur");
            }
        } catch (CrudException $exception) {
            $this->assertEquals(
                $expectedStatus, $exception->getHttpStatus()
            );
            $this->assertEquals(
                $expectedExceptionCode, $exception->getDcpCode()
            );
        }
    }

    public function dataSortByInvalid()
    {
        return array(
            array(
                "invalid",
                "400",
                "CRUD0403"
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
        $crud = new Enumerates();
        $crud->setUrlParameters(array(
            "familyId" => $name
        ));
        try {
            $crud->update($name);
            $this->assertFalse(true, "An exception must occur");
        }
        catch(DocumentException $exception) {
            $this->assertEquals(501, $exception->getHttpStatus());
        }
    }
    
    public function dataUpdateDocument()
    {
        return array(
            array(
                "TST_APIBASE",
                null,
                array()
            )
        );
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
        $crud = new Enumerates();
        $crud->setUrlParameters(array(
            "familyId" => $name
        ));
        try {
            $crud->delete(null);
            $this->assertFalse(true, "An exception must occur");
        }
        catch(DocumentException $exception) {
            $this->assertEquals(501, $exception->getHttpStatus());
        }
    }
    
    public function dataDeleteDocument()
    {
        return array(
            array(
                "TST_APIBASE",
                null,
                array()
            )
        );
    }
    
    public function prepareData($data)
    {
        //Get RefDoc
        $familyDoc = DocManager::getDocument("TST_APIBASE");
        $this->assertNotNull($familyDoc, "Unable to find family TST_APIBASE doc");
        //Replace variant part
        $data = str_replace('%baseURL%', AnalyzeURL::getBaseURL() , $data);
        $data = str_replace('%initId%', $familyDoc->getPropertyValue('initid') , $data);
        $data = str_replace('%id%', $familyDoc->getPropertyValue('id') , $data);
        
        $data = json_decode($data, true);
        
        $this->assertEquals(JSON_ERROR_NONE, json_last_error() , "Unable to decode the test data");
        
        return $data;
    }
}
