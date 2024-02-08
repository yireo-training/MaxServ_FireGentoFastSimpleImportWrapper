<?php declare(strict_types=1);

namespace MaxServ\ProductImportQueueTest\Test\Integration\Model;

use FireGento\FastSimpleImport\Model\Importer;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;

class ProductImportTest extends TestCase
{
    /**
     * @return void
     * @throws LocalizedException
     * @magentoAppArea adminhtml
     */
    public function testProcessCsvWithNothing()
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
        $csvData = $this->getDataFromCsvFile($csvFile);
        $this->getImporter()->processImport($csvData);
    }

    private function getDataFromCsvFile(string $csvFile): array
    {
        $products = [];
        $heading = [];
        $i = 0;
        if (($handle = fopen($csvFile, "r")) === false) {
            return [];
        }

        while (($row = fgetcsv($handle, 1000, ";")) !== false) {
            if ($i === 0) {
                $heading = $row;
                $i++;
                continue;
            }

            foreach ($heading as $rowName) {
                $data[$rowName] = '';
            }

            foreach ($row as $index => $value) {
                $rowName = $heading[$index];
                $data[$rowName] = $value;
            }

            $products[] = $data;
            $i++;
        }

        fclose($handle);

        return $products;
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
