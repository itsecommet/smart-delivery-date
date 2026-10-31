<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Model;

use DateTimeImmutable;

/**
 * Result of a delivery estimate. All dates are in the store timezone; date-only values are at midnight.
 */
class Estimate
{
    /**
     * @param DateTimeImmutable $now Moment the estimate was made
     * @param DateTimeImmutable $processedDate Day the order is ready to ship
     * @param DateTimeImmutable $shippingDate Day the carrier collects the order
     * @param DateTimeImmutable $earliestDelivery First possible delivery day
     * @param DateTimeImmutable|null $latestDelivery Last day of the delivery window, null for a single date
     * @param DateTimeImmutable $nextCutoff Next moment after which the estimate moves on
     */
    public function __construct(
        private readonly DateTimeImmutable $now,
        private readonly DateTimeImmutable $processedDate,
        private readonly DateTimeImmutable $shippingDate,
        private readonly DateTimeImmutable $earliestDelivery,
        private readonly ?DateTimeImmutable $latestDelivery,
        private readonly DateTimeImmutable $nextCutoff
    ) {
    }

    /**
     * Moment the estimate was made
     *
     * @return DateTimeImmutable
     */
    public function getNow(): DateTimeImmutable
    {
        return $this->now;
    }

    /**
     * Day the order is ready to ship
     *
     * @return DateTimeImmutable
     */
    public function getProcessedDate(): DateTimeImmutable
    {
        return $this->processedDate;
    }

    /**
     * Day the carrier collects the order
     *
     * @return DateTimeImmutable
     */
    public function getShippingDate(): DateTimeImmutable
    {
        return $this->shippingDate;
    }

    /**
     * First possible delivery day
     *
     * @return DateTimeImmutable
     */
    public function getEarliestDelivery(): DateTimeImmutable
    {
        return $this->earliestDelivery;
    }

    /**
     * Last day of the delivery window, null for a single date
     *
     * @return DateTimeImmutable|null
     */
    public function getLatestDelivery(): ?DateTimeImmutable
    {
        return $this->latestDelivery;
    }

    /**
     * Next moment after which the estimate moves on
     *
     * @return DateTimeImmutable
     */
    public function getNextCutoff(): DateTimeImmutable
    {
        return $this->nextCutoff;
    }

    /**
     * Seconds left until the next cut-off
     *
     * @return int
     */
    public function getSecondsToCutoff(): int
    {
        return max(0, $this->nextCutoff->getTimestamp() - $this->now->getTimestamp());
    }
}
