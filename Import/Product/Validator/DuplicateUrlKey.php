<?php declare(strict_types=1);

namespace MaxServ\FireGentoFastSimpleImportWrapper\Import\Product\Validator;

use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface;
use Magento\CatalogImportExport\Model\Import\Product\Validator\AbstractImportValidator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class DuplicateUrlKey extends AbstractImportValidator implements RowValidatorInterface
{
    public function __construct(
        private ResourceConnection $resourceConnection,
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isValid($value)
    {
        if (!isset($value['url_key'])) {
            return true;
        }

        $storeId = 0;
        if (isset($value['store_id'])) {
            $storeId = (int)$value['store_id'];
        }
        // @todo: Do something when $value['website_id'] is set

        if ($this->hasDuplicates($value['url_key'], $storeId)) {
            $this->_addMessages(['URL key is duplicate']);
            return false;
        }

        return true;
    }

    public function hasDuplicates(string $oldUrl, int $storeId = 0): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from(
            $this->resourceConnection->getTableName('url_rewrite'),
            [new \Zend_Db_Expr('1')]
        );

        $select->where('request_path = "'.$oldUrl.$this->getUrlSuffix($storeId).'"');
        $select->where('entity_type != "product"');
        
        if ($storeId > 0) {
            $select->where('store_id = "'.$storeId.'"');
        }

        $select->limit(1);
        $results = $connection->fetchAll($select);

        return count($results) > 0;
    }

    private function getUrlSuffix(int $storeId = 0): string
    {
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeCode = null;

        if ($storeId > 0) {
            $scopeCode = $storeId;
            $scopeType = 'store'; // @todo: Use a constant for this?
        }

        return (string)$this->scopeConfig->getValue(
            'catalog/seo/product_url_suffix',
            $scopeType,
            $scopeCode
        );
    }
}