<?php

function default_page(Action &$action) {

    $usage = new ActionUsage($action);

    $usage->setStrictMode(false);
    $usage->verify(true);
    $systemCrud = json_decode(\ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "SYSTEM_CRUD_CLASS"), true);
    $customCrud = json_decode(\ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "CUSTOM_CRUD_CLASS"), true);

    usort($systemCrud, function ($value1, $value2) {
        return $value1["order"] < $value2["order"];
    });
    usort($customCrud, function ($value1, $value2) {
        return $value1["order"] < $value2["order"];
    });

    $defaultValues = function($value) {
        $value["canonicalURL"] = isset($value["canonicalURL"]) ? $value["canonicalURL"] : $value["regExp"];
        $value["description"] = isset($value["description"]) ? $value["description"] : $value["class"];
        return $value;
    };

    $systemCrud = array_map($defaultValues, $systemCrud);
    $customCrud = array_map($defaultValues, $customCrud);

    $action->lay->esetBlockData('SYSTEM_CRUD', $systemCrud);
    $action->lay->set('HAS_CUSTOM_CRUD', !empty($customCrud));
    $action->lay->esetBlockData('CUSTOM_CRUD', $customCrud);
    $action->lay->set("DOCUMENTATION_URL", \ApplicationParameterManager::getParameterValue(ApplicationParameterManager::CURRENT_APPLICATION, "DOCUMENTATION_URL"));

}