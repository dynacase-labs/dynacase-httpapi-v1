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
    protected $keywordFilter = '';
    protected $operatorFilter = self::containsOperator;
    protected $enumid = null;

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
            $exception = new Exception("API0400", $resourceId, $this->family->name);
            $exception->setHttpStatus("404", "Attribute $resourceId not found");
            throw $exception;
        }
        if ($attribute->type !== "enum") {
            $exception = new Exception("API0401", $resourceId, $attribute->type, $this->family->name);
            $exception->setHttpStatus("403", "Attribute $resourceId is not an enum");
            throw $exception;
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
        if (isset($this->contentParameters["keyword"])) {
            $this->setKeywordFilter($this->contentParameters["keyword"]);
        }
        if (isset($this->contentParameters["operator"])) {
            $this->setOperatorFilter($this->contentParameters["operator"]);
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

    /**
     * Initialize the current family
     *
     * @param array $array
     * @throws Exception
     */
    public function setUrlParameters(Array $array) {
        parent::setUrlParameters($array);
        $familyId = isset($this->urlParameters["familyId"]) ? $this->urlParameters["familyId"] : false;
        $this->family = DocManager::getFamily($this->urlParameters["familyId"]);
        if (!$this->family) {
            $exception = new Exception("API0200", $familyId);
            $exception->setHttpStatus("404", "Family not found");
            throw $exception;
        }
        $this->enumid = $this->urlParameters["identifier"];
        $this->parseParameters();
    }

}
