<?php declare(strict_types = 1);

namespace Tests\App\Controllers\Backend;

use CodeIgniter\Config\Factories;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use Config\Services;
use Modules\Backend\Brands\Controllers\BrandsController;
use Modules\Backend\Brands\Models\BrandsModel;
use Modules\Backend\Categories\Controllers\CategoriesController;
use Modules\Backend\Categories\Models\CategoriesModel;
use Modules\Backend\Products\Controllers\ProductsController;
use Modules\Backend\Products\Models\ProductsModel;
use PHPUnit\Framework\Attributes\DataProvider;

/** Verifica che i conteggi viaggino con la lista, e solo dopo una richiesta valida. */
class WarehouseTrashCountsTest extends CIUnitTestCase
{
    use ControllerTestTrait;

    public static function controllers(): array
    {
        return [
            'brands' => [BrandsController::class, BrandsModel::class],
            'categories' => [CategoriesController::class, CategoriesModel::class],
            'products' => [ProductsController::class, ProductsModel::class],
        ];
    }

    protected function tearDown(): void
    {
        Factories::reset('models');
        Services::reset(true);

        parent::tearDown();
    }

    /** Prepara il contesto HTTP, senza caricare dati applicativi reali. */
    private function requestList(string $controllerClass, string $modelClass, bool $success): array
    {
        $authorization = $this->getMockBuilder(\stdClass::class)->addMethods(['currentAdmin'])->getMock();
        $authorization->method('currentAdmin')->willReturn((object) ['id' => 1, 'firstname' => 'System', 'lastname' => 'Test', 'superadmin' => 1]);
        Services::injectMock('authorization', $authorization);

        $methods = ['showAllValidationRules', 'showAllSearchValidationRules', 'getData', 'getTrashCounts'];

        if ($modelClass === CategoriesModel::class):
            $methods[] = 'getCategoryHierarchy';
        endif;

        $model = $this->getMockBuilder($modelClass)->disableOriginalConstructor()->onlyMethods($methods)->getMock();
        $model->method('showAllValidationRules')->willReturn(['page' => 'required|numeric']);
        $model->method('showAllSearchValidationRules')->willReturn(['searchFields' => 'permit_empty']);
        $model->method('getData')->willReturn($success
            ? ['result' => true, 'records' => [], 'pagination' => ['page' => 1, 'limit' => 5, 'totalRows' => 0], 'lastItemPage' => 0]
            : ['result' => false, 'message' => 'Lista non disponibile']);
        $model->expects($success ? $this->once() : $this->never())->method('getTrashCounts')
              ->willReturn(['active' => 8, 'suspended' => 0, 'trashed' => 2, 'all' => 10]);

        if ($modelClass === CategoriesModel::class):
            $model->method('getCategoryHierarchy')->willReturn(['tree' => [], 'pathsById' => [], 'pathsByUuid' => []]);
        endif;

        Factories::injectMock('models', $modelClass, $model);

        $config = config('App');
        $request = new IncomingRequest($config, new SiteURI($config, 'http://essential.test'), null, new UserAgent());
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $request->setGlobal('post', ['page' => '1', 'rows' => '5']);

        $result = $this->withRequest($request)->controller($controllerClass)->execute('showAll');

        return json_decode($result->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    #[DataProvider('controllers')]
    public function testSuccessfulListIncludesGlobalCounts(string $controllerClass, string $modelClass): void
    {
        $json = $this->requestList($controllerClass, $modelClass, true);

        $this->assertTrue($json['result']);
        $this->assertArrayHasKey('output', $json);
        $this->assertSame(['active' => 8, 'suspended' => 0, 'trashed' => 2, 'all' => 10], $json['trashCounts']);
    }

    #[DataProvider('controllers')]
    public function testFailedListDoesNotFetchCounts(string $controllerClass, string $modelClass): void
    {
        $json = $this->requestList($controllerClass, $modelClass, false);

        $this->assertFalse($json['result']);
        $this->assertArrayNotHasKey('trashCounts', $json);
    }
}
