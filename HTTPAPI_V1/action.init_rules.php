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


    $rules=initRoutes("./HTTPAPI_V1/rules.d/");
    ApplicationParameterManager::setParameterValue(ApplicationParameterManager::CURRENT_APPLICATION, "CRUD_CLASS", $rules);


    $rules=initRoutes("./HTTPAPI_V1/rules.d/middleware.d/");
    ApplicationParameterManager::setParameterValue(ApplicationParameterManager::CURRENT_APPLICATION, "CRUD_MIDDLECLASS", $rules);


    
    Redirect($action, "HTTPAPI_V1", "DEFAULT_PAGE");
}


function initRoutes($directoryPath) {
    $rules = array();
    if (is_dir($directoryPath)) {
        $directoryIterator = new DirectoryIterator($directoryPath);
        $jsonIterator = new RegexIterator($directoryIterator, "/.*\.json$/");
        $jsonList = array();
        foreach ($jsonIterator as $currentFile) {
            $jsonList[] = $currentFile->getPathName();
        }
        sort($jsonList);
        foreach ($jsonList as $currentPath) {
            $currentRule = file_get_contents($currentPath);
            $currentRule = json_decode($currentRule, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Unable to read and decode " . $currentPath);
            }
            $rules = array_merge($rules, $currentRule);
        }

        usort($rules, function ($value1, $value2) {
            return ($value1["order"] > $value2["order"]) ? -1 : (($value1["order"] < $value2["order"]) ? 1 : 0);
        });

        $rules = json_encode($rules);
    }
    return $rules;
}
