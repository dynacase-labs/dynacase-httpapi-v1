<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

global $app_desc, $action_desc;

$app_desc = array(
    "name" => "HTTPAPI_V1", //Name
    "short_name" => N_("Http Api"), //Short name
    "description" => "HTTP Api (version 1)", //long description
    "icon" => "httpapi.png", //Icon
    "displayable" => "N", //Should be displayed on an app list (Y,N)
    "tag" => "CORE SYSTEM"
);

$app_acl = array(
    array(
        "name" => "GET",
        "description" => N_("Access to http api getter") ,
        "group_default" => "Y"
    ),
    array(
        "name" => "POST",
        "description" => N_("Access to http api setter") ,
        "group_default" => "Y"
    ),
    array(
        "name" => "PUT",
        "description" => N_("Access to http api setter") ,
        "group_default" => "Y"
    ),
    array(
        "name" => "DELETE",
        "description" => N_("Access to http api setter") ,
        "group_default" => "Y"
    )
);

$action_desc = array(
    array(
        "name" => "CREATEDOCUMENT",
        "short_name" => "Create new document",
        "acl" => "POST"
    ),
    array(
        "name" => "MODIFYDOCUMENT",
        "short_name" => "Modify document",
        "acl" => "POST"
    ),
    array(
        "name" => "RECORDFILE",
        "short_name" => "Record file document",
        "acl" => "POST"
    )

);
