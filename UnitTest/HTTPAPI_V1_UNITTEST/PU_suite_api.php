<?php
/**
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package Dcp\Pu
 */

namespace Dcp\Pu\HttpApi\V1\Test;

use Dcp\Pu\FrameworkDcp;

require_once 'WHAT/autoload.php';

class SuiteApi
{
    public static function suite()
    {
        $suite = new FrameworkDcp('Package');
        
        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Documents\\TestDocumentCrud");
        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Documents\\TestDocumentsCollectionCrud");
        //$suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\TestFamilyCrud");

        return $suite;
    }
}
