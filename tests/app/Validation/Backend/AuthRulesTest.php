<?php declare(strict_types = 1);

namespace App\Validation\Backend;

use App\Models\Backend\AuthModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;

class AuthRulesTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Factories::reset();

        parent::tearDown();
    }

    public function testCheckTokenRuleReturnsTrueWhenTokenIsValid(): void
    {
        $authModel = $this->createMock(AuthModel::class);

        $authModel->expects($this->once())
            ->method('checkAuthToken')
            ->with('valid-token')
            ->willReturn(true);

        Factories::injectMock(
            'models',
            AuthModel::class,
            $authModel
        );

        $rules = new AuthRules();

        $result = $rules->checkTokenRule('valid-token');

        $this->assertTrue($result);
    }

    public function testCheckTokenRuleReturnsFalseWhenTokenIsInvalid(): void
    {
        $authModel = $this->createMock(AuthModel::class);

        $authModel->expects($this->once())
            ->method('checkAuthToken')
            ->with('invalid-token')
            ->willReturn(false);

        Factories::injectMock(
            'models',
            AuthModel::class,
            $authModel
        );

        $rules = new AuthRules();

        $result = $rules->checkTokenRule('invalid-token');

        $this->assertFalse($result);
    }
}