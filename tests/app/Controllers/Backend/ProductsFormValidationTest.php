<?php declare(strict_types = 1);

namespace Tests\App\Controllers\Backend;

use CodeIgniter\Config\Factories;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use Config\Services;
use Modules\Backend\Products\Controllers\ProductsController;
use Modules\Backend\Products\Models\ProductsModel;
use PHPUnit\Framework\Attributes\DataProvider;

/** Esegue le regole reali dei form senza salvare prodotti o associazioni. */
class ProductsFormValidationTest extends CIUnitTestCase
{
    use ControllerTestTrait;

    private const UUID = '79dda187-1b4a-4798-a2f9-250872ff1edc';

    protected function tearDown(): void
    {
        Factories::reset('models');
        Services::reset(true);
        parent::tearDown();
    }

    /** Inietta le sole letture di contesto; la validazione rimane quella del modulo. */
    private function submitForm(string $action, array $posts): array
    {
        Services::resetSingle('validation');
        $authorization = $this->getMockBuilder(\stdClass::class)->addMethods(['currentAdmin'])->getMock();
        $authorization->method('currentAdmin')->willReturn((object) ['id' => 1, 'firstname' => 'System', 'lastname' => 'Test', 'superadmin' => 1]);
        Services::injectMock('authorization', $authorization);

        $model = $this->getMockBuilder(ProductsModel::class)->disableOriginalConstructor()
                      ->onlyMethods(['getBrandOptions', 'getCategoryOptions', 'getProduct', 'add', 'edit'])->getMock();
        $model->method('getBrandOptions')->willReturn([]);
        $model->method('getCategoryOptions')->willReturn([]);
        $model->method('getProduct')->willReturn(['result' => true, 'row' => (object) ['uuid' => self::UUID, 'deleted_at' => null]]);
        $model->expects($this->never())->method('add');
        $model->expects($this->never())->method('edit');
        Factories::injectMock('models', ProductsModel::class, $model);

        $config = config('App');
        $request = new IncomingRequest($config, new SiteURI($config, 'http://products-validation.test'), null, new UserAgent());
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $request->setGlobal('post', $action === 'edit' ? ['uuid' => self::UUID] + $posts : $posts);

        $result = $this->withRequest($request)->controller(ProductsController::class)->execute($action);

        return json_decode($result->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function formActions(): array
    {
        return [['add'], ['edit']];
    }

    #[DataProvider('formActions')]
    public function testEmptyFormReturnsAllErrorsWithUsableFieldNames(string $action): void
    {
        $json = $this->submitForm($action, []);

        $this->assertNotEmpty($json['message']);
        $this->assertSame(['sku', 'product', 'quantity', 'price', 'status', 'brand_uuids', 'category_uuids'], array_keys($json['errors']));
        foreach ($json['errors'] as $message):
            $this->assertNotEmpty($message);
        endforeach;
    }

    #[DataProvider('formActions')]
    public function testIndexedRelationErrorsAreDisplayedOnTheirSelect(string $action): void
    {
        $json = $this->submitForm($action, ['brand_uuids' => ['not-a-uuid', 'also-invalid'], 'category_uuids' => ['bad']]);

        $this->assertArrayHasKey('brand_uuids', $json['errors']);
        $this->assertArrayHasKey('category_uuids', $json['errors']);
        $this->assertCount(7, $json['errors']);
        $this->assertArrayNotHasKey('brand_uuids.0', $json['errors']);
        $this->assertArrayNotHasKey('brand_uuids.1', $json['errors']);
        $this->assertArrayNotHasKey('category_uuids.0', $json['errors']);
    }

    #[DataProvider('formActions')]
    public function testScalarRelationsRemainInvalidAndValidUuidArraysDoNotProduceErrors(string $action): void
    {
        $invalid = $this->submitForm($action, ['brand_uuids' => 'invalid', 'category_uuids' => 'invalid']);
        $this->assertArrayHasKey('brand_uuids', $invalid['errors']);
        $this->assertArrayHasKey('category_uuids', $invalid['errors']);

        $valid = $this->submitForm($action, ['brand_uuids' => [self::UUID], 'category_uuids' => [self::UUID]]);
        $this->assertArrayNotHasKey('brand_uuids', $valid['errors']);
        $this->assertArrayNotHasKey('category_uuids', $valid['errors']);
        $this->assertArrayHasKey('sku', $valid['errors']);
    }

    public function testNormalizationKeepsParentErrorsAndImagePreviewKeys(): void
    {
        $validator = Services::validation(null, false);
        $validator->setError('brand_uuids', 'Parent error');
        $validator->setError('brand_uuids.*', 'Wildcard error');
        $validator->setError('category_uuids.3', 'Category error');
        $validator->setError('images.preview-id', 'Image error');

        $controller = new ProductsController();
        (new \ReflectionProperty($controller, 'validator'))->setValue($controller, $validator);
        $errors = (new \ReflectionMethod($controller, 'getFormValidationErrors'))->invoke($controller);

        $this->assertSame([
            'brand_uuids' => 'Parent error',
            'images.preview-id' => 'Image error',
            'category_uuids' => 'Category error',
        ], $errors);
    }
}
