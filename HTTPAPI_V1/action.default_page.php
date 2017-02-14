<?php
/*
 * @author Anakeen
 * @package FDL
*/
function default_page(Action & $action)
{
    
    $usage = new ActionUsage($action);
    
    $usage->setStrictMode(false);
    $usage->verify(true);
    $crud = json_decode(\ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "CRUD_CLASS") , true);
    $middle = json_decode(\ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "CRUD_MIDDLECLASS") , true);
    
    $baseURL = \Dcp\HttpApi\V1\Api\AnalyzeURL::getBaseURL();
    
    usort($crud, function ($value1, $value2)
    {
        return $value1["canonicalURL"] > $value2["canonicalURL"];
    });
    
    $defaultValues = function ($value) use ($baseURL)
    {
        $value["canonicalURL"] = isset($value["canonicalURL"]) ? $value["canonicalURL"] : $value["regExp"];
        $value["canonicalURL"] = $baseURL . $value["canonicalURL"];
        $value["description"] = isset($value["description"]) ? $value["description"] : $value["class"];
        $value["acceptExtensions"] = isset($value["acceptExtensions"]) ? implode(", ", $value["acceptExtensions"]) : "json";
        return $value;
    };
    
    $crud = array_map($defaultValues, $crud);
    
    $action->lay->esetBlockData('SYSTEM_CRUD', $crud);
    
    $middle = array_map($defaultValues, $middle);
    
    $action->lay->esetBlockData('MIDDLE_CRUD', $middle);
    $action->lay->set("DOCUMENTATION_URL", \ApplicationParameterManager::getParameterValue(ApplicationParameterManager::CURRENT_APPLICATION, "DOCUMENTATION_URL"));
}
