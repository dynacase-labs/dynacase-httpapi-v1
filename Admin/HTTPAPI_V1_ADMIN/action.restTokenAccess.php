<?php
function restTokenAccess(Action & $action)
{
    $action->parent->addJsRef("lib/jquery/1.7.2/jquery.js");
    $action->parent->addJsRef("lib/jquery-ui-1.12.0/jquery-ui.js");
    $action->parent->addJsRef("lib/jquery-dataTables/1.10/js/jquery.dataTables.js");
    $action->parent->addJsRef("AUTHENTUI/Layout/jquery-ui-combo.js");
    $action->parent->addJsRef("AUTHENTUI/Layout/token_access.js");
    $action->parent->addJsRef("HTTPAPI_V1_ADMIN/Layout/token_admin.js");
    
    $action->parent->addCssRef("WHAT/Layout/size-normal.css");
    $action->parent->addCssRef("lib/jquery-ui-1.12.0/jquery-ui.css");
    $action->parent->addCssRef("lib/jquery-dataTables/1.10/css/jquery.dataTables.css");
    $action->parent->addCssRef("lib/jquery-dataTables/1.10/css/dataTables.jqueryui.css");
    
    $action->parent->addCssRef("AUTHENTUI/Layout/token_access.css");
    
    simpleQuery($action->dbaccess, "select application.name as appname, action.name as actionname from action, application where action.id_application=application.id and action.openaccess= 'Y' order by application.name, action.name;", $openActions);
    
    $openActionData = [];
    foreach ($openActions as $openAction) {
        $openActionData[] = ["openAction" => sprintf("%s:%s", $openAction["appname"], $openAction["actionname"]) , "openActionLabel" => sprintf("%s:%s", $openAction["appname"], $openAction["actionname"]) ];
    }
    $action->lay->eSetBlockData("OPENACTIONS", $openActionData);
    $action->lay->eSet("today", date("Y-m-d"));
    $action->lay->eSet("lang", substr($action->getParam("CORE_LANG") , 0, 2));
}
