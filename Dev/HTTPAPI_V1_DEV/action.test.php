<?php

function test(Action &$action) {

    $usage = new ActionUsage($action);

    $usage->setStrictMode(false);
    $usage->verify(true);

    $coreURL = \ApplicationParameterManager::getScopedParameterValue("CORE_URLINDEX");
    $helpPage = $coreURL . \ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "DEFAULT_PAGE");

    $defaultValues = array(
        "baseURL" => \Dcp\HttpApi\V1\AnalyzeURL::getBaseURL()
    );

    $action->lay->set("DEFAULT_VALUES", json_encode($defaultValues));
    $action->lay->eset("HELP_PAGE", $helpPage);

}