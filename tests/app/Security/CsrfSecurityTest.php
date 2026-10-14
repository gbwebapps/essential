<?php

namespace Tests\App\Security;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Security\Exceptions\SecurityException;

class CsrfSecurityTest extends CIUnitTestCase
{
    public function testPostWithoutCsrfTokenIsRejected(): void
    {
        $this->assertSame('testing', ENVIRONMENT);
        $this->assertFalse(config('Security')->redirect);
        $this->assertSame('session', config('Security')->csrfProtection);

        $request = service('request');
        $request->setMethod('POST');
        $request->setGlobal('post', []);
        $request->removeHeader('X-CSRF-TOKEN');

        $filter = new CSRF();

        $this->expectException(SecurityException::class);

        $filter->before($request);
    }

    public function testPostWithInvalidCsrfTokenIsRejected(): void
    {
        $this->assertSame('testing', ENVIRONMENT);

        $request = service('request');
        $request->setMethod('POST');
        $request->setGlobal('post', [
            config('Security')->tokenName => 'invalid_csrf_token',
        ]);
        $request->removeHeader('X-CSRF-TOKEN');

        $filter = new CSRF();

        $this->expectException(SecurityException::class);

        $filter->before($request);
    }


    public function testPostWithValidCsrfTokenIsAccepted(): void
    {
        $this->assertSame('testing', ENVIRONMENT);

        $security = service('security');
        $tokenName = config('Security')->tokenName;

        $request = service('request');
        $request->setMethod('POST');
        $request->setGlobal('post', [
            $tokenName => $security->getHash(),
        ]);
        $request->removeHeader('X-CSRF-TOKEN');

        $filter = new CSRF();

        $this->assertNull($filter->before($request));
    }

    public function testValidCsrfTokenRemainsAcceptedForTheWholeSession(): void
    {
        $security = service('security');
        $tokenName = config('Security')->tokenName;
        $token = $security->getHash();
        $request = service('request');
        $request->setMethod('POST');
        $request->removeHeader('X-CSRF-TOKEN');

        $request->setGlobal('post', [$tokenName => $token]);
        $this->assertNull((new CSRF())->before($request));

        $request->setGlobal('post', [$tokenName => $token]);
        $this->assertNull((new CSRF())->before($request));
    }

}
