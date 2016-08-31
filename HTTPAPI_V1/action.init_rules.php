<?php
/*
 * @author Anakeen
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
    
    usort($rules, function ($value1, $value2)
    {
        return $value1["order"] > $value2["order"];
    });
    
    $rules = json_encode($rules);
    ApplicationParameterManager::setParameterValue(ApplicationParameterManager::CURRENT_APPLICATION, "CRUD_CLASS", $rules);
    
    Redirect($action, "HTTPAPI_V1", "DEFAULT_PAGE");
}
