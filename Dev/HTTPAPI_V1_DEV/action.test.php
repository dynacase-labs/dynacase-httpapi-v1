<?php

function test(Action &$action)
{

    $usage = new ActionUsage($action);

    $usage->setStrictMode(false);
    $usage->verify(true);

    $coreURL = \ApplicationParameterManager::getScopedParameterValue("CORE_URLINDEX");
    $helpPage = $coreURL . \ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "DEFAULT_PAGE");

    $defaultValues = array(
        "baseURL" => \Dcp\HttpApi\V1\Api\AnalyzeURL::getBaseURL(),
        "helpPage" => $helpPage
    );

    $examples = file_get_contents("./HTTPAPI_V1_DEV/examples.json");
    $action->lay->set("DEFAULT_VALUES", json_encode($defaultValues));
    $action->lay->set("EXAMPLES", $examples);
    $listOfExamples = array_map(function($currentExemple) {
        if (isset($currentExemple["params"])) {
            unset($currentExemple["params"]);
        }
        return $currentExemple;
    }, json_decode($examples, true));
    $action->lay->eSetBlockData("EXAMPLES", $listOfExamples);

}