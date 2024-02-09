<?php declare(strict_types=1);

namespace MaxServ\FireGentoFastSimpleImportWrapper\Import\Product\Validator;

use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface;
use Magento\CatalogImportExport\Model\Import\Product\Validator\AbstractImportValidator;

class Foobar extends AbstractImportValidator implements RowValidatorInterface
{
    public function isValid($value)
    {
        if (isset($value['name'])) {
            if ($value['name'] === 'Foobar 42') {
                $this->_addMessages(['Foobar is 42']);
                return false;
            }
        }

        return true;
    }
}