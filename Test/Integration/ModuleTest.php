<?php declare(strict_types=1);

namespace MaxServ\FireGentoFastSimpleImportWrapper\Test\Integration;

use PHPUnit\Framework\TestCase;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsEnabled;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsRegistered;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsRegisteredForReal;

class ModuleTest extends TestCase
{
    use AssertModuleIsEnabled;
    use AssertModuleIsRegistered;
    use AssertModuleIsRegisteredForReal;

    public function testModuleBasics()
    {
        $moduleName = 'MaxServ_FireGentoFastSimpleImportWrapper';
        $this->assertModuleIsEnabled($moduleName);
        $this->assertModuleIsRegistered($moduleName);
        $this->assertModuleIsRegisteredForReal($moduleName);

        $moduleName = 'FireGento_FastSimpleImport';
        $this->assertModuleIsEnabled($moduleName);
        $this->assertModuleIsRegistered($moduleName);
        $this->assertModuleIsRegisteredForReal($moduleName);
    }
}
