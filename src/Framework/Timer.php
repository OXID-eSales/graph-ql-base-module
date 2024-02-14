<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Framework;

/**
 * Basic timing construct, start and end values are expected to be seconds.
 *
 * @internal This class is not covered by the backward compatibility promise
 */
class Timer
{
    private float $start;
    private float $end;

    public function start(): self
    {
        // Without the parameter, microtime returns a string, we want a float
        $this->start = microtime(true);

        return $this;
    }

    public function startAt(float $microtime): self
    {
        $this->start = $microtime;

        return $this;
    }

    public function stop(): self
    {
        $this->end = microtime(true);

        return $this;
    }

    /**
     * Returns duration of measured period in seconds
     */
    public function getDuration(): float
    {
        return $this->end - $this->start;
    }
}
