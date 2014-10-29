<?php
/**
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package Dcp\Pu
 */

namespace Dcp\Pu\HttpApi\V1\Test;

use Dcp\Pu\FrameworkDcp;

set_include_path(get_include_path() . PATH_SEPARATOR . "./DCPTEST:./WHAT");

require_once 'WHAT/autoload.php';

class TestSuiteApi extends \Dcp\Pu\TestSuiteDcp
{
    public static function suite()
    {
        self::configure();
        self::$allInProgress = true;
        $suite = new FrameworkDcp('Api');

        $suite->addTest(SuiteApi::suite());

        printf("\nerror log in %s, messages in %s\n", self::logFile, self::msgFile);
        return $suite;
    }
}
?>
