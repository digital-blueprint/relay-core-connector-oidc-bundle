<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class KernelTest extends KernelTestCase
{
    public function testBasics()
    {
        $container = static::getContainer();
        $this->assertNotNull($container);
    }
}
