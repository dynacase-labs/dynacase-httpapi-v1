<?php

$app_desc = array(
    "name" => "HTTPAPI_V1_TEST",
    "short_name" => N_("HTTPAPI_V1_TEST:HTTPAPI_V1_TEST"),
    "description" => N_("HTTPAPI_V1_TEST:HTTPAPI_V1_TEST"),
    "icon" => "HTTPAPI_V1_TEST.png",
    "displayable" => "N",
    "childof" => ""
);

// ACLs for this application
$app_acl = array(
    array(
        "name" => "BASIC",
        "description" => N_("HTTPAPI_V1_TEST:Basic ACL")
    )
);
// Actions for this application
$action_desc = array(
    array(
        "name" => "TEST",
        "short_name" => N_("HTTPAPI_V1_TEST:Test HTTP API"),
        "layout" => "test.html",
        "script" => "action.test.php",
        "function" => "test",
        "root" => "Y",
        "acl" => "BASIC"
    )
);

