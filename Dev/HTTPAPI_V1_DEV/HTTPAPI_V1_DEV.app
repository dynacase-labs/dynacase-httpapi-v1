<?php

$app_desc = array(
    "name" => "HTTPAPI_V1_DEV",
    "short_name" => N_("HTTPAPI_V1_DEV:HTTPAPI_V1_DEV"),
    "description" => N_("HTTPAPI_V1_DEV:HTTPAPI_V1_DEV"),
    "icon" => "HTTPAPI_V1_DEV.png",
    "displayable" => "N",
    "childof" => ""
);

// ACLs for this application
$app_acl = array(
    array(
        "name" => "BASIC",
        "description" => N_("HTTPAPI_V1_DEV:Basic ACL")
    )
);
// Actions for this application
$action_desc = array(
    array(
        "name" => "TEST",
        "short_name" => N_("HTTPAPI_V1_DEV:Test HTTP API"),
        "layout" => "test.html",
        "script" => "action.test.php",
        "function" => "test",
        "root" => "Y",
        "acl" => "BASIC"
    )
);

