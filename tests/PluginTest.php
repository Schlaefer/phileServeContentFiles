<?php

namespace Phile\Plugin\Siezi\PhileServeContentFiles\Tests;

use Phile\Core\Config;
use Phile\Test\TestCase;
use Phile\Core\Event;

class PluginTest extends TestCase
{
    public function testPlugingLoadsWithoutError()
    {
        $config = new Config(
            [
                'plugins' => [
                    'siezi\\phileServeContentFiles' => ['active' => true]
                ]
            ]
        );

        $core = $this->createPhileCore(null, $config);
        $request = $this->createServerRequestFromArray();
        $response = $this->createPhileResponse($core, $request);

        $body = (string)$response->getBody();
        $this->assertSame(200, $response->getStatusCode());
    }
}
