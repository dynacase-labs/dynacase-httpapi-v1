<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\DocManager;

class DocManager
{
    static $trace = false;
    /**
     * @var MemoryCache $localCache
     */
    static protected $localCache = null;
    /**
     * Get document object identified by its identifier
     * @param int|string $documentIdentifier
     * @param bool $latest
     * @param bool $useCache
     * @throws Exception
     * @api Get document object from identifier
     * @return \Doc
     */
    static public function getDocument($documentIdentifier, $latest = true, $useCache = true)
    {
        $id = self::getIdentifier($documentIdentifier, $latest);
        
        if ($id > 0) {
            if ($useCache && self::cache()->isDocumentIdInCache($id)) {
                $cacheDocument = self::cache()->getDocumentFromCache($id);
                if ($cacheDocument && $cacheDocument->id == $id) {
                    return $cacheDocument;
                }
            }
            
            $fromid = self::getFromId($id);
            $classname = '';
            if ($fromid > 0) {
                self::requireFamilyClass($fromid);
                $classname = "Doc$fromid";
            } else if ($fromid == - 1) {
                $classname = "DocFam";
            }
            if ($classname) {
                /* @var \Doc $doc */
                $doc = new $classname(self::getDbAccess() , $id);
                
                if (self::$trace) {
                    self::logTrace($doc->id);
                }
                return $doc;
            }
        }
        
        return null;
    }
    
    public static function activeTrace($active = true, $traceFile = "/tmp/docManager.log")
    {
        self::$trace = $active;
        self::logTrace(null, $traceFile);
    }
    
    private static function logTrace($docid, $newTraceFile = '')
    {
        $tempDir = sys_get_temp_dir();
        static $traceIds = array();
        static $traceLog = array();
        $traceFile = $tempDir . "/docManager.log";
        if ($newTraceFile != '') {
            $traceFile = $newTraceFile;
        }
        if (!$docid) return;
        if (isset($traceIds[$docid])) {
            $traceIds[$docid]++;
            error_log(sprintf("Double set for %d (%s)\n", $docid, self::getTitle($docid)) , 3, $traceFile);
            
            error_log(sprintf("Call log : %s\n", print_r(getDebugStack(2) , true)) , 3, $traceFile);
            
            error_log(sprintf("Previous call : %s\n============\n", print_r($traceLog[$docid], true)) , 3, $traceFile);
            
            addWarningMsg("DM double detected");
        } else {
            $traceIds[$docid] = 1;
            $traceLog[$docid] = getDebugStack(2);
        }
    }
    /**
     * Get family object identified by its identifier
     * @param int|string $familyIdentifier
     * @param bool $useCache
     * @throws Exception
     * @api Get document object from identifier
     * @return \DocFam return null if id not match a family identifier
     */
    static public function getFamily($familyIdentifier, $useCache = true)
    {
        $id = self::getFamilyIdFromName($familyIdentifier);
        
        if ($id > 0) {
            if ($useCache && self::cache()->isDocumentIdInCache($id)) {
                $cacheDocument = self::cache()->getDocumentFromCache($id);
                if ($cacheDocument && $cacheDocument->id == $id) {
                    //if (self::$trace) printf("GET %s %s <br/>\n", $cacheDocument->name, $cacheDocument->title);
                    return $cacheDocument;
                }
            }
            
            $doc = new \DocFam(self::getDbAccess() , $id);
            if (self::$trace) {
                //  printf("%d,<b>SET $useCache</b> %s [#%s] %s <br/>\n", $c++, $doc->name,$id, $doc->title);
                self::logTrace($doc->id);
            }
            return $doc;
        }
        
        return null;
    }
    /**
     * return latest id of document from its initid or other id
     *
     * @param int $initid document identificator
     * @throws Exception
     * @return int|null identifier relative to latest revision
     */
    static protected function getLatestDocumentId($initid)
    {
        if (!is_numeric($initid)) {
            throw new Exception("APIDM0100", print_r($initid, true));
        }
        $dbaccess = self::getDbAccess();
        // first more quick if alive
        simpleQuery($dbaccess, sprintf("select id from docread where initid='%d' and locked != -1", $initid) , $id, true, true);
        if ($id > 0) return intval($id);
        // second for zombie document
        simpleQuery($dbaccess, sprintf("select id from docread where initid='%d' order by id desc limit 1", $initid) , $id, true, true);
        if ($id > 0) return intval($id);
        // it is not really on initid
        simpleQuery($dbaccess, sprintf("select id from docread where initid=(select initid from docread where id=%d) and locked != -1", $initid) , $id, true, true);
        if ($id > 0) return intval($id);
        return null;
    }
    /**
     * return latest id of document from its initid or other id
     *
     * @param string $name document identificator
     *
     * @throws Exception
     * @return int|null identifier relative to latest revision
     */
    static public function getInitIdFromIdOrName($name)
    {
        $id = $name;
        if (!is_numeric($name)) {
            $id = static::getIdFromName($name);
        }
        $dbaccess = self::getDbAccess();
        simpleQuery($dbaccess, sprintf("select initid from docread where id='%d' limit 1;", $id) , $initid, true, true);
        if ($id > 0) {
            return intval($initid);
        }
        return null;
    }
    /**
     * return  id of document identified by its revision
     *
     * @param int $initid document identificator
     * @param int $revision
     * @throws Exception
     * @return int|null identifier relative to latest revision
     */
    static public function getRevisedDocumentId($initid, $revision)
    {
        if (!is_numeric($initid)) {
            $id = static::getIdFromName($initid);
            if ($id === null) {
                throw new Exception("APIDM0100", print_r($initid, true));
            } else {
                $initid = $id;
            }
        }
        $dbaccess = self::getDbAccess();
        if (is_numeric($revision) && $revision >= 0) {
            // first more quick if alive
            simpleQuery($dbaccess, sprintf("select id from docread where initid='%d' and revision = %d", $initid, $revision) , $id, true, true);
            if ($id > 0) return intval($id);
            // it is not really on initid
            simpleQuery($dbaccess, sprintf("select id from docread where initid=(select initid from docread where id=%d) and revision = %d", $initid, $revision) , $id, true, true);
            
            if ($id > 0) return intval($id);
        } else {
            if (preg_match('/^state:(.+)$/', $revision, $regStates)) {
                simpleQuery($dbaccess, sprintf("select id from docread where initid='%d' and state = '%s' and locked = -1 order by id desc", $initid, pg_escape_string($regStates[1])) , $id, true, true);
                if ($id > 0) return intval($id);
                // it is not really on initid
                simpleQuery($dbaccess, sprintf("select id from docread where initid=(select initid from docread where id=%d) and state = '%s' and locked = -1 order by id desc", $initid, pg_escape_string($regStates[1])) , $id, true, true);
                
                if ($id > 0) return intval($id);
            }
        }
        return null;
    }
    /**
     * Initialize document object
     *
     * The document is not yet recorded to database and has no identifier
     * @param int|string $familyIdentifier
     * @throws Exception
     * @return \Dcp\Family\Document
     */
    static public function initializeDocument($familyIdentifier)
    {
        
        $famId = self::getFamilyIdFromName($familyIdentifier);
        
        if (empty($famId)) {
            throw new Exception("APIDM0001", $familyIdentifier);
        }
        /**
         * @var \DocFam $family
         */
        $family = self::getDocument($famId);
        if ($family === null) {
            throw new Exception("APIDM0002", $familyIdentifier, $famId);
        }
        
        self::cache()->addDocument($family);
        
        $classname = "Doc" . $famId;
        self::requireFamilyClass($family->id);
        /* @var \Doc $doc */
        $doc = new $classname(self::getDbAccess());
        
        $doc->revision = "0";
        $doc->doctype = $doc->defDoctype; // it is a new  document (not a familly)
        $doc->fromid = $famId;
        $doc->fromname = $doc->attributes->fromname;
        
        $doc->icon = $family->icon; // inherit from its familly
        $doc->usefor = $family->usefor; // inherit from its familly
        $doc->atags = $family->atags;
        
        $doc->applyMask();
        return $doc;
    }
    
    protected static function requireFamilyClass($familyId)
    {
        if (!is_numeric($familyId)) {
            throw new Exception("APIDM0102", $familyId);
        }
        $classFilePath = sprintf("FDLGEN/Class.Doc%d.php", $familyId);
        require_once ($classFilePath);
    }
    /**
     * Create document object
     *
     * The document is not yet recorded to database and has no identifier
     * @param int|string $familyIdentifier
     * @param bool $control
     * @param bool $useDefaultValues
     * @throws Exception
     * @return \Dcp\Family\Document
     */
    static public function createDocument($familyIdentifier, $control = true, $useDefaultValues = true)
    {
        $doc = self::initializeDocument($familyIdentifier);
        /**
         * @var \DocFam $family
         */
        $family = self::getFamily($doc->fromid);
        
        if ($control) {
            $err = $family->control('create');
            if ($err != "") {
                throw new Exception("APIDM0003", $familyIdentifier);
            }
        }
        
        $doc->wid = $family->wid;
        $doc->setProfil($family->cprofid); // inherit from its familly
        $doc->setCvid($family->ccvid); // inherit from its familly
        if ($useDefaultValues) {
            $doc->setDefaultValues($family->getDefValues());
        }
        $doc->applyMask();
        return $doc;
    }
    /**
     * Create document object
     *
     * The document is not yet recorded to database and has no identifier
     * this document has no profile. It will be destroyed by dynacaseDbCleaner wsh program
     * @param int|string $familyIdentifier
     * @param bool $useDefaultValues
     * @return \Dcp\Family\Document
     */
    static public function createTemporaryDocument($familyIdentifier, $useDefaultValues = true)
    {
        $doc = self::initializeDocument($familyIdentifier);
        $doc->doctype = 'T';
        if ($useDefaultValues) {
            /**
             * @var \DocFam $family
             */
            $family = self::getDocument($doc->fromid, false);
            $doc->setDefaultValues($family->getDefValues());
        }
        $doc->applyMask();
        return $doc;
    }
    /**
     * Get document's values
     *
     * retrieve raw values directly from database
     * @param int|string $documentIdentifier
     * @param bool $latest
     * @api Get indexed array with property values and attribute values
     * @return string[] indexed properties and attributes values
     */
    static public function getRawDocument($documentIdentifier, $latest = true)
    {
        
        $id = self::getIdentifier($documentIdentifier, $latest);
        if ($id > 0) {
            $sql = sprintf("select * from docread where id=%d", $id);
            simpleQuery(self::getDbAccess() , $sql, $result, false, true);
            $avalues = json_decode($result["avalues"]);
            if ($avalues) {
                foreach ($avalues as $aid => $v) {
                    $result[$aid] = $v;
                }
            }
            unset($result["avalues"]);
            return $result;
        }
        return null;
    }
    /**
     * Create document object from document's values
     *
     * No call to database is done to retrieve attributes values
     * @param string[] $rawDocument
     * @throws Exception APIDM0104, APIDM0105
     * @return \Doc
     */
    static public function getDocumentFromRawDocument(array $rawDocument)
    {
        
        if (empty($rawDocument["id"]) || !self::getIdentifier($rawDocument["id"], false)) {
            throw new Exception("APIDM0104", print_r($rawDocument, true));
        }
        if ($rawDocument["doctype"] == "C") {
            $d = new \DocFam();
        } else {
            if ($rawDocument["fromid"] > 0) {
                $d = self::initializeDocument($rawDocument["fromid"]);
            } else {
                throw new Exception("APIDM0105", print_r($rawDocument, true));
            }
        }
        $d->affect($rawDocument);
        return $d;
    }
    /**
     * Get document title
     *
     * Retrieve raw title of document directly from database.
     * No use any cache
     * No use Doc::getCustomTitle(), so dynamic title cannot be get with this method
     *
     * @see Doc::getTitle()
     * @param int|string $documentIdentifier
     * @param bool $latest
     * @return string|null
     */
    static public function getTitle($documentIdentifier, $latest = true)
    {
        
        $id = self::getIdentifier($documentIdentifier, $latest);
        if ($id > 0) {
            $sql = sprintf("select title from docread where id=%d", $id);
            simpleQuery(self::getDbAccess() , $sql, $result, true, true);
            
            return $result;
        }
        
        return null;
    }
    /**
     * Get document properties
     *
     * Retrieve proterties of document directly from database.
     * No use any cache
     *
     * @param int|string $documentIdentifier
     * @param bool $latest
     * @param array $returnProperties list properties to return, if empty return all properties.
     * @return string[] indexed array of properties
     */
    static public function getDocumentProperties($documentIdentifier, array $returnProperties, $latest = true)
    {
        $id = self::getIdentifier($documentIdentifier, $latest);
        if ($id > 0) {
            if (count($returnProperties) == 0) {
                $returnProperties = array_keys(\Doc::$infofields);
            }
            $sqlSelect = array();
            foreach ($returnProperties as $rProp) {
                $sqlSelect[] = sprintf('"%s"', pg_escape_string($rProp));
            }
            $sql = sprintf("select %s from docread where id=%d", implode(',', $sqlSelect) , $id);
            simpleQuery(self::getDbAccess() , $sql, $result, false, true);
            
            return $result;
        }
        
        return null;
    }
    /**
     * Get raw value for a document
     *
     * Retrieve raw value of document directly from database
     *
     * @param string|int $documentIdentifier
     * @param string $dataIdentifier attribute or property identifier
     * @param bool $latest
     * @param bool $useCache if true use cache object if exists
     * @return string the value
     */
    static public function getRawValue($documentIdentifier, $dataIdentifier, $latest = true, $useCache = true)
    {
        $id = self::getIdentifier($documentIdentifier, $latest);
        if ($id > 0) {
            $dataIdentifier = strtolower($dataIdentifier);
            if ($useCache) {
                if (self::cache()->isDocumentIdInCache($id)) {
                    $cacheDoc = self::getDocument($id);
                    return $cacheDoc->getRawValue($dataIdentifier);
                }
            }
            //$sql=sprintf("select avalues->'%s' from docread where id=%d", pg_escape_string($dataIdentifier), $id); // best perfo but cannot distinct null values and id not exists
            $fromid = self::getFromId($id);
            if ($fromid > 0) {
                $sql = sprintf("select %s from doc%d where id=%d", pg_escape_string($dataIdentifier) , $fromid, $id);
                simpleQuery(self::getDbAccess() , $sql, $result, true, true);
                if ($result === null) {
                    $result = '';
                } elseif ($result === false) {
                    $result = null;
                }
                return $result;
            }
        }
        
        return null;
    }
    /**
     * Return numerical id
     * @param int|string $documentIdentifier document identifier
     * @param bool $latest if true search latest id
     * @return int
     */
    static public function getIdentifier($documentIdentifier, $latest)
    {
        if (empty($documentIdentifier)) {
            return 0;
        }
        if (!is_numeric($documentIdentifier)) {
            $id = self::getIdFromName($documentIdentifier);
        } else {
            $id = intval($documentIdentifier);
            if ($latest) {
                $lid = self::getLatestDocumentId($id);
                if ($lid > 0) {
                    $id = $lid;
                }
            }
        }
        return $id;
    }
    /**
     * Get latest id from document name (logical name)
     * @param string $documentName
     * @throws Exception
     * @api Get document identifier fro logical name
     * @return int
     */
    static public function getIdFromName($documentName)
    {
        static $first = true;
        
        if (empty($documentName)) {
            return 0;
        }
        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/i', $documentName)) {
            throw new Exception("APIDM0101", print_r($documentName, true));
        }
        
        $dbid = self::getDbResource();
        
        $id = null;
        
        if ($first) {
            pg_prepare($dbid, "dm_getidfromname", 'select id from docname where name=$1');
            $first = false;
        }
        $result = pg_execute($dbid, "dm_getidfromname", array(
            trim($documentName)
        ));
        $n = pg_num_rows($result);
        if ($n > 0) {
            $arr = pg_fetch_array($result, ($n - 1) , PGSQL_ASSOC);
            $id = intval($arr["id"]);
        }
        return $id;
    }
    /**
     * Get document name (logical name) from numerical identifier
     * @param int $documentId
     * @api Get logical name of a document
     * @return string|null return null if id not found
     */
    static public function getNameFromId($documentId)
    {
        static $first = true;
        
        $dbid = self::getDbResource();
        $id = intval($documentId);
        $name = null;
        if ($first) {
            pg_prepare($dbid, "dm_getNameFromId", 'select name from docread where id=$1');
            $first = false;
        }
        $result = pg_execute($dbid, "dm_getNameFromId", array(
            $id
        ));
        $n = pg_num_rows($result);
        if ($n > 0) {
            $arr = pg_fetch_array($result, ($n - 1) , PGSQL_ASSOC);
            $name = $arr["name"];
        }
        return $name;
    }
    /**
     * Get Family Id
     * @param string $famName familyName
     * @param bool $reset
     * @return string|null return null if id not found
     */
    static public function getFamilyIdFromName($famName, $reset = false)
    {
        static $tFamIdName = null;
        if (!isset($tFamIdName) || $reset) {
            $tFamIdName = array();
            simpleQuery(self::getDbAccess() , "select id, name from docfam", $r);
            
            foreach ($r as $v) {
                if ($v["name"] != "") $tFamIdName[strtoupper($v["name"]) ] = $v["id"];
            }
        }
        if (is_numeric($famName)) {
            if (in_array($famName, $tFamIdName)) {
                return $famName;
            } else {
                if (!$reset) {
                    return self::getFamilyIdFromName($famName, true);
                }
            }
        } else {
            $name = strtoupper($famName);
            if (isset($tFamIdName[$name])) return $tFamIdName[$name];
        }
        
        return 0;
    }
    static protected function getDbAccess()
    {
        static $dbaccess = null;
        
        if ($dbaccess === null) {
            $dbaccess = getDbAccess();
        }
        return $dbaccess;
    }
    static protected function getDbResource()
    {
        static $dbid = null;
        
        if ($dbid === null) {
            $dbid = getDbId(self::getDbAccess());
        }
        return $dbid;
    }
    /**
     * Get document fromid
     * @param int|string $documentId document identifier
     * @return null|int
     */
    static protected function getFromId($documentId)
    {
        if (!is_numeric($documentId)) {
            $documentId = self::getIdFromName($documentId);
        }
        $dbid = self::getDbResource();
        $fromid = null;
        
        $result = pg_query($dbid, sprintf("select fromid from docfrom where id=%d", $documentId));
        if ($result) {
            if (pg_num_rows($result) > 0) {
                $arr = pg_fetch_array($result, 0, PGSQL_ASSOC);
                $fromid = intval($arr["fromid"]);
            }
        }
        
        return $fromid;
    }
    /**
     * Return Document Cache Object
     * @return Cache
     */
    static public function &cache()
    {
        static $documentCache = null;
        if ($documentCache === null) {
            $documentCache = new Cache();
        }
        return $documentCache;
    }
}
