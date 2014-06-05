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
    
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }
}
