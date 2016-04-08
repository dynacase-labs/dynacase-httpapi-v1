<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\DocManager;

class MemoryCache
{
    static $cacheObjects = array();
    /**
     * Retrieve object from key identifier
     * @param string $key object identifier
     * @return object|null
     */
    public function &get($key)
    {
        $null = null;
        if ($key === '' or $key === null or (!is_scalar($key))) {
            return $null;
        }
        if (array_key_exists($key, self::$cacheObjects)) {
            return self::$cacheObjects[$key];
        }
        return $null;
    }
    /**
     * Add or update an object
     * @param string $key object identifier
     * @param mixed $item object to add or update
     * @return bool
     */
    public function set($key, &$item)
    {
        if ($key === '' or $key === null or (!is_scalar($key))) {
            return false;
        }
        self::$cacheObjects[$key] = & $item;
        
        return true;
    }
    /**
     * Unset object
     * @param string $key object identifier
     * @return bool
     */
    public function remove($key)
    {
        if ($key === '' or $key === null or (!is_scalar($key))) {
            return false;
        }
        unset(self::$cacheObjects[$key]);
        return true;
    }
    /**
     * unset all objects referenced in cache
     * @return bool
     */
    public function clear()
    {
        self::$cacheObjects = array();
        return true;
    }
    /**
     * Return all keys referenced in cached
     * @return array referenced keys returns
     */
    public function getKeys()
    {
        return array_keys(self::$cacheObjects);
    }
    /**
     * Verifi if a key is referenced in cached
     * @param $key object identifier
     * @return bool
     */
    public function exists($key)
    {
        return array_key_exists($key, self::$cacheObjects);
    }
    /**
     * Verify if a key is referenced in cached and object is same as item object
     * @param string $key object identifier
     * @param object $item object item
     * @return bool true if $key and item match
     */
    public function isInCache($key, &$item)
    {
        if ($key === '' or $key === null or (!is_scalar($key))) {
            return false;
        }
        return (isset(self::$cacheObjects[$key]) && self::$cacheObjects[$key] === $item);
    }
}
