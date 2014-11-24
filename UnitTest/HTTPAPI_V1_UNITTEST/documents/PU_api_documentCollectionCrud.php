<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package Dcp\Pu
*/

namespace Dcp\Pu\HttpApi\V1\Test;

use Dcp\HttpApi\V1\Api\AnalyzeURL;
use Dcp\HttpApi\V1\Crud\DocumentCollection as DocumentCollection;

require_once 'HTTPAPI_V1_UNITTEST/PU_TestCaseApi.php';

class TestDocumentsCollectionCrud extends TestCaseApi
{
    const familyName = "TST_APIBASE";

    /**
     * import TST_APIBASE family
     * @static
     * @return string
     */
    /*protected static function getCommonImportFile()
    {
        return array(
            "PU_api_crudDocument.csv"
        );
    }*/

    /**
     * @param array $expectedValues
     * @dataProvider datagetDocument
     */
    public function testGetDocument(array $expectedValues)
    {

        $documentCollectionCrud = new DocumentCollection();
        $data = $documentCollectionCrud->read(null);

        $this->verifyData($data, $expectedValues);
    }

    public function datagetDocument()
    {

        return json_decode(<<<JSON
[{
    "requestParameters": {
        "slice": 10,
        "offset": 0,
        "length": 10,
        "orderBy": "title asc, id desc"
    },
    "uri": "{baseUrl}documents/"
}]
JSON
            , true);

    }

    protected function verifyData($apiData, $expectedValues)
    {
        foreach ($expectedValues as $expectedKey => $expectedValue) {
            $keys = explode(".", $expectedKey);
            $currentData = $apiData;
            foreach ($keys as $key) {
                if ($expectedValue !== null) {
                    // If the current expected key is not found throw an error
                    $this->assertTrue(isset($currentData[$key]), sprintf("key \"%s\" not found %s", $key, print_r($currentData, true)));
                    $currentData = $currentData[$key];
                    if (is_object($currentData)) {
                        $currentData = get_object_vars($currentData);
                    } elseif (is_array($currentData)) {
                        foreach ($currentData as $k => $v) {
                            if (is_object($v)) {
                                $currentData[$k] = get_object_vars($v);
                            }
                        }
                    }
                } else {
                    if (isset($currentData[$key])) {
                        $currentData = $currentData[$key];
                    } else {
                        $currentData = null;
                        break;
                    }
                }
            }
            if (is_string($expectedValue)) {
                $expectedValue = str_replace('{baseUrl}', AnalyzeURL::getBaseURL(), $expectedValue);
            }
            $this->assertEquals($expectedValue, $currentData, sprintf("wrong value for $expectedKey :%s ", print_r($apiData, true)));
        }
    }


}
