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
    
    $rules = initRoutes("./HTTPAPI_V1/rules.d/");
    ApplicationParameterManager::setParameterValue(ApplicationParameterManager::CURRENT_APPLICATION, "CRUD_CLASS", $rules);
    
    $rules = initRoutes("./HTTPAPI_V1/rules.d/middleware.d/", true);
    ApplicationParameterManager::setParameterValue(ApplicationParameterManager::CURRENT_APPLICATION, "CRUD_MIDDLECLASS", $rules);
    
    $action->set("DEFAULT_PAGE", $action->parent);
    $action->execute();
}

function initRoutes($directoryPath, $verifyProcess = false)
{
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
            foreach ($currentRule as $kRule => $rule) {
                if (empty($rule["order"]) || empty($rule["regExp"]) || empty($rule["class"])) {
                    throw new Exception(sprintf("Incomplete rule : Must contain \"order\", \"regExp\" and \"class\" : file \"%s\" :\n%s ", $currentPath, print_r($rule, true)));
                }
                if ($verifyProcess && (empty($rule["process"]) || ($rule["process"] !== "before" && $rule["process"] !== "after"))) {
                    throw new Exception(sprintf("Incomplete middleware : Must contain \"process\" with value \"before\" or \"after\" : file \"%s\" :\n%s ", $currentPath, print_r($rule, true)));
                }
                if (empty($rule["description"])) {
                    $currentRule[$kRule]["description"] = sprintf("Rule %s #%02d", basename($currentPath) , $kRule);
                }
            }
            $rules = array_merge($rules, $currentRule);
        }
        
        usort($rules, function ($value1, $value2)
        {
            return ($value1["order"] > $value2["order"]) ? -1 : (($value1["order"] < $value2["order"]) ? 1 : 0);
        });
    }
    return json_encode($rules);
}
