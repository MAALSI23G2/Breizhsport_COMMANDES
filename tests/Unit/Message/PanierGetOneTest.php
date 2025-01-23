<?php

namespace App\Tests\Unit\Message;

use App\Message\PanierGetOne;
use PHPUnit\Framework\TestCase;

class PanierGetOneTest extends TestCase
{
    public function testPanierGetOneConstructor(): void
    {
        $message = new PanierGetOne(1);

        $this->assertEquals(1, $message->getUserId());
        $this->assertNotEmpty($message->getUniqueId());
    }

    public function testPanierGetOneWithCustomUniqueId(): void
    {
        $uniqueId = 'custom-id';
        $message = new PanierGetOne(2, $uniqueId);

        $this->assertEquals(2, $message->getUserId());
        $this->assertEquals($uniqueId, $message->getUniqueId());
    }
}
