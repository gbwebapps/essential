<?php declare(strict_types = 1);

namespace App\Models\Backend;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseResult;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

if ( ! function_exists(__NAMESPACE__ . '\\log_admin_activity')):
    function log_admin_activity(): bool
    {
        return true;
    }
endif;

class TokensModelTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Services::resetSingle('authorization');
        parent::tearDown();
    }

    public function testShowAllValidationRulesReturnsExpectedRules(): void
    {
        $this->assertSame([
            'column' => ['rules' => ['required', 'alpha_dash']],
            'order' => ['rules' => ['required', 'in_list[asc,desc]']],
            'page' => ['rules' => ['required', 'is_natural_no_zero']],
            'rows' => ['rules' => ['required', 'is_natural_no_zero']]
        ], (new TokensModel())->showAllValidationRules());
    }

    public function testShowAllSearchValidationRulesSupportsPartialEmailSearch(): void
    {
        $rules = (new TokensModel())->showAllSearchValidationRules();

        $this->assertSame(
            ['permit_empty', 'trim', 'max_length[100]', 'regex_match[/^[a-zA-Z0-9@._+\-]+$/]'],
            $rules['searchFields.email']['rules']
        );
        $this->assertSame(
            ['permit_empty', 'in_list[activation,session,cookie]'],
            $rules['searchFields.token_type']['rules']
        );
    }

    public function testDelValidationRulesRequiresUuidAndTokenId(): void
    {
        $rules = (new TokensModel())->delValidationRules();

        $this->assertArrayHasKey('uuid', $rules);
        $this->assertArrayHasKey('id', $rules);
        $this->assertContains('is_natural_no_zero', $rules['id']['rules']);
    }

    public function testBuildFiltersUsesJoinedAdminEmailColumn(): void
    {
        $model = new TokensModel();
        $params = [];
        $invoke = function (array $fields, array &$values): string {
            return $this->buildFilters($fields, $values);
        };
        $buildFilters = \Closure::bind($invoke, $model, TokensModel::class);

        $sql = $buildFilters(['email' => 'admin1@', 'token_type' => 'session'], $params);

        $this->assertSame(' and admins.email like ? and admins_tokens.token_type like ?', $sql);
        $this->assertSame(['%admin1@%', '%session%'], $params);
    }

    public function testHardDeleteRejectsTokenOwnedByAnotherAdmin(): void
    {
        $database = $this->createMock(BaseConnection::class);
        $result = $this->createMock(BaseResult::class);
        $result->method('getRow')->willReturn(null);

        $database->expects($this->once())->method('transBegin')->willReturn(true);
        $database->expects($this->once())
            ->method('query')
            ->with($this->stringContains('where at.id = ? and at.admin_uuid = ?'), [7, 'owner-uuid'])
            ->willReturn($result);
        $database->expects($this->once())->method('transRollback')->willReturn(true);

        $response = $this->createModel($database)->hardDelete(['id' => 7, 'uuid' => 'owner-uuid']);

        $this->assertFalse($response['result']);
        $this->assertSame(lang('backend/tokens.messages.deleteTokenError'), $response['message']);
    }

    public function testHardDeleteRejectsDeletedAdmin(): void
    {
        $database = $this->createMock(BaseConnection::class);
        $result = $this->createMock(BaseResult::class);
        $result->method('getRow')->willReturn($this->tokenRow(['deleted_at' => '2026-01-01 00:00:00']));

        $database->method('transBegin')->willReturn(true);
        $database->method('query')->willReturn($result);
        $database->expects($this->once())->method('transRollback')->willReturn(true);

        $response = $this->createModel($database)->hardDelete($this->posts());

        $this->assertFalse($response['result']);
        $this->assertSame(lang('backend/tokens.messages.cannotModifyDeleted'), $response['message']);
    }

    public function testHardDeleteRejectsSuperadmin(): void
    {
        $database = $this->createMock(BaseConnection::class);
        $result = $this->createMock(BaseResult::class);
        $result->method('getRow')->willReturn($this->tokenRow(['superadmin' => 1]));

        $database->method('transBegin')->willReturn(true);
        $database->method('query')->willReturn($result);
        $database->expects($this->once())->method('transRollback')->willReturn(true);

        $response = $this->createModel($database)->hardDelete($this->posts());

        $this->assertFalse($response['result']);
        $this->assertSame(lang('backend/tokens.messages.protectedAdmin'), $response['message']);
    }

    public function testHardDeleteRollsBackWhenDeleteDoesNotAffectOneToken(): void
    {
        $database = $this->createMock(BaseConnection::class);
        $result = $this->createMock(BaseResult::class);
        $result->method('getRow')->willReturn($this->tokenRow());

        $database->method('transBegin')->willReturn(true);
        $database->method('query')->willReturn($result);
        $database->method('affectedRows')->willReturn(0);
        $database->method('transStatus')->willReturn(true);
        $database->expects($this->once())->method('transRollback')->willReturn(true);

        $response = $this->createModel($database)->hardDelete($this->posts());

        $this->assertFalse($response['result']);
        $this->assertSame(lang('backend/tokens.messages.deleteTokenError'), $response['message']);
    }

    public function testHardDeleteLocksTokenClosesOpenLogAndCommits(): void
    {
        $queries = [];
        $result = $this->createMock(BaseResult::class);
        $result->method('getRow')->willReturn($this->tokenRow());
        $database = $this->createMock(BaseConnection::class);

        $database->expects($this->once())->method('transBegin')->willReturn(true);
        $database->method('query')->willReturnCallback(
            function (string $sql, array $params = []) use (&$queries, $result) {
                $queries[] = [$sql, $params];
                return str_contains($sql, 'select at.id') ? $result : true;
            }
        );
        $database->method('affectedRows')->willReturn(1);
        $database->method('transStatus')->willReturn(true);
        $database->expects($this->once())->method('transCommit')->willReturn(true);
        $database->expects($this->never())->method('transRollback');

        $authorization = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['currentAdmin'])
            ->getMock();
        $authorization->method('currentAdmin')->willReturn((object) ['uuid' => 'current-admin']);
        Services::injectMock('authorization', $authorization);

        $response = $this->createModel($database)->hardDelete($this->posts());

        $this->assertTrue($response['result'], json_encode($response));
        $this->assertTrue($this->queriesContain($queries, 'for update'));
        $this->assertTrue($this->queriesContain($queries, "logout_reason = 'banned' where token_id = ? and logout is null"));
        $this->assertTrue($this->queriesContain($queries, 'delete from admins_tokens where admin_uuid = ? and id = ?'));
    }

    public function testHardDeleteRollsBackOnException(): void
    {
        $database = $this->createMock(BaseConnection::class);
        $database->method('transBegin')->willReturn(true);
        $database->method('query')->willThrowException(new \RuntimeException('Database error'));
        $database->expects($this->once())->method('transRollback')->willReturn(true);

        $response = $this->createModel($database)->hardDelete($this->posts());

        $this->assertFalse($response['result']);
    }

    /**
     * Crea il model iniettando una connessione database controllata dal test.
     *
     * @param BaseConnection $database Connessione database simulata
     * @return TokensModel Model configurato per il test
     */
    private function createModel(BaseConnection $database): TokensModel
    {
        $model = new TokensModel();
        $inject = function (BaseConnection $connection): void {
            $this->db = $connection;
        };
        \Closure::bind($inject, $model, TokensModel::class)($database);

        return $model;
    }

    /**
     * Restituisce un token valido sovrascrivibile per i singoli scenari.
     *
     * @param array $overrides Proprietà da sovrascrivere
     * @return object Riga token simulata
     */
    private function tokenRow(array $overrides = []): object
    {
        return (object) array_merge([
            'id' => 7,
            'admin_uuid' => 'owner-uuid',
            'last_activity' => '2026-10-13 10:11:12',
            'token_type' => 'session',
            'uuid' => 'owner-uuid',
            'firstname' => 'Mario',
            'lastname' => 'Rossi',
            'deleted_at' => null,
            'superadmin' => 0
        ], $overrides);
    }

    /**
     * Restituisce il payload valido per la cancellazione del token.
     *
     * @return array Payload del test
     */
    private function posts(): array
    {
        return ['id' => 7, 'uuid' => 'owner-uuid'];
    }

    /**
     * Verifica la presenza di un frammento SQL nelle query intercettate.
     *
     * @param array $queries Query e parametri registrati
     * @param string $fragment Frammento SQL cercato
     * @return bool Esito della ricerca
     */
    private function queriesContain(array $queries, string $fragment): bool
    {
        foreach ($queries as [$sql]):
            if (str_contains($sql, $fragment)):
                return true;
            endif;
        endforeach;

        return false;
    }
}
