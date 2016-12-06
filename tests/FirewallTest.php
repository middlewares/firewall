<?php

namespace Middlewares\Tests;

use Middlewares\Firewall;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;

class FirewallTest extends \PHPUnit_Framework_TestCase
{
    public function firewallProvider()
    {
        return [
            ['123.234.123.10', ['123.234.123.10'], [], 200],
            ['123.456.789.10', ['123.456.789.20'], [], 403],
            ['123.456.789.10', ['123.234.123.11'], ['123.234.123.10'], 403],
            ['123.0.0.10', ['123.0.0.*'], [], 200],
            ['123.0.0.12', ['123.0.0.*'], ['123.0.0.12'], 403],
            [null, [], [], 403],
        ];
    }

    /**
     * @dataProvider firewallProvider
     */
    public function testFirewall($ip, $whitelist, $blacklist, $status)
    {
        $request = Factory::createServerRequest(['REMOTE_ADDR' => $ip]);

        $response = (new Dispatcher([
            (new Firewall($whitelist))->blacklist($blacklist),
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals($status, $response->getStatusCode());
    }
}
