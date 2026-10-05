<?php declare(strict_types = 1);

namespace App\Libraries;

use CodeIgniter\Test\CIUnitTestCase;

class TokenTest extends CIUnitTestCase
{
    public function testGeneratedTokenHasExpectedCryptographicFormat(): void
    {
        $token = new Token();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token->getValue());
    }

    public function testGeneratedTokensAreDifferent(): void
    {
        $this->assertNotSame((new Token())->getValue(), (new Token())->getValue());
    }

    public function testProvidedTokenIsPreserved(): void
    {
        $this->assertSame('plain-token', (new Token('plain-token'))->getValue());
    }

    public function testHashUsesHmacSha256WithProvidedKey(): void
    {
        $token = new Token('plain-token');

        $this->assertSame(
            hash_hmac('sha256', 'plain-token', 'secret-key'),
            $token->getHash('secret-key')
        );
    }

    public function testDifferentKeysProduceDifferentHashes(): void
    {
        $token = new Token('plain-token');

        $this->assertNotSame($token->getHash('first-key'), $token->getHash('second-key'));
    }
}
