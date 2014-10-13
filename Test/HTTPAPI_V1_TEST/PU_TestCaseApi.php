<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\Pu\HttpApi\V1\Test;

use Dcp\HttpApi\V1\DocManager;

require_once 'DCPTEST/PU_testcase_dcp_commonfamily.php';

class TestCaseApi extends \Dcp\Pu\TestCaseDcpCommonFamily
{
    
    protected static $testDirectory = "HTTPAPI_V1_TEST";

    protected function resetDocumentCache() {
        parent::resetDocumentCache();
        DocManager::cache()->clear();
    }
}
