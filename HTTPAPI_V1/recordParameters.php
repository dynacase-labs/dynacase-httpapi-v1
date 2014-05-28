<?php
/**
 * Created by PhpStorm.
 * User: eric
 * Date: 26/05/14
 * Time: 13:59
 */

namespace Dcp\HttpApi;


class recordParameters {


    public static function getFamily() {
        return $_GET["family"];
    }

    public static function getValues() {
        return $_POST;
    }
} 