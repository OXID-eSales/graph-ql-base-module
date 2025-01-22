<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Framework;

use OxidEsales\GraphQL\Base\Framework\Timer;
use OxidEsales\GraphQL\Base\Framework\TimerHandler;
use PHPUnit\Framework\TestCase;

class TimerHandlerTest extends TestCase
{
    public function testTimerHandler(): void
    {
        $timerHandler = new TimerHandler();

        $this->assertEquals(Timer::class, get_class($timerHandler->create('test')));
        $this->assertTrue(is_array($timerHandler->getTimers()));
    }
}
