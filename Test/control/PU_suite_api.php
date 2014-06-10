<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package Dcp\Pu
 */

namespace Dcp\Pu\Api;

require_once 'WHAT/autoload.php';
class SuiteApi
{
    public static function suite()
    {
        $suite = new \Dcp\Pu\FrameworkDcp('Package');
        
        $suite->addTestSuite('Dcp\Pu\Api\TestDocumentCrud');
        $suite->addTestSuite('Dcp\Pu\Api\TestFamilyCrud');
        // ...
        return $suite;
    }
}
