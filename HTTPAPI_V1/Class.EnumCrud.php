<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

class EnumCrud extends Crud
{
    
    const startsOperator = "startswith";
    const containsOperator = "contains";
    /**
     * @var \DocFam
     */
    protected $family = null;
    protected $enumid = null;
    protected $keywordFilter = '';
    protected $operatorFilter = self::containsOperator;
    
    public function __construct($familyId)
    {
        $this->family = \Dcp\HttpApi\V1\DocManager::getFamily($familyId);
        if (!$this->family) {
            $e = new Exception("API0200", $familyId);
            $e->setHttpStatus("404", "Family not found");
            throw $e;
        }
        $this->enumid = $this->getRessourceIdentifier();
        $this->parseParameters();
    }
    
    protected function parseParameters()
    {
        if (isset($_GET["keyword"])) {
            $this->setKeywordFilter($_GET["keyword"]);
        }
        if (isset($_GET["operator"])) {
            $this->setOperatorFilter($_GET["operator"]);
        }
    }
    /**
     * Get ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function get($resourceId)
    {
        
        $oa = $this->family->getAttribute($resourceId);
        if (!$oa) {
            throw new Exception("API0400", $resourceId, $this->family->name);
        }
        if ($oa->type !== "enum") {
            throw new Exception("API0401", $resourceId, $oa->type, $this->family->name);
        }
        /**
         * @var \NormalAttribute $oa
         */
        $enums = $oa->getEnumLabel();
        $info = array(
            "uri" => sprintf("enums/%s/%s", $this->family->name, $resourceId) ,
            "label" => $oa->getLabel()
        );
        
        $filterKeyword = $this->getFilterKeyword();
        $filterOp = $this->getOperatorFilter();
        $pattern = '';
        if ($filterKeyword !== "") {
            switch ($filterOp) {
                case self::containsOperator:
                    $pattern = sprintf("/%s/i", str_replace("/", "\\/", preg_quote($filterKeyword)));
                    break;

                case self::startsOperator:
                    $pattern = sprintf("/^%s/i", str_replace("/", "\\/", preg_quote($filterKeyword)));
                    break;
            }
        }
        
        $enumItems = array();
        foreach ($enums as $key => $label) {
            $good = true;
            if ($filterKeyword !== "") {
                
                if (!preg_match($pattern, $label, $reg)) {
                    $good = false;
                }
            }
            
            if ($good && $key !== '' && $key !== ' ' && $key !== null) {
                $enumItems[] = array(
                    "key" => (string)$key,
                    "label" => $label
                );
            }
        }
        $info["filter"] = array(
            "operator" => $filterOp,
            "keyword" => $filterKeyword
        );
        $info["enumItems"] = $enumItems;
        
        return $info;
    }
    
    protected function setKeywordFilter($word)
    {
        if ($word === null) {
            $word = '';
        }
        $this->keywordFilter = $word;
    }
    /**
     * @return string
     */
    public function getOperatorFilter()
    {
        return $this->operatorFilter;
    }
    /**
     * @param string $operatorFilter
     * @throws Exception
     */
    public function setOperatorFilter($operatorFilter)
    {
        $availables = array(
            self::startsOperator,
            self::containsOperator
        );
        if (!in_array($operatorFilter, $availables)) {
            throw new Exception("API0402", $operatorFilter, implode(", ", $availables));
        }
        $this->operatorFilter = $operatorFilter;
    }
    
    protected function getFilterKeyword()
    {
        
        return $this->keywordFilter;
    }
    /**
     * Delete ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function delete($resourceId)
    {
        $e = new Exception("API0002", __METHOD__);
        $e->setHttpStatus("501", "Not implemented");
        throw $e;
    }
    /**
     * Update the ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function update($resourceId)
    {
        $e = new Exception("API0002", __METHOD__);
        $e->setHttpStatus("501", "Not implemented");
        throw $e;
    }
    /**
     * Create new ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $e = new Exception("API0002", __METHOD__);
        $e->setHttpStatus("501", "Not implemented");
        throw $e;
    }
}
