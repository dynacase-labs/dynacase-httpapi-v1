<?php
/*
 * @author Anakeen
 * @package FDL
*/
/**
 * Created by PhpStorm.
 * User: eric
 * Date: 17/06/15
 * Time: 09:08
 */

namespace Dcp;

class ConsoleTime
{
    
    static protected $partial = array();
    static protected $partialIndex = '';
    static protected $records = array();
    static protected $begin = array();
    static protected $activated = false;
    
    public static function activate($active = true)
    {
        self::$activated = (bool)$active;
    }
    
    public static function begin()
    {
        if (self::$activated !== true) {
            return;
        }
        self::$begin = self::memory();
    }
    
    public static function step($text)
    {
        if (self::$activated !== true) {
            return;
        }
        if (!self::$partial) {
            self::$records[$text] = self::memory();
        } else {
            $record = & self::$records;
            foreach (self::$partial as $aPartial) {
                $record = & $record[$aPartial];
            }
            $record[$text] = self::memory();
        }
    }
    
    public static function startPartial($text)
    {
        if (self::$activated !== true) {
            return;
        }

        $record = & self::$records;
            foreach (self::$partial as $aPartial) {
                $record = & $record[$aPartial];
            }
            $record[$text] = array(
                "__startPartial" => self::memory()
            );

        self::$partial[] = $text;
    }
    
    public static function stopPartial()
    {
        if (self::$activated !== true) {
            return;
        }
        array_pop(self::$partial);
    }
    
    protected static function memory()
    {
        return array(
            "memory" => memory_get_usage(false) ,
            "time" => microtime(true)
        );
    }
    
    public static function getRecords()
    {
        return self::$records;
    }
    
    protected static function getPartial($ptext, $pstats, &$out, &$prev)
    {
        
        $prev = array_shift($pstats);
        $partialBegin = $prev;
        foreach ($pstats as $text => $stats) {
            if (isset($stats["__startPartial"])) {
                self::getPartial($text, $stats, $out, $prev);
            } else {
                
                $out[$ptext . '/' . $text] = self::diff($stats, $prev);
                $prev = $stats;
            }
        }
        $out[$ptext] = self::diff($prev, $partialBegin);
    }
    
    public static function get()
    {
        $prev = self::$begin;
        $out = array();
        foreach (self::$records as $text => $stats) {
            
            if (isset($stats["__startPartial"])) {
                self::getPartial($text, $stats, $out, $prev);
            } else {
                $out[$text] = self::diff($stats, $prev);
                $prev = $stats;
            }
        }
        $out["total"] = self::diff($prev, self::$begin);
        return $out;
    }
    
    protected static function diff($a, $b)
    {
        return sprintf("%3d.02 ms |  %6d ko", ($a["time"] - $b["time"]) * 1000, ($a["memory"] - $b["memory"]) / 1024);
    }
    public static function getDisplay()
    {
        $get = self::get();
        $out = "";
        foreach ($get as $key => $value) {
            $prefix = str_pad("", substr_count($key, "/") * 2, "-");
            $out.= sprintf("| %-30s | %15s |\n", $prefix . $key, $value);
        }
        return $out;
    }
}
