<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Config\Services;
use CodeIgniter\Email\Email;
use CodeIgniter\Test\CIUnitTestCase;

class EmailServiceTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Services::reset();

        parent::tearDown();
    }

    public function testSendActivationEmailConfiguresAndSendsEscapedMessage(): void
    {
        $row = (object) [
            'firstname' => '<Mario>',
            'lastname' => 'Rossi & Figli',
            'email' => 'admin@example.com'
        ];

        $email = $this->createMock(Email::class);
        $email->expects($this->once())->method('setTo')->with('admin@example.com');
        $email->expects($this->once())->method('setSubject')->with($this->isType('string'));
        $email->expects($this->once())->method('setMessage')->with(
            $this->callback(static function(string $message): bool {
                return str_contains($message, '&lt;Mario&gt;')
                    && str_contains($message, 'Rossi &amp; Figli')
                    && str_contains($message, 'raw-token');
            })
        );
        $email->expects($this->once())->method('send')->willReturn(true);

        Services::injectMock('email', $email);

        $result = (new EmailService())->sendActivationEmail(
            $row,
            'raw-token',
            'admins',
            'emailCreateAdminPartial',
            'backend/email.admins.add.subjectCreateAdminEmail'
        );

        $this->assertTrue($result);
    }

    public function testSendActivationEmailReturnsFalseAndReadsDebuggerWhenSendFails(): void
    {
        $row = (object) [
            'firstname' => 'Mario',
            'lastname' => 'Rossi',
            'email' => 'admin@example.com'
        ];

        $email = $this->createMock(Email::class);
        $email->expects($this->once())->method('send')->willReturn(false);
        $email->expects($this->once())->method('printDebugger')->with(['headers'])->willReturn('SMTP failure');

        Services::injectMock('email', $email);

        $result = (new EmailService())->sendActivationEmail(
            $row,
            'raw-token',
            'admins',
            'emailCreateAdminPartial',
            'backend/email.admins.add.subjectCreateAdminEmail'
        );

        $this->assertFalse($result);
    }
}
