<?php declare(strict_types=1);

namespace MaxServ\ProductImportQueueTest\Test\Integration\Model;

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
        $productsArray = [
            [
                'entity' => 'product',
                'sku' => 'firegento-test',
                'attribute_set_code' => 'Default',
                'product_type' => 'simple',
                'product_websites' => 'base',
                'name' => 'FireGento Test Product',
                'price' => '14.0000',
            ],
        ];

        $this->getImporter()->processImport($productsArray);
        $this->assertProductSkuExists('firegento-test');
        //print_r($productImport->getLogTrace());
        //print_r($productImport->getErrorMessages());
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
        $csvFile = $moduleDir.'/files/original.csv';
        $this->assertTrue(is_file($csvFile));
        $records = $this->getDataFromCsvFile($csvFile);

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
        $productRepository = ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
        try {
            $product = $productRepository->get($productSku);
        } catch (NoSuchEntityException $e) {
            $this->assertEquals($productSku, null, 'Product does not exist');
        }

        $this->assertEquals($productSku, $product->getSku());
    }
}
