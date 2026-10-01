<?php

namespace LegacyOpenCart\Tests;

if (!defined('DIR_SYSTEM')) {
    define('DIR_SYSTEM', __DIR__ . '/../src/system/');
}

if (!defined('VERSION')) {
    define('VERSION', 'test');
}

if (!defined('HTTP_SERVER')) {
    define('HTTP_SERVER', 'https://example.test');
}

use PHPUnit\Framework\TestCase;

final class ScocEncoderSecurityTest extends TestCase
{
    public function testResolveEncryptionKeyPrefersConfiguredSecret(): void
    {
        $registry = new class {
            public function get($name)
            {
                if ($name === 'config') {
                    return new class {
                        public function get($key)
                        {
                            if ($key === 'scoc_secret_key') {
                                return 'configured-secret';
                            }

                            return '';
                        }
                    };
                }

                return null;
            }
        };

        $encoder = new \scoc_encoder($registry);
        $method = new \ReflectionMethod(\scoc_encoder::class, 'resolveEncryptionKey');
        $method->setAccessible(true);

        $encoder->config = new class {
            public function get($key)
            {
                if ($key === 'scoc_secret_key') {
                    return 'configured-secret';
                }

                return '';
            }
        };

        $this->assertSame('configured-secret', $method->invoke($encoder));
    }

    public function testValidateLoginFailsClosedWhenSecretMissing(): void
    {
        $_SERVER['QUERY_STRING'] = 'u=admin&p=password';

        $response = new class {
            public string $output = '';

            public function setOutput(string $text): void
            {
                $this->output = $text;
            }
        };

        $registry = new class ($response) {
            private $response;

            public function __construct($response)
            {
                $this->response = $response;
            }

            public function get($name)
            {
                if ($name === 'response') {
                    return $this->response;
                }

                return null;
            }
        };

        $encoder = new \scoc_encoder($registry);
        $encoder->key = '';
        $encoder->response = $response;

        $this->assertNull($encoder->validateLogin());
        $this->assertStringContainsString('Authentication secret is not configured', $response->output);
    }
}
