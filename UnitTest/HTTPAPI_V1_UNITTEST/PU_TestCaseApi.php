<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\Pu\HttpApi\V1\Test;

use Dcp\HttpApi\V1\DocManager\DocManager;

require_once 'DCPTEST/PU_testcase_dcp_commonfamily.php';

class TestCaseApi extends \Dcp\Pu\TestCaseDcpCommonFamily
{
    
    protected static $testDirectory = "HTTPAPI_V1_UNITTEST";

    protected function resetDocumentCache() {
        parent::resetDocumentCache();
        DocManager::cache()->clear();
    }

    /**
     * Verify data coherence between API DATA and expectedValues
     *
     * @param $data
     * @param $expectedValues
     * @param string $keys
     */
    protected function verifyData(array $data, array $expectedValues, $keys = "")
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
