<?php

$app_desc = array(
    "name" => "HTTPAPI_V1_ADMIN",
    "short_name" => N_("HTTPAPI_V1_ADMIN:HTTPAPI_V1_ADMIN"),
    "description" => N_("HTTPAPI_V1_ADMIN:HTTPAPI_V1_ADMIN"),
    "icon" => "HTTPAPI_V1_ADMIN.png",
    "displayable" => "N",
    "with_frame" =>"Y",
    "tag" => "ADMIN SYSTEM AUTHENT",
    "childof" => ""
);

// ACLs for this application
$app_acl = array(
    array(
        "name" => "BASIC",
        "description" => N_("HTTPAPI_V1_ADMIN:Basic ACL")
    )
);
// Actions for this application
$action_desc = array(
    array(
        "name" => "TOKEN_ACCESS",
        "toc_order" => 1,
        "toc" => "Y",
        "acl" => "ADMIN",
        "short_name" => N_("HttpApi:Token Access"),
        "script" => "action.restTokenAccess.php",
        "function" => "restTokenAccess",
        "layout" => "token_access.html"
    ),
    array(
        "name" => "TOKEN_DATA",
        "acl" => "ADMIN",
        "short_name" => N_("HttpApi:Token Data"),
        "script" => "action.restTokenData.php",
        "function" => "restTokenData"
    )
);

