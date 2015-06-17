<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 */

function init_rules(Action & $action)
{
    
    $usage = new ActionUsage($action);
    
    $usage->setStrictMode(false);
    $usage->verify(true);
    $directoryIterator = new DirectoryIterator("./HTTPAPI_V1/rules.d/");
    $jsonIterator = new RegexIterator($directoryIterator, "/.*\.json$/");
    $jsonList = array();
    foreach ($jsonIterator as $currentFile) {
        $jsonList[] = $currentFile->getPathName();
    }
    sort($jsonList);
    $rules = array();
    foreach ($jsonList as $currentPath) {
        $currentRule = file_get_contents($currentPath);
        $currentRule = json_decode($currentRule, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Unable to read and decode " . $currentPath);
        }
        $rules = array_merge($rules, $currentRule);
    }
    $rules = json_encode($rules);
    ApplicationParameterManager::setParameterValue(ApplicationParameterManager::CURRENT_APPLICATION, "CRUD_CLASS", $rules);
    
    Redirect($action, "HTTPAPI_V1", "DEFAULT_PAGE");
}
