<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Middlewares\Firewall;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class FirewallTest extends TestCase
{
    /**
     * @return array<array<string|string[]|int>>
     */
    public function firewallProvider(): array
    {
        return [
            ['123.234.123.10', ['123.234.123.10'], [], 200],
            ['123.456.789.10', ['123.456.789.20'], [], 403],
            ['123.456.789.10', ['123.234.123.11'], ['123.234.123.10'], 403],
            ['123.0.0.10', ['123.0.0.*'], [], 200],
            ['123.0.0.12', ['123.0.0.*'], ['123.0.0.12'], 403],
            ['', [], [], 403],
        ];
    }

    /**
     * @dataProvider firewallProvider
     *
     * @param string[] $whitelist
     * @param string[] $blacklist
     */
    public function testFirewall(string $ip, array $whitelist, array $blacklist, int $status): void
    {
        $response = Dispatcher::run(
            [
                (new Firewall($whitelist))->blacklist($blacklist),
            ],
            Factory::createServerRequest('GET', '/', ['REMOTE_ADDR' => $ip])
        );

        $this->assertEquals($status, $response->getStatusCode());
    }

    public function testIpAttribute(): void
    {
        $response = Dispatcher::run(
            [
                (new Firewall(['123.0.0.*']))->ipAttribute('client-ip'),
            ],
            Factory::createServerRequest('GET', '/')->withAttribute('client-ip', '123.0.0.1')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }
}
