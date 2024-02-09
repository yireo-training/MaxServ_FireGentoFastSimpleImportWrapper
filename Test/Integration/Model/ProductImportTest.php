<?php declare(strict_types=1);

namespace MaxServ\ProductImportQueueTest\Test\Integration\Model;

use FireGento\FastSimpleImport\Exception\ValidationException;
use FireGento\FastSimpleImport\Model\Importer;
use Iterator;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use League\Csv\Reader;

class ProductImportTest extends TestCase
{
    /**
     * @return void
     * @throws LocalizedException
     * @magentoAppArea adminhtml
     */
    public function testProcessCsvWithBasicProduct()
    {
        $productSku = 'test-product';
        $this->getImporter()->processImport([$this->getBasicProductData($productSku)]);
        $this->assertProductSkuExists($productSku);
        $this->removeProductBySku($productSku);
    }

    /**
     * @return void
     * @throws LocalizedException
     * @magentoAppArea adminhtml
     */
    public function testProcessCsvWithBasicProductWithATitleTooLong()
    {
        $productSku = 'test-product';
        $productArray = $this->getBasicProductData($productSku);
        $productArray['name'] = str_repeat(chr(rand(1, 128)), 258);
        $this->expectExceptionMessage('Attribute name exceeded max length in rows: 1');
        $this->expectException(ValidationException::class);
        $this->getImporter()->processImport([$productArray]);
        $this->removeProductBySku($productSku);
    }

    /**
     * @return void
     * @throws LocalizedException
     * @magentoAppArea adminhtml
     */
    public function testProcessCsvWithBasicProductWithASkuTooLong()
    {
        $productSku = 'test-'.str_repeat(chr(rand(1, 128)), 60); // @todo: Make sure SKU never contains whitespaces
        $productArray = $this->getBasicProductData($productSku);
        $this->expectExceptionMessage('Attribute sku exceeded max length in rows: 1');
        $this->expectException(ValidationException::class);
        $this->getImporter()->processImport([$productArray]);
        $this->removeProductBySku($productSku);
    }

    /**
     * @return void
     * @throws LocalizedException
     * @magentoAppArea adminhtml
     */
    public function testProcessCsvWithBasicProductWithValidColor()
    {
        $productSku = 'test-color-product';
        $productArray = $this->getBasicProductData($productSku);
        $productArray['color'] = 'Blue';
        $productArray['attribute_set_code'] = 'Bag';
        $this->getImporter()->processImport([$productArray]);
        $this->assertProductAttributeHasValue($productSku, 'color', 56);
        $this->removeProductBySku($productSku);
    }

    /**
     * @return void
     * @throws LocalizedException
     * @magentoAppArea adminhtml
     */
    public function testProcessCsvWithBasicProductWithInvalidColor()
    {
        $productSku = 'test-product';
        $productArray = $this->getBasicProductData($productSku);
        $productArray['color'] = 'foobar';
        $this->expectExceptionMessage('Value for \'color\' attribute contains incorrect value, see acceptable values on settings specified for Admin in rows: 1');
        $this->expectException(ValidationException::class);
        $this->getImporter()->processImport([$productArray]);
        $this->removeProductBySku($productSku);
    }

    /**
     * @return void
     * @throws LocalizedException
     * @magentoAppArea adminhtml
     */
    public function testProcessCsvWithBasicProductWithFoobarValidation()
    {
        $productSku = 'test-product';
        $productArray = $this->getBasicProductData($productSku);
        $productArray['name'] = 'Foobar 42';
        $this->expectExceptionMessage('Foobar is 42');
        $this->expectException(ValidationException::class);
        $this->getImporter()->processImport([$productArray]);
        $this->removeProductBySku($productSku);
    }

    /**
     * @return void
     * @throws LocalizedException
     * @magentoAppArea adminhtml
     */
    public function testProcessCsvWithBasicProductWithIncorrectUrlKey()
    {
        // @todo: Create custom URL Rewrite rule with URL `test-product.html`
        $productSku = 'test-product';
        $productArray = $this->getBasicProductData();
        $productArray['url_key'] = 'test-product';
        $this->expectException(ValidationException::class);
        $this->getImporter()->processImport([$productArray]);
        $this->removeProductBySku($productSku);
    }

    /**
     * @return void
     * @throws LocalizedException
     * @magentoAppArea adminhtml
     */
    public function testProcessCsvWithOriginalCsv()
    {
        $moduleDir = ObjectManager::getInstance()->get(ComponentRegistrar::class)->getPath(
            'module',
            'MaxServ_FireGentoFastSimpleImportWrapper'
        );
        $csvFile = $moduleDir . '/files/original.csv';
        $this->assertTrue(is_file($csvFile));
        $records = $this->getDataFromCsvFile($csvFile);

        //$this->expectException(ValidationException::class);
        foreach ($records as $record) {
            $this->getImporter()->processImport([$record]);
        }
    }

    private function getDataFromCsvFile(string $csvFile): Iterator
    {
        $csv = Reader::createFromPath($csvFile, 'r');
        $csv->setDelimiter(';');
        $csv->setHeaderOffset(0);
        return $csv->getRecords();
    }

    private function getImporter(): Importer
    {
        return ObjectManager::getInstance()->create(Importer::class);
    }

    private function assertProductSkuExists(string $productSku)
    {
        $this->assertProductAttributeHasValue($productSku, 'sku', $productSku);
    }

    private function assertProductAttributeHasValue(string $productSku, string $attributeCode, mixed $value)
    {
        $productRepository = ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
        try {
            $product = $productRepository->get($productSku);
        } catch (NoSuchEntityException $e) {
            $this->assertEquals($productSku, null, 'Product does not exist');
        }

        $this->assertEquals($value, $product->getData($attributeCode));
    }
    
    private function removeProductBySku(string $productSku)
    {
        $productRepository = ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
        try {
            $product = $productRepository->get($productSku);
        } catch (NoSuchEntityException $e) {
            return;
        }
        $productRepository->get($productSku);
    }

    private function getBasicProductData(string $productSku = 'test-product'): array
    {
        return
            [
                'entity' => 'product',
                'sku' => $productSku,
                'attribute_set_code' => 'Default',
                'product_type' => 'simple',
                'product_websites' => 'base',
                'name' => $productSku,
                'price' => '14.0000',
            ];
    }
}
