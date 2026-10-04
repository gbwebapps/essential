<?php declare(strict_types = 1);

namespace App\Models\Backend;

class ToolsModelTest extends \CodeIgniter\Test\CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testGetAuditsStatsWithRecords()
    {
        $resCls =
            \CodeIgniter\Database\BaseResult::class;

        $resBld =
            $this->getMockBuilder(
                $resCls
            );

        $resBld->disableOriginalConstructor();

        $mockRes =
            $resBld->getMock();

        $rowObj =
            new \stdClass();

        $rowObj->total_audits =
            5;

        $rowObj->min_date =
            '2026-01-01 10:00:00';

        $rowObj->max_date =
            '2026-12-31 23:59:59';

        $mockRes->method(
            'getRow'
        )
        ->willReturn(
            $rowObj
        );

        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $mockDb->method(
            'query'
        )
        ->willReturn(
            $mockRes
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $model =
            new $modelCls();

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $model,
                get_class(
                    $model
                )
            );

        $bind();

        $result =
            $model->getAuditsStats();

        $expected =
            [
                'total'    => 5,
                'min_date' => '2026-01-01 10:00:00',
                'max_date' => '2026-12-31 23:59:59'
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testGetAuditsStatsEmpty()
    {
        $resCls =
            \CodeIgniter\Database\BaseResult::class;

        $resBld =
            $this->getMockBuilder(
                $resCls
            );

        $resBld->disableOriginalConstructor();

        $mockRes =
            $resBld->getMock();

        $rowObj =
            new \stdClass();

        $rowObj->total_audits =
            0;

        $rowObj->min_date =
            null;

        $rowObj->max_date =
            null;

        $mockRes->method(
            'getRow'
        )
        ->willReturn(
            $rowObj
        );

        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $mockDb->method(
            'query'
        )
        ->willReturn(
            $mockRes
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $model =
            new $modelCls();

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $model,
                get_class(
                    $model
                )
            );

        $bind();

        $result =
            $model->getAuditsStats();

        $expected =
            [
                'total'    => 0,
                'min_date' => null,
                'max_date' => null
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testBuildAuditDatesValid()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['checkAllowedFields']
        );

        $mockModel =
            $builder->getMock();

        $postsData =
            [
                'fromDate' => '2026-01-01',
                'toDate'   => '2026-01-31'
            ];

        $mockModel->method(
            'checkAllowedFields'
        )
        ->willReturn(
            $postsData
        );

        $refCls =
            new \ReflectionClass(
                $modelCls
            );

        $method =
            $refCls->getMethod(
                'buildAuditDates'
            );

        $method->setAccessible(
            true
        );

        $result =
            $method->invoke(
                $mockModel,
                $postsData
            );

        $expected =
            [
                'from' => '2026-01-01',
                'to'   => '2026-01-31'
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testBuildAuditDatesInvalid()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['checkAllowedFields']
        );

        $mockModel =
            $builder->getMock();

        $postsData =
            [
                'fromDate' => '2026-02-01',
                'toDate'   => '2026-01-15'
            ];

        $mockModel->method(
            'checkAllowedFields'
        )
        ->willReturn(
            $postsData
        );

        $refCls =
            new \ReflectionClass(
                $modelCls
            );

        $method =
            $refCls->getMethod(
                'buildAuditDates'
            );

        $method->setAccessible(
            true
        );

        $result =
            $method->invoke(
                $mockModel,
                $postsData
            );

        $this->assertFalse(
            $result
        );
    }

    public function testCountAuditsToDeleteReturnsFalse()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['buildAuditDates']
        );

        $mockModel =
            $builder->getMock();

        $mockModel->method(
            'buildAuditDates'
        )
        ->willReturn(
            false
        );

        $postsData =
            [];

        $result =
            $mockModel->countAuditsToDelete(
                $postsData
            );

        $this->assertFalse(
            $result
        );
    }

    public function testCountAuditsToDeleteSuccess()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['buildAuditDates']
        );

        $mockModel =
            $builder->getMock();

        $datesArr =
            [
                'from' => '2026-01-01',
                'to'   => '2026-01-31'
            ];

        $mockModel->method(
            'buildAuditDates'
        )
        ->willReturn(
            $datesArr
        );

        $resCls =
            \CodeIgniter\Database\BaseResult::class;

        $resBld =
            $this->getMockBuilder(
                $resCls
            );

        $resBld->disableOriginalConstructor();

        $mockRes =
            $resBld->getMock();

        $rowObj =
            new \stdClass();

        $rowObj->total =
            42;

        $mockRes->method(
            'getRow'
        )
        ->willReturn(
            $rowObj
        );

        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $sqlStr =
            'select count(*) as total from admins_audits where created_at between ? and ?';

        $bindings =
            [
                '2026-01-01',
                '2026-01-31'
            ];

        $mockDb->expects(
            $this->once()
        )
        ->method(
            'query'
        )
        ->with(
            $sqlStr,
            $bindings
        )
        ->willReturn(
            $mockRes
        );

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $mockModel,
                get_class(
                    $mockModel
                )
            );

        $bind();

        $postsData =
            [];

        $result =
            $mockModel->countAuditsToDelete(
                $postsData
            );

        $expected =
            [
                'count' => 42,
                'from'  => '2026-01-01',
                'to'    => '2026-01-31'
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testDeleteAuditsDatesInvalid()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['buildAuditDates']
        );

        $mockModel =
            $builder->getMock();

        $mockModel->method(
            'buildAuditDates'
        )
        ->willReturn(
            false
        );

        $postsData =
            [];

        $result =
            $mockModel->deleteAudits(
                $postsData
            );

        $langStr =
            lang('backend/tools.messages.startDateAfterEndDate');

        $expected =
            [
                'result'  => false,
                'message' => $langStr
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testDeleteAuditsZeroDeleted()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['buildAuditDates']
        );

        $mockModel =
            $builder->getMock();

        $datesArr =
            [
                'from' => '2026-01-01',
                'to'   => '2026-01-31'
            ];

        $mockModel->method(
            'buildAuditDates'
        )
        ->willReturn(
            $datesArr
        );

        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $sqlStr =
            'delete from admins_audits where created_at between ? and ?';

        $bindings =
            [
                '2026-01-01',
                '2026-01-31'
            ];

        $mockDb->expects(
            $this->once()
        )
        ->method(
            'query'
        )
        ->with(
            $sqlStr,
            $bindings
        );

        $mockDb->method(
            'affectedRows'
        )
        ->willReturn(
            0
        );

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $mockModel,
                get_class(
                    $mockModel
                )
            );

        $bind();

        $postsData =
            [];

        $result =
            $mockModel->deleteAudits(
                $postsData
            );

        $langStr =
            lang('backend/tools.messages.noAuditsDeleted');

        $expected =
            [
                'result'  => false,
                'message' => $langStr
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testDeleteAuditsSuccess()
    {
        if ( ! function_exists(__NAMESPACE__ . '\convertDate')):
            function convertDate(
                $a,
                $b
            ) {
                return $a;
            }
        endif;

        if ( ! function_exists(__NAMESPACE__ . '\log_admin_activity')):
            function log_admin_activity(
                $a,
                $b,
                $c,
                $d
            ) {
            }
        endif;

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['buildAuditDates']
        );

        $mockModel =
            $builder->getMock();

        $datesArr =
            [
                'from' => '2026-01-01',
                'to'   => '2026-01-31'
            ];

        $mockModel->method(
            'buildAuditDates'
        )
        ->willReturn(
            $datesArr
        );

        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $mockDb->method(
            'query'
        )
        ->willReturn(
            true
        );

        $delCount =
            5;

        $mockDb->method(
            'affectedRows'
        )
        ->willReturn(
            $delCount
        );

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $mockModel,
                get_class(
                    $mockModel
                )
            );

        $bind();

        $authCls =
            \stdClass::class;

        $authBld =
            $this->getMockBuilder(
                $authCls
            );

        $authBld->addMethods(
            ['currentAdmin']
        );

        $mockAuth =
            $authBld->getMock();

        $mockAuth->method(
            'currentAdmin'
        )
        ->willReturn(
            1
        );

        \Config\Services::injectMock(
            'authorization',
            $mockAuth
        );

        $postsData =
            [];

        $result =
            $mockModel->deleteAudits(
                $postsData
            );

        $fromLog =
            convertDate(
                '2026-01-01',
                'conversational'
            );

        $toLog =
            convertDate(
                '2026-01-31',
                'conversational'
            );

        $langStr =
            lang('backend/tools.messages.deleteAuditsSuccess');

        $expectedMsg =
            sprintf(
                $langStr,
                $delCount,
                $fromLog,
                $toLog
            );

        $expected =
            [
                'result'  => true,
                'message' => $expectedMsg
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testGetLogsStatsWithRecords()
    {
        $resCls =
            \CodeIgniter\Database\BaseResult::class;

        $resBld =
            $this->getMockBuilder(
                $resCls
            );

        $resBld->disableOriginalConstructor();

        $mockRes =
            $resBld->getMock();

        $rowObj =
            new \stdClass();

        $rowObj->total_logs =
            15;

        $rowObj->min_date =
            '2026-03-01 09:00:00';

        $rowObj->max_date =
            '2026-03-31 17:30:00';

        $mockRes->method(
            'getRow'
        )
        ->willReturn(
            $rowObj
        );

        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $mockDb->method(
            'query'
        )
        ->willReturn(
            $mockRes
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $model =
            new $modelCls();

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $model,
                get_class(
                    $model
                )
            );

        $bind();

        $result =
            $model->getLogsStats();

        $expected =
            [
                'total'    => 15,
                'min_date' => '2026-03-01 09:00:00',
                'max_date' => '2026-03-31 17:30:00'
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testGetLogsStatsEmpty()
    {
        $resCls =
            \CodeIgniter\Database\BaseResult::class;

        $resBld =
            $this->getMockBuilder(
                $resCls
            );

        $resBld->disableOriginalConstructor();

        $mockRes =
            $resBld->getMock();

        $rowObj =
            new \stdClass();

        $rowObj->total_logs =
            0;

        $rowObj->min_date =
            null;

        $rowObj->max_date =
            null;

        $mockRes->method(
            'getRow'
        )
        ->willReturn(
            $rowObj
        );

        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $mockDb->method(
            'query'
        )
        ->willReturn(
            $mockRes
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $model =
            new $modelCls();

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $model,
                get_class(
                    $model
                )
            );

        $bind();

        $result =
            $model->getLogsStats();

        $expected =
            [
                'total'    => 0,
                'min_date' => null,
                'max_date' => null
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testBuildLogDatesValid()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['checkAllowedFields']
        );

        $mockModel =
            $builder->getMock();

        $postsData =
            [
                'fromDate' => '2026-01-01',
                'toDate'   => '2026-01-31'
            ];

        $mockModel->method(
            'checkAllowedFields'
        )
        ->willReturn(
            $postsData
        );

        $refCls =
            new \ReflectionClass(
                $modelCls
            );

        $method =
            $refCls->getMethod(
                'buildLogDates'
            );

        $method->setAccessible(
            true
        );

        $result =
            $method->invoke(
                $mockModel,
                $postsData
            );

        $expected =
            [
                'from' => '2026-01-01',
                'to'   => '2026-01-31'
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testBuildLogDatesInvalid()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['checkAllowedFields']
        );

        $mockModel =
            $builder->getMock();

        $postsData =
            [
                'fromDate' => '2026-02-01',
                'toDate'   => '2026-01-15'
            ];

        $mockModel->method(
            'checkAllowedFields'
        )
        ->willReturn(
            $postsData
        );

        $refCls =
            new \ReflectionClass(
                $modelCls
            );

        $method =
            $refCls->getMethod(
                'buildLogDates'
            );

        $method->setAccessible(
            true
        );

        $result =
            $method->invoke(
                $mockModel,
                $postsData
            );

        $this->assertFalse(
            $result
        );
    }

    public function testCountLogsToDeleteReturnsFalse()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['buildLogDates']
        );

        $mockModel =
            $builder->getMock();

        $mockModel->method(
            'buildLogDates'
        )
        ->willReturn(
            false
        );

        $postsData =
            [];

        $result =
            $mockModel->countLogsToDelete(
                $postsData
            );

        $this->assertFalse(
            $result
        );
    }

    public function testCountLogsToDeleteSuccess()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['buildLogDates']
        );

        $mockModel =
            $builder->getMock();

        $datesArr =
            [
                'from' => '2026-04-01',
                'to'   => '2026-04-30'
            ];

        $mockModel->method(
            'buildLogDates'
        )
        ->willReturn(
            $datesArr
        );

        $resCls =
            \CodeIgniter\Database\BaseResult::class;

        $resBld =
            $this->getMockBuilder(
                $resCls
            );

        $resBld->disableOriginalConstructor();

        $mockRes =
            $resBld->getMock();

        $rowObj =
            new \stdClass();

        $rowObj->total =
            75;

        $mockRes->method(
            'getRow'
        )
        ->willReturn(
            $rowObj
        );

        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $sqlStr =
            'select count(*) as total from admins_logs where created_at between ? and ?';

        $bindings =
            [
                '2026-04-01',
                '2026-04-30'
            ];

        $mockDb->expects(
            $this->once()
        )
        ->method(
            'query'
        )
        ->with(
            $sqlStr,
            $bindings
        )
        ->willReturn(
            $mockRes
        );

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $mockModel,
                get_class(
                    $mockModel
                )
            );

        $bind();

        $postsData =
            [];

        $result =
            $mockModel->countLogsToDelete(
                $postsData
            );

        $expected =
            [
                'count' => 75,
                'from'  => '2026-04-01',
                'to'    => '2026-04-30'
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testDeleteLogsDatesInvalid()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['buildLogDates']
        );

        $mockModel =
            $builder->getMock();

        $mockModel->method(
            'buildLogDates'
        )
        ->willReturn(
            false
        );

        $postsData =
            [];

        $result =
            $mockModel->deleteLogs(
                $postsData
            );

        $langStr =
            lang('backend/tools.messages.startDateAfterEndDate');

        $expected =
            [
                'result'  => false,
                'message' => $langStr
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testDeleteLogsZeroDeleted()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['buildLogDates']
        );

        $mockModel =
            $builder->getMock();

        $datesArr =
            [
                'from' => '2026-05-01',
                'to'   => '2026-05-31'
            ];

        $mockModel->method(
            'buildLogDates'
        )
        ->willReturn(
            $datesArr
        );

        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $sqlStr =
            'delete from admins_logs where created_at between ? and ?';

        $bindings =
            [
                '2026-05-01',
                '2026-05-31'
            ];

        $mockDb->expects(
            $this->once()
        )
        ->method(
            'query'
        )
        ->with(
            $sqlStr,
            $bindings
        );

        $mockDb->method(
            'affectedRows'
        )
        ->willReturn(
            0
        );

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $mockModel,
                get_class(
                    $mockModel
                )
            );

        $bind();

        $postsData =
            [];

        $result =
            $mockModel->deleteLogs(
                $postsData
            );

        $langStr =
            lang('backend/tools.messages.noLogsDeleted');

        $expected =
            [
                'result'  => false,
                'message' => $langStr
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testDeleteLogsSuccess()
    {
        if ( ! function_exists(__NAMESPACE__ . '\convertDate')):
            function convertDate(
                $a,
                $b
            ) {
                return $a;
            }
        endif;

        if ( ! function_exists(__NAMESPACE__ . '\log_admin_activity')):
            function log_admin_activity(
                $a,
                $b,
                $c,
                $d
            ) {
            }
        endif;

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['buildLogDates']
        );

        $mockModel =
            $builder->getMock();

        $datesArr =
            [
                'from' => '2026-06-01',
                'to'   => '2026-06-30'
            ];

        $mockModel->method(
            'buildLogDates'
        )
        ->willReturn(
            $datesArr
        );

        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $mockDb->method(
            'query'
        )
        ->willReturn(
            true
        );

        $delCount =
            12;

        $mockDb->method(
            'affectedRows'
        )
        ->willReturn(
            $delCount
        );

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $mockModel,
                get_class(
                    $mockModel
                )
            );

        $bind();

        $authCls =
            \stdClass::class;

        $authBld =
            $this->getMockBuilder(
                $authCls
            );

        $authBld->addMethods(
            ['currentAdmin']
        );

        $mockAuth =
            $authBld->getMock();

        $mockAuth->method(
            'currentAdmin'
        )
        ->willReturn(
            1
        );

        \Config\Services::injectMock(
            'authorization',
            $mockAuth
        );

        $postsData =
            [];

        $result =
            $mockModel->deleteLogs(
                $postsData
            );

        $fromLog =
            convertDate(
                '2026-06-01',
                'conversational'
            );

        $toLog =
            convertDate(
                '2026-06-30',
                'conversational'
            );

        $langStr =
            lang('backend/tools.messages.deleteLogsSuccess');

        $expectedMsg =
            sprintf(
                $langStr,
                $delCount,
                $fromLog,
                $toLog
            );

        $expected =
            [
                'result'  => true,
                'message' => $expectedMsg
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testGetTablesStatusAll()
    {
        $resCls =
            \CodeIgniter\Database\BaseResult::class;

        $resBld =
            $this->getMockBuilder(
                $resCls
            );

        $resBld->disableOriginalConstructor();

        $mockRes =
            $resBld->getMock();

        $tableData =
            [
                'Name'         => 'table_one',
                'Rows'         => 100,
                'Data_length'  => 1048576,
                'Index_length' => 1048576,
                'Data_free'    => 524288,
                'Engine'       => 'InnoDB'
            ];

        $tablesArr =
            [$tableData];

        $mockRes->method(
            'getResultArray'
        )
        ->willReturn(
            $tablesArr
        );

        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $sqlStr =
            'show table status';

        $emptyParams =
            [];

        $mockDb->expects(
            $this->once()
        )
        ->method(
            'query'
        )
        ->with(
            $sqlStr,
            $emptyParams
        )
        ->willReturn(
            $mockRes
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $model =
            new $modelCls();

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $model,
                get_class(
                    $model
                )
            );

        $bind();

        $result =
            $model->getTablesStatus();

        $expectedRow =
            [
                'name'     => 'table_one',
                'rows'     => 100,
                'size'     => 2.0,
                'overhead' => 0.5,
                'engine'   => 'InnoDB'
            ];

        $expected =
            [$expectedRow];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testGetTablesStatusSpecific()
    {
        $resCls =
            \CodeIgniter\Database\BaseResult::class;

        $resBld =
            $this->getMockBuilder(
                $resCls
            );

        $resBld->disableOriginalConstructor();

        $mockRes =
            $resBld->getMock();

        $tableData =
            [
                'Name'         => 'users',
                'Rows'         => 500,
                'Data_length'  => 2097152,
                'Index_length' => 0,
                'Data_free'    => 1048576,
                'Engine'       => 'InnoDB'
            ];

        $tablesArr =
            [$tableData];

        $mockRes->method(
            'getResultArray'
        )
        ->willReturn(
            $tablesArr
        );

        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $sqlStr =
            'show table status like ?';

        $params =
            ['users'];

        $mockDb->expects(
            $this->once()
        )
        ->method(
            'query'
        )
        ->with(
            $sqlStr,
            $params
        )
        ->willReturn(
            $mockRes
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $model =
            new $modelCls();

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $model,
                get_class(
                    $model
                )
            );

        $bind();

        $targetTable =
            'users';

        $result =
            $model->getTablesStatus(
                $targetTable
            );

        $expectedRow =
            [
                'name'     => 'users',
                'rows'     => 500,
                'size'     => 2.0,
                'overhead' => 1.0,
                'engine'   => 'InnoDB'
            ];

        $expected =
            [$expectedRow];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testRunOptimizationSingleTable()
    {
        if ( ! function_exists(__NAMESPACE__ . '\log_admin_activity')):
            function log_admin_activity(
                $a,
                $b,
                $c,
                $d
            ) {
            }
        endif;

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['getTablesStatus']
        );

        $mockModel =
            $builder->getMock();

        $statusArr =
            ['status' => 'single_ok'];

        $targetTable =
            'users';

        $mockModel->expects(
            $this->once()
        )
        ->method(
            'getTablesStatus'
        )
        ->with(
            $targetTable
        )
        ->willReturn(
            $statusArr
        );

        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $mockDb->method(
            'escapeIdentifiers'
        )
        ->willReturn(
            $targetTable
        );

        $expectedQueries =
            3;

        $mockDb->expects(
            $this->exactly(
                $expectedQueries
            )
        )
        ->method(
            'query'
        );

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $mockModel,
                get_class(
                    $mockModel
                )
            );

        $bind();

        $authCls =
            \stdClass::class;

        $authBld =
            $this->getMockBuilder(
                $authCls
            );

        $authBld->addMethods(
            ['currentAdmin']
        );

        $mockAuth =
            $authBld->getMock();

        $mockAuth->method(
            'currentAdmin'
        )
        ->willReturn(
            1
        );

        \Config\Services::injectMock(
            'authorization',
            $mockAuth
        );

        $result =
            $mockModel->runOptimization(
                $targetTable
            );

        $this->assertSame(
            $statusArr,
            $result
        );
    }

    public function testRunOptimizationArrayMultipleTables()
    {
        if ( ! function_exists(__NAMESPACE__ . '\log_admin_activity')):
            function log_admin_activity(
                $a,
                $b,
                $c,
                $d
            ) {
            }
        endif;

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $builder =
            $this->getMockBuilder(
                $modelCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['getTablesStatus']
        );

        $mockModel =
            $builder->getMock();

        $statusArr =
            ['status' => 'all_ok'];

        $mockModel->expects(
            $this->once()
        )
        ->method(
            'getTablesStatus'
        )
        ->willReturn(
            $statusArr
        );

        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $callback =
            function (
                $val
            ) {
                return $val;
            };

        $mockDb->method(
            'escapeIdentifiers'
        )
        ->willReturnCallback(
            $callback
        );

        $expectedQueries =
            6;

        $mockDb->expects(
            $this->exactly(
                $expectedQueries
            )
        )
        ->method(
            'query'
        );

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $mockModel,
                get_class(
                    $mockModel
                )
            );

        $bind();

        $authCls =
            \stdClass::class;

        $authBld =
            $this->getMockBuilder(
                $authCls
            );

        $authBld->addMethods(
            ['currentAdmin']
        );

        $mockAuth =
            $authBld->getMock();

        $mockAuth->method(
            'currentAdmin'
        )
        ->willReturn(
            1
        );

        \Config\Services::injectMock(
            'authorization',
            $mockAuth
        );

        $targetArr =
            [
                'users',
                'logs'
            ];

        $result =
            $mockModel->runOptimization(
                $targetArr
            );

        $this->assertSame(
            $statusArr,
            $result
        );
    }

    public function testGetDatabase()
    {
        $dbCls =
            \CodeIgniter\Database\BaseConnection::class;

        $dbBld =
            $this->getMockBuilder(
                $dbCls
            );

        $dbBld->disableOriginalConstructor();

        $mockDb =
            $dbBld->getMock();

        $mockDb->method(
            'getDatabase'
        )
        ->willReturn(
            'essential2'
        );

        $mockDb->method(
            'getVersion'
        )
        ->willReturn(
            '10.4.24-MariaDB'
        );

        $callback =
            function (
                $prop
            ) {
                if (
                    $prop === 'DBDriver'
                ) {
                    return 'MySQLi';
                }
                
                return null;
            };

        $mockDb->method(
            '__get'
        )
        ->willReturnCallback(
            $callback
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $model =
            new $modelCls();

        $closure =
            function () use (
                $mockDb
            ) {
                $this->db =
                    $mockDb;
            };

        $bind =
            \Closure::bind(
                $closure,
                $model,
                get_class(
                    $model
                )
            );

        $bind();

        $result =
            $model->getDatabase();

        $expected =
            [
                'dbName'    => 'essential2',
                'dbDriver'  => 'MySQLi',
                'dbVersion' => '10.4.24-MariaDB'
            ];

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testGetBackupsReturnsArray()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $model =
            new $modelCls();

        $result =
            $model->getBackups();

        $isArr =
            is_array(
                $result
            );

        $this->assertTrue(
            $isArr
        );
    }

    public function testGetBackupsRetrievesAndSortsFiles()
    {
        if ( ! function_exists(__NAMESPACE__ . '\convertDate')):
            function convertDate(
                $dt,
                $fmt
            ) {
                return $dt;
            }
        endif;

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $model =
            new $modelCls();

        $basePath =
            WRITEPATH;

        $bkpDir =
            $basePath . 'backups/database/';

        if ( ! is_dir($bkpDir)):
            mkdir(
                $bkpDir,
                0777,
                true
            );
        endif;

        $fileOld =
            $bkpDir . 'test_old.zip';

        $fileNew =
            $bkpDir . 'test_new.zip';

        file_put_contents(
            $fileOld,
            'old data'
        );

        file_put_contents(
            $fileNew,
            'new data'
        );

        $timeRef =
            time();

        $timePast =
            $timeRef - 3600;

        touch(
            $fileOld,
            $timePast
        );

        touch(
            $fileNew,
            $timeRef
        );

        $result =
            $model->getBackups();

        $keys =
            array_column(
                $result,
                'filename'
            );

        $idxOld =
            array_search(
                'test_old.zip',
                $keys
            );

        $idxNew =
            array_search(
                'test_new.zip',
                $keys
            );

        $oldFound =
            $idxOld !== false;

        $this->assertTrue(
            $oldFound
        );

        $newFound =
            $idxNew !== false;

        $this->assertTrue(
            $newFound
        );

        /* assertLessThan verifica che il primo parametro sia MAGGIORE del secondo.
           Quindi \(idxNew deve essere MINORE di\)idxOld (ovvero fileNew appare PRIMA nell'array) */
        $this->assertLessThan(
            $idxOld,
            $idxNew
        );

        @unlink(
            $fileOld
        );

        @unlink(
            $fileNew
        );
    }

    public function testExtractDateFromFilenameSuccess()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $model =
            new $modelCls();

        $refCls =
            new \ReflectionClass(
                $modelCls
            );

        $method =
            $refCls->getMethod(
                'extractDateFromFilename'
            );

        $method->setAccessible(
            true
        );

        $fileNameStr =
            'backup_2026-12-31_23-59-59.zip';

        $result =
            $method->invoke(
                $model,
                $fileNameStr
            );

        $expected =
            '2026-12-31 23:59:59';

        $this->assertSame(
            $expected,
            $result
        );
    }

    public function testExtractDateFromFilenameFails()
    {
        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $model =
            new $modelCls();

        $refCls =
            new \ReflectionClass(
                $modelCls
            );

        $method =
            $refCls->getMethod(
                'extractDateFromFilename'
            );

        $method->setAccessible(
            true
        );

        $fileNameStr =
            'invalid_name.zip';

        $result =
            $method->invoke(
                $model,
                $fileNameStr
            );

        $this->assertFalse(
            $result
        );
    }

    public function testValidateManageAuditsRulesReturnsCorrectArray(): void
    {
        /* Inizializzazione del modello */
        $model = 
        new \App\Models\Backend\ToolsModel();

        /* Esecuzione del metodo per ottenere l array delle regole */
        $rules = 
        $model
        ->validateManageAuditsRules();

        /* Verifica tipo di ritorno */
        $this
        ->assertIsArray(
            $rules
        );

        /* Verifica presenza chiavi */
        $this
        ->assertArrayHasKey(
            'fromDate', 
            $rules
        );

        $this
        ->assertArrayHasKey(
            'toDate', 
            $rules
        );

        /* Verifica configurazione fromDate */
        $this
        ->assertEquals(
            lang('backend/tools.labels.dateFrom'), 
            $rules
            ['fromDate']['label']
        );

        $this
        ->assertEquals(
            ['required', 'valid_date[Y-m-d H:i:s]'], 
            $rules
            ['fromDate']['rules']
        );

        /* Verifica configurazione toDate */
        $this
        ->assertEquals(
            lang('backend/tools.labels.dateTo'), 
            $rules
            ['toDate']['label']
        );

        $this
        ->assertEquals(
            ['required', 'valid_date[Y-m-d H:i:s]'], 
            $rules
            ['toDate']['rules']
        );
    }

    public function testValidateManageLogsRulesReturnsCorrectArray(): void
    {
        /* Inizializzazione del modello */
        $model = 
        new \App\Models\Backend\ToolsModel();

        /* Esecuzione del metodo per ottenere l array delle regole */
        $rules = 
        $model
        ->validateManageLogsRules();

        /* Verifica tipo di ritorno */
        $this
        ->assertIsArray(
            $rules
        );

        /* Verifica presenza chiavi */
        $this
        ->assertArrayHasKey(
            'fromDate', 
            $rules
        );

        $this
        ->assertArrayHasKey(
            'toDate', 
            $rules
        );

        /* Verifica configurazione fromDate */
        $this
        ->assertEquals(
            lang('backend/tools.labels.dateFrom'), 
            $rules
            ['fromDate']['label']
        );

        $this
        ->assertEquals(
            ['required', 'valid_date[Y-m-d H:i:s]'], 
            $rules
            ['fromDate']['rules']
        );

        /* Verifica configurazione toDate */
        $this
        ->assertEquals(
            lang('backend/tools.labels.dateTo'), 
            $rules
            ['toDate']['label']
        );

        $this
        ->assertEquals(
            ['required', 'valid_date[Y-m-d H:i:s]'], 
            $rules
            ['toDate']['rules']
        );
    }

    /* All'interno della classe del test */
    public function testGenerateDatabaseBackupsReturnsTrueOnSuccess(): void
    {
        /* Inizializzazione DB Mock */
        $mockDb = 
        $this
        ->createMock(
            \CodeIgniter\Database\BaseConnection::class
        );

        /* Mock listTables */
        $mockDb
        ->method(
            'listTables'
        )
        ->willReturn(
            ['test_table']
        );

        /* Mock Query Schema */
        $mockQuerySchema = 
        $this
        ->createMock(
            \CodeIgniter\Database\BaseResult::class
        );

        $mockQuerySchema
        ->method(
            'getRowArray'
        )
        ->willReturn(
            ['Create Table' => 'CREATE TABLE test_table (id INT)']
        );

        /* Mock Query Data */
        $mockQueryData = 
        $this
        ->createMock(
            \CodeIgniter\Database\BaseResult::class
        );

        $mockQueryData
        ->method(
            'getResultArray'
        )
        ->willReturn(
            [
                ['id' => '1', 'name' => 'test'],
                ['id' => '2', 'name' => null]
            ]
        );

        /* Configurazione callback per query diverse */
        $mockDb
        ->method(
            'query'
        )
        ->willReturnCallback(
            function(
                $sql
            ) use (
                $mockQuerySchema,
                $mockQueryData
            ) {
                $isSchema = 
                strpos(
                    $sql, 
                    'SHOW CREATE TABLE'
                );
                
                if (
                    $isSchema !== 
                    false
                ):
                    return 
                    $mockQuerySchema;
                endif;
                
                return 
                $mockQueryData;
            }
        );

        /* Mock escape */
        $mockDb
        ->method(
            'escape'
        )
        ->willReturnCallback(
            function(
                $val
            ) {
                return 
                "'" . 
                $val . 
                "'";
            }
        );

        /* Inizializzazione modello e iniezione */
        $model = 
        new \App\Models\Backend\ToolsModel();

        $closure = 
        function() use (
            $mockDb
        ) {
            $this
            ->db = 
            $mockDb;
        };

        \Closure::bind(
            $closure, 
            $model, 
            $model
        )();

        /* Esecuzione */
        $result = 
        $model
        ->generateDatabaseBackups();

        /* Asserzione principale */
        $this
        ->assertTrue(
            $result
        );

        /* Pulizia dei file generati per evitare side-effect */
        $path = 
        WRITEPATH . 
        'backups/database/';

        $files = 
        glob(
            $path . 
            '*.zip'
        );

        if (
            $files !== 
            false
        ):
            foreach (
                $files 
                as 
                $file
            ):
                if (file_exists(
                    $file
                )):
                    unlink(
                        $file
                    );
                endif;
            endforeach;
        endif;
    }

    public function testDeleteBackupsReturnsTrueOnSuccess(): void
    {
        /* Preparazione file fittizio per il test */
        $path = 
        WRITEPATH . 
        'backups/database/';
        
        if (! is_dir(
            $path
        )):
            mkdir(
                $path, 
                0775, 
                true
            );
        endif;
        
        $filename = 
        'test_delete.zip';
        
        $fullPath = 
        $path . 
        $filename;
        
        file_put_contents(
            $fullPath, 
            'dummy content'
        );
        
        /* Inizializzazione modello */
        $model = 
        new \App\Models\Backend\ToolsModel();
        
        /* Esecuzione */
        $result = 
        $model
        ->deleteBackups(
            $filename
        );
        
        /* Asserzioni */
        $this
        ->assertTrue(
            $result
        );
        
        $this
        ->assertFileDoesNotExist(
            $fullPath
        );
    }

    public function testDeleteBackupsReturnsFalseIfFileDoesNotExist(): void
    {
        /* Inizializzazione modello */
        $model = 
        new \App\Models\Backend\ToolsModel();
        
        $filename = 
        'not_exist.zip';

        /* Esecuzione */
        $result = 
        $model
        ->deleteBackups(
            $filename
        );
        
        /* Asserzione */
        $this
        ->assertFalse(
            $result
        );
    }

    public function testGetWritableFoldersStatusReturnsCorrectArray(): void
    {
        /* Inizializzazione modello */
        $model = 
        new \App\Models\Backend\ToolsModel();
        
        $testFolder = 
        'test_folder';
        
        /* Iniezione cartella custom */
        $closure = 
        function() use (
            $testFolder
        ) {
            $this
            ->cleanableFolders = [
                $testFolder
            ];
        };
        
        \Closure::bind(
            $closure, 
            $model, 
            $model
        )();
        
        /* Preparazione directory e file fittizi */
        $path = 
        WRITEPATH . 
        $testFolder;
        
        if (! is_dir(
            $path
        )):
            mkdir(
                $path, 
                0775, 
                true
            );
        endif;
        
        $dummyFile = 
        $path . 
        '/dummy.txt';

        $indexFile = 
        $path . 
        '/index.html';

        file_put_contents(
            $dummyFile, 
            'dummy'
        );
        
        file_put_contents(
            $indexFile, 
            'dummy'
        );
        
        /* Esecuzione */
        $result = 
        $model
        ->getWritableFoldersStatus();
        
        /* Asserzioni */
        $this
        ->assertIsArray(
            $result
        );
        
        $this
        ->assertEquals(
            $testFolder, 
            $result
            [0]['name']
        );
        
        /* Si aspetta 1 perché index.html viene ignorato */
        $this
        ->assertEquals(
            1, 
            $result
            [0]['count']
        );
        
        /* Pulizia */
        unlink(
            $dummyFile
        );

        unlink(
            $indexFile
        );

        rmdir(
            $path
        );
    }

    public function testCleanWritableFolderReturnsFalseOnInvalidFolder(): void
    {
        /* Inizializzazione modello */
        $model = 
        new \App\Models\Backend\ToolsModel();
        
        $invalidFolder = 
        'invalid_folder';

        /* Esecuzione */
        $result = 
        $model
        ->cleanWritableFolder(
            $invalidFolder
        );
        
        /* Asserzione */
        $this
        ->assertFalse(
            $result
            ['result']
        );
    }

    public function testCleanWritableFolderReturnsTrueOnSuccess(): void
    {
        /* Inizializzazione modello */
        $model = 
        new \App\Models\Backend\ToolsModel();
        
        $testFolder = 
        'test_folder_clean';
        
        /* Iniezione cartella custom */
        $closure = 
        function() use (
            $testFolder
        ) {
            $this
            ->cleanableFolders = [
                $testFolder
            ];
        };
        
        \Closure::bind(
            $closure, 
            $model, 
            $model
        )();
        
        /* Preparazione directory e file fittizi */
        $path = 
        WRITEPATH . 
        $testFolder;
        
        if (! is_dir(
            $path
        )):
            mkdir(
                $path, 
                0775, 
                true
            );
        endif;
        
        $dummyFile = 
        $path . 
        '/dummy.txt';
        
        file_put_contents(
            $dummyFile, 
            'dummy'
        );
        
        /* Esecuzione */
        $result = 
        $model
        ->cleanWritableFolder(
            $testFolder
        );
        
        /* Asserzioni */
        $this
        ->assertTrue(
            $result
            ['result']
        );
        
        $this
        ->assertFileDoesNotExist(
            $dummyFile
        );
        
        /* Pulizia */
        rmdir(
            $path
        );
    }

    public function testGetSystemInfoReturnsCorrectArray(): void
    {
        /* Imposta la variabile server per il contesto CLI */
        $_SERVER
        ['SERVER_SOFTWARE'] = 
        'PHPUnit Test Server';

        /* Inizializzazione modello */
        $model = 
        new \App\Models\Backend\ToolsModel();

        /* Esecuzione */
        $info = 
        $model
        ->getSystemInfo();

        /* Asserzioni strutturali e chiavi primarie */
        $this
        ->assertIsArray(
            $info
        );

        $this
        ->assertArrayHasKey(
            'framework', 
            $info
        );

        $this
        ->assertArrayHasKey(
            'local', 
            $info
        );

        $this
        ->assertArrayHasKey(
            'server', 
            $info
        );

        $this
        ->assertArrayHasKey(
            'php', 
            $info
        );

        $this
        ->assertArrayHasKey(
            'extensions', 
            $info
        );

        /* Asserzioni valori specifici di framework e server */
        $this
        ->assertEquals(
            \CodeIgniter\CodeIgniter::CI_VERSION, 
            $info
            ['framework']['ci_version']
        );

        $this
        ->assertEquals(
            'PHPUnit Test Server', 
            $info
            ['server']['software']
        );

        /* Asserzioni valori dipendenti da costanti e funzioni native */
        $this
        ->assertEquals(
            PHP_VERSION, 
            $info
            ['php']['version']
        );

        $this
        ->assertIsBool(
            $info
            ['extensions']['curl']
        );
    }

    public function testGenerateDatabaseBackupsCreatesDirectoryAndHandlesFopenFailure(): void
    {
        $path = 
        WRITEPATH . 
        'backups/database';

        /* Rimuove la cartella se esiste per poi sostituirla con un file */
        if (is_dir(
            $path
        )):
            $files = 
            glob(
                $path . 
                '/*'
            );

            foreach (
                $files 
                as 
                $file
            ):
                if (is_file(
                    $file
                )):
                    unlink(
                        $file
                    );
                endif;
            endforeach;

            rmdir(
                $path
            );
        endif;

        /* Creare un file con lo stesso nome della cartella farà fallire mkdir() e di conseguenza fopen() */
        file_put_contents(
            $path, 
            'dummy_file'
        );

        $model = 
        new \App\Models\Backend\ToolsModel();

        /* Esecuzione con @ per sopprimere i warning PHP generati da mkdir e fopen */
        $result = 
        @$model
        ->generateDatabaseBackups();

        /* Asserzione fallimento a causa di fopen */
        $this
        ->assertFalse(
            $result
        );

        /* Pulizia */
        unlink(
            $path
        );
    }

    public function testGenerateDatabaseBackupsFailsOnZipErrorAndCleansUp(): void
    {
        $path = 
        WRITEPATH . 
        'backups/database/';

        if (! is_dir(
            $path
        )):
            mkdir(
                $path, 
                0775, 
                true
            );
        endif;

        /* Per far fallire ZipArchive, creiamo 60 directory (una per ogni possibile secondo del minuto) */
        $baseDate = 
        date('Y-m-d_H-i-');
        
        $createdDirs = 
        [];

        for (
            $i = 0; 
            $i < 60; 
            $i++
        ):
            $second = 
            str_pad(
                (string) $i, 
                2, 
                '0', 
                STR_PAD_LEFT
            );
            
            $zipName = 
            $path . 
            'backup_' . 
            $baseDate . 
            $second . 
            '.zip';
            
            if (! is_dir(
                $zipName
            )):
                mkdir(
                    $zipName
                );
                
                $createdDirs
                [] = 
                $zipName;
            endif;
        endfor;

        /* Inizializzazione DB Mock (senza tabelle per velocizzare e arrivare allo Zip) */
        $mockDb = 
        $this
        ->createMock(
            \CodeIgniter\Database\BaseConnection::class
        );

        $mockDb
        ->method(
            'listTables'
        )
        ->willReturn(
            []
        );

        $model = 
        new \App\Models\Backend\ToolsModel();

        $closure = 
        function() use (
            $mockDb
        ) {
            $this
            ->db = 
            $mockDb;
        };

        \Closure::bind(
            $closure, 
            $model, 
            $model
        )();

        /* L'apertura ZIP fallirà perché esiste già una cartella con il nome del file da creare */
        $result = 
        @$model
        ->generateDatabaseBackups();

        /* Asserzione fallimento ZIP e clean up del file SQL */
        $this
        ->assertFalse(
            $result
        );

        /* Pulizia directory fittizie */
        foreach (
            $createdDirs 
            as 
            $dir
        ):
            rmdir(
                $dir
            );
        endforeach;
    }

    public function testGenerateDatabaseBackupsRotatesOldFiles(): void
    {
        $path = 
        WRITEPATH . 
        'backups/database/';

        if (! is_dir(
            $path
        )):
            mkdir(
                $path, 
                0775, 
                true
            );
        endif;

        /* Creiamo 12 file zip vecchi per superare il limite di 10 e attivare la rotazione */
        for (
            $i = 0; 
            $i < 12; 
            $i++
        ):
            $dummyZip = 
            $path . 
            'backup_dummy_' . 
            $i . 
            '.zip';

            file_put_contents(
                $dummyZip, 
                'dummy'
            );

            /* Scaliamo i timestamp nel passato */
            touch(
                $dummyZip, 
                time() - (1000 * 
                $i)
            );
        endfor;

        $mockDb = 
        $this
        ->createMock(
            \CodeIgniter\Database\BaseConnection::class
        );

        $mockDb
        ->method(
            'listTables'
        )
        ->willReturn(
            []
        );

        $model = 
        new \App\Models\Backend\ToolsModel();

        $closure = 
        function() use (
            $mockDb
        ) {
            $this
            ->db = 
            $mockDb;
        };

        \Closure::bind(
            $closure, 
            $model, 
            $model
        )();

        $result = 
        $model
        ->generateDatabaseBackups();

        $this
        ->assertTrue(
            $result
        );

        $files = 
        glob(
            $path . 
            '*.zip'
        );

        /* Si aspetta un totale di 10 file ZIP (rotazione avvenuta eliminando i 3 più vecchi: i 2 fittizi + quello appena generato che porta a 13 il conto prima della cancellazione) */
        $this
        ->assertCount(
            10, 
            $files
        );

        /* Pulizia */
        foreach (
            $files 
            as 
            $f
        ):
            unlink(
                $f
            );
        endforeach;
    }
}
