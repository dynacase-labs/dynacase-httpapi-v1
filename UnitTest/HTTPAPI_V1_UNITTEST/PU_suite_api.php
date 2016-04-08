<?php
/**
 * @author Anakeen
 * @package Dcp\Pu
 */

namespace Dcp\Pu\HttpApi\V1\Test;

use Dcp\HttpApi\V1\Crud\Exception;
use Dcp\HttpApi\V1\DocManager\DocManager;
use Dcp\Pu\FrameworkDcp;

require_once 'WHAT/autoload.php';

class ApiTestSuite extends FrameworkDcp {

    /**
     * Import the standard test family before the test suite
     *
     * @throws Exception
     */
    public function setUp() {
        parent::setUp();
        $this->importFamily("HTTPAPI_V1_UNITTEST/PU_api_crudDocument_family.csv");
    }

    /**
     * Delete the standard test family after the test suite
     *
     */
    public function tearDown() {
        //$this->destroyFamily("TST_APIBASE");
        parent::tearDown();
    }

    protected function destroyFamily($family) {
        $familyDocument = DocManager::getDocument($family);
        if ($familyDocument) {
            $familyId = $familyDocument->getPropertyValue("id");
            $familyName = $familyDocument->getPropertyValue("name");
            $sqlContent = array();
            $sqlContent += array(
                "delete from fld where childid in (select id from doc$familyId);",
                "delete from doc$familyId;",
                "drop view family.\"" . strtolower($familyName) . "\";",
                "delete from docname where name='$familyName'",
                "delete from docfrom where fromid=$familyId",
                "drop table doc$familyId;",
                "delete from docattr where docid=$familyId;",
                "delete from docfam where id=$familyId;"
            );
            unlink("FDLGEN/Class.Doc" . $familyId . ".php");
            foreach ($sqlContent as $sql) {
                simpleQuery(getDbAccess(), $sql);
            }
        }
    }

    protected function importFamily($fileName) {
        global $action;
        if (!is_file($fileName)) {
            throw new Exception("Unable to find $fileName");
        }
        $importDocument = new \ImportDocument();
        $importDocument->setCsvOptions(",", '"');
        $importDocument->setVerifyAttributeAccess(false);
        $importDocument->importDocuments($action, $fileName);
        $err = $importDocument->getErrorMessage();
        if ($err) {
            throw new Exception("Unable to import $err");
        }
    }

}

class SuiteApi
{
    public static function suite()
    {
        $suite = new ApiTestSuite();

        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Documents\\TestDocumentCrud");
        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Documents\\TestDocumentsCollectionCrud");
        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Documents\\TestHistory");
        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Documents\\TestRevisionCollection");
        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Documents\\TestRevision");
        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Families\\TestFamilyCrud");
        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Families\\TestFamilyDocumentCollection");
        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Families\\TestFamilyDocumentCrud");
        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Families\\TestFamilyEnumerateCrud");
        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Folders\\TestFoldersCrud");
        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Folders\\TestFolderCollectionCrud");
        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Searches\\TestSearchesCrud");
        $suite->addTestSuite("Dcp\\Pu\\HttpApi\\V1\\Test\\Searches\\TestSearchesCollectionCrud");

        return $suite;
    }
}
