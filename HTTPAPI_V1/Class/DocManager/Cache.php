<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\DocManager;

class Cache
{
    /**
     * @var MemoryCache $localCache
     */
    static protected $localCache = null;
    /**
     * Set document object to local cache
     *
     * Return object itself
     *
     * @param \Doc $document
     * @throws Exception APIDM0200, APIDM0201
     * @api Record document to local cache
     * @return \Dcp\Family\Document|\DocFam
     */
    static public function &addDocument(\Doc & $document)
    {
        if (empty($document->id)) {
            throw new Exception("APIDM0200");
        }
        if (($document->doctype != 'C') || (count($document->attributes->attr) > 0)) {
            if (!self::getLocalCache()->set($document->id, $document)) {
                throw new Exception("APIDM0201", $document->getTitle() , $document->id);
            }
            // Add Core cache compatibility
            global $gdocs;
            $gdocs[$document->id] = & $document;
        }
        
        return $document;
    }
    /**
     * Clear local cache
     *
     * @throws Exception APIDM0202
     * @api Clear local cache
     * @return void
     */
    static public function clear()
    {
        if (!self::getLocalCache()->clear()) {
            throw new Exception("APIDM0202");
        }
    }
    /**
     * Verify if object referenced by key exists
     *
     * @param int $documentId Document identifier
     * @throws Exception
     * @return bool
     */
    static public function isDocumentIdInCache($documentId)
    {
        if (empty($documentId)) {
            return false;
        }
        if (!is_numeric($documentId)) {
            throw new Exception("APIDM0203");
        }
        
        return self::getLocalCache()->exists($documentId);
    }
    /**
     * Return object referenced by key exists
     *
     * Return null if key not exists in cache.
     *
     * @param string $documentId object key
     * @return \Dcp\Family\Document|null
     */
    static public function getDocumentFromCache($documentId)
    {
        $cachedDocument = self::getLocalCache()->get($documentId);
        if (is_object($cachedDocument)) {
            return $cachedDocument;
        }
        return null;
    }
    /**
     * Unset document object from local cache
     *
     * Return removed object itself
     *
     * @param \Doc $document
     *
     * @api Unset document object from local cache
     * @return \Doc
     */
    static public function &removeDocument(\Doc & $document)
    {
        self::getLocalCache()->remove($document->id);
        return $document;
    }
    /**
     * Unset a document's object by its id from local cache
     *
     * Return bool(true) on success or bool(false) if $key is invalid
     *
     * @param int $id
     * @return bool bool(true) on success or bool(false) if $key is invalid
     */
    static public function removeDocumentById($id)
    {
        return self::getLocalCache()->remove($id);
    }
    /**
     * Verify if document object is in cache
     *
     * Return true is object is in local cache
     *
     * @param \Doc $document
     * @return bool
     */
    static public function isInCache(\Doc & $document)
    {
        return self::getLocalCache()->isInCache($document->id, $document);
    }
    /**
     * Get local cache object
     * @return MemoryCache
     */
    protected static function getLocalCache()
    {
        if (self::$localCache === null) {
            self::$localCache = new MemoryCache();
        }
        return self::$localCache;
    }
}
