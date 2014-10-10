<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

use Dcp\HttpApi\V1\DocManager;

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

    /**
     * @param string|int $familyId
     * @throws Exception
     */
    public function __construct($familyId)
    {
        parent::__construct();
        $this->family = DocManager::getFamily($familyId);
        if (!$this->family) {
            $exception = new Exception("API0200", $familyId);
            $exception->setHttpStatus("404", "Family not found");
            throw $exception;
        }
        $this->enumid = $this->getRessourceIdentifier();
        $this->parseParameters();
    }
    //region CRUD part
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
    /**
     * Get ressource
     *
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function read($resourceId)
    {
        $attribute = $this->family->getAttribute($resourceId);
        if (!$attribute) {
            throw new Exception("API0400", $resourceId, $this->family->name);
        }
        if ($attribute->type !== "enum") {
            throw new Exception("API0401", $resourceId, $attribute->type, $this->family->name);
        }
        /**
         * @var \NormalAttribute $attribute
         */
        $enums = $attribute->getEnumLabel();
        $info = array(
            "uri" => sprintf("enums/%s/%s", $this->family->name, $resourceId) ,
            "label" => $attribute->getLabel()
        );
        
        $filterKeyword = $this->getFilterKeyword();
        $filterOperator = $this->getOperatorFilter();
        $pattern = '';
        if ($filterKeyword !== "") {
            switch ($filterOperator) {
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
            "operator" => $filterOperator,
            "keyword" => $filterKeyword
        );
        $info["enumItems"] = $enumItems;
        
        return $info;
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
    //endregion CRUD part
    /**
     * Analyze the parameters of the request
     *
     * @throws Exception
     */
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
     * Register the keyword
     *
     * @param $word
     */
    protected function setKeywordFilter($word)
    {
        if ($word === null) {
            $word = '';
        }
        $this->keywordFilter = $word;
    }
    /**
     * Return the operator filter
     *
     * @return string
     */
    public function getOperatorFilter()
    {
        return $this->operatorFilter;
    }
    /**
     * Set the operator filter
     *
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
    /**
     * Return the filter keyword
     *
     * @return string
     */
    protected function getFilterKeyword()
    {
        return $this->keywordFilter;
    }

}
