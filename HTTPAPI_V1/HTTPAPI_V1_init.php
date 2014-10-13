<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

global $app_const;

$json_system_logger = <<<'JSON'
[
    "\\Dcp\\HttpApi\\V1\\Logger\\Dcp"
]
JSON;

$json_system_crud = <<<'JSON'
[
    { "order" : 100, "class" : "\\Dcp\\HttpApi\\V1\\DocumentCrud", "regExp" : "/^\\/documents\\/?(?P<identifier>[^\\/]*)$/"},
    { "order" : 100, "class" : "\\Dcp\\HttpApi\\V1\\EnumCrud", "regExp" : "/^\\/enums\\/(?P<familyId>[^\\/]*)\\/?(?P<identifier>[^\\/]*)$/"},
    { "order" : 100, "class" : "\\Dcp\\HttpApi\\V1\\FamilyCrud", "regExp" : "/^\\/families\\/(?P<identifier>[^\\/]*)\\/?$/"},
    { "order" : 200, "class" : "\\Dcp\\HttpApi\\V1\\FamilyDocumentCrud", "regExp" : "/^\\/families\\/(?P<familyId>[^\\/]*)\\/documents\\/(?P<identifier>[^\\/]*)$/"},
    { "order" : 100, "class" : "\\Dcp\\HttpApi\\V1\\Trash", "regExp" : "/^\\/trash\\/?(?P<identifier>[^\\/]*)$/"},
    { "order" : 100, "class" : "\\Dcp\\HttpApi\\V1\\EnumCrud", "regExp" : "/^\\/enums\\/(?P<familyId>[^\\/]*)\\/(?P<identifier>[^\\/]*)$/"}
]
JSON;

$app_const = array(
    "INIT" => "yes",
    "VERSION" => "1.0.0-0",
    "SYSTEM_CRUD_CLASS" => array(
        "val" => $json_system_crud,
        "descr" => N_("rest:default crud class"),
        "kind" => "static"
    ),
    "CUSTOM_CRUD_CLASS" => "[]",
    "SYSTEM_LOGGER" => array(
        "val" => $json_system_logger,
        "descr" => N_("rest:default logger class"),
        "kind" => "static"
    ),
    "CUSTOM_LOGGER" => "[]"
);
