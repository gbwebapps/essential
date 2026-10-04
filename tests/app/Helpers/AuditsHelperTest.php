<?php declare(strict_types = 1);

namespace App\Helpers;

use App\Models\Backend\AuditsModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;

class AuditsHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        helper('audits');
    }

    protected function tearDown(): void
    {
        Factories::reset();

        parent::tearDown();
    }

    public function testLogAdminActivityReturnsTrue(): void
    {
        $currentAdmin = (object) [
            'uuid' => 'admin-uuid'
        ];

        $model = $this->createMock(AuditsModel::class);

        $model->expects($this->once())
            ->method('logActivity')
            ->with(
                'UPDATE',
                'admins',
                'Test details',
                $currentAdmin
            )
            ->willReturn(true);

        Factories::injectMock(
            'models',
            AuditsModel::class,
            $model
        );

        $result = log_admin_activity(
            'UPDATE',
            'admins',
            'Test details',
            $currentAdmin
        );

        $this->assertTrue($result);
    }

    public function testLogAdminActivityReturnsFalse(): void
    {
        $model = $this->createMock(AuditsModel::class);

        $model->expects($this->once())
            ->method('logActivity')
            ->with(
                'DELETE',
                'users',
                'Test failure',
                null
            )
            ->willReturn(false);

        Factories::injectMock(
            'models',
            AuditsModel::class,
            $model
        );

        $result = log_admin_activity(
            'DELETE',
            'users',
            'Test failure'
        );

        $this->assertFalse($result);
    }
}