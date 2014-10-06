<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\Pu\Api;

require_once 'DCPTEST/PU_testcase_dcp_commonfamily.php';

class TestCaseApi extends \Dcp\Pu\TestCaseDcpCommonFamily
{
    
    protected static $testDirectory = "APITEST";

    protected function resetDocumentCache() {
        parent::resetDocumentCache();
        \Dcp\HttpApi\V1\DocManager::cache()->clear();
    }
}
