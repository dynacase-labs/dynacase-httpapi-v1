<?php
/*
 * @author Anakeen
 * @package FDL
*/

global $app_desc, $action_desc;

$app_desc = array(
    "name" => "HTTPAPI_V1", //Name
    "short_name" => N_("HTTPAPI_V1:Http Api"), //Short name
    "description" => "HTTP Api (version 1)", //long description
    "icon" => "httpapi.png", //Icon
    "displayable" => "N", //Should be displayed on an app list (Y,N)
    "tag" => "CORE SYSTEM"
);

$app_acl = array(
    array(
        "name" => "GET",
        "description" => N_("HTTPAPI_V1:Access to http api read"),
        "group_default" => "Y"
    ),
    array(
        "name" => "POST",
        "description" => N_("HTTPAPI_V1:Access to http api creation"),
        "group_default" => "Y"
    ),
    array(
        "name" => "PUT",
        "description" => N_("HTTPAPI_V1:Access to http api update"),
        "group_default" => "Y"
    ),
    array(
        "name" => "DELETE",
        "description" => N_("HTTPAPI_V1:Access to http api delete"),
        "group_default" => "Y"
    ),
    array(
        "name" => "ADMIN",
        "description" => N_("HTTPAPI_V1:Admin right")
    )
);

$action_desc = array(
    array(
        "name" => "DEFAULT_PAGE",
        "short_name" => N_("HTTPAPI_V1:DEFAULT_PAGE"),
        "layout" => "default_page.html",
        "script" => "action.default_page.php",
        "function" => "default_page",
        "root" => "Y",
        "acl" => "ADMIN"
    ),
    array(
        "name" => "INIT_RULES",
        "short_name" => N_("HTTPAPI_V1:INIT_RULES"),
        "script" => "action.init_rules.php",
        "function" => "init_rules",
        "acl" => "ADMIN"
    )

);
