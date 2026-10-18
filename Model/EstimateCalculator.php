<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Model;

use DateTimeImmutable;
use DateTimeZone;
use Ecommet\SmartDeliveryDate\Exception\NoWorkingDayException;
use Ecommet\SmartDeliveryDate\Model\Calendar\WorkCalendar;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Calculates delivery estimates and the dates a customer may choose, in the store timezone
 */
class EstimateCalculator
{
    /**
     * @param Config $config
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private readonly Config $config,
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * Estimate delivery for an order placed now (or at $now)
     *
     * @param int|null $storeId
     * @param int|null $processingTime Processing days, null for the configured default
     * @param DateTimeImmutable|null $now
     * @return Estimate
     * @throws NoWorkingDayException
     */
    public function estimate(
        ?int $storeId = null,
        ?int $processingTime = null,
        ?DateTimeImmutable $now = null
    ): Estimate {
        $now = ($now ?? new DateTimeImmutable('now'))->setTimezone($this->getTimezone($storeId));
        $today = $now->setTime(0, 0);
        [$hour, $minute, $second] = $this->config->getCutoffTime($storeId);
        $processing = $this->getProcessingCalendar($storeId);
        $carrier = $this->getCarrierCalendar($storeId);

        $todayCutoff = $today->setTime($hour, $minute, $second);
        if ($processing->isWorkingDay($today) && $now <= $todayCutoff) {
            $start = $today;
        } else {
            $start = $processing->firstWorkingDayFrom($today->modify('+1 day'));
        }

        $processed = $processing->addWorkingDays(
            $start,
            $processingTime ?? $this->config->getProcessingTime($storeId)
        );
        $shipping = $carrier->firstWorkingDayFrom($processed);
        $earliest = $carrier->addWorkingDays($shipping, $this->config->getTransitTime($storeId));
        $windowLength = $this->config->getDeliveryWindowLength($storeId);
        $latest = $windowLength > 0 ? $carrier->addWorkingDays($earliest, $windowLength) : null;

        return new Estimate(
            $now,
            $processed,
            $shipping,
            $earliest,
            $latest,
            $start->setTime($hour, $minute, $second)
        );
    }

    /**
     * Delivery dates a customer may choose for the given estimate
     *
     * @param Estimate $estimate
     * @param int|null $storeId
     * @return DateTimeImmutable[]
     */
    public function getSelectableDates(Estimate $estimate, ?int $storeId = null): array
    {
        $carrier = $this->getCarrierCalendar($storeId);
        $date = $estimate->getEarliestDelivery();
        $last = $date->modify('+' . $this->config->getSelectionMaxDaysAhead($storeId) . ' days');

        $dates = [];
        while ($date <= $last) {
            if ($carrier->isWorkingDay($date)) {
                $dates[] = $date;
            }
            $date = $date->modify('+1 day');
        }

        return $dates;
    }

    /**
     * Calendar of the merchant's processing days
     *
     * @param int|null $storeId
     * @return WorkCalendar
     */
    public function getProcessingCalendar(?int $storeId = null): WorkCalendar
    {
        return new WorkCalendar(
            $this->config->getProcessingDaysOfWeek($storeId),
            $this->config->getProcessingExcludedDates($storeId)
        );
    }

    /**
     * Calendar of the carrier's delivery days
     *
     * @param int|null $storeId
     * @return WorkCalendar
     */
    public function getCarrierCalendar(?int $storeId = null): WorkCalendar
    {
        return new WorkCalendar(
            $this->config->getDeliveryDaysOfWeek($storeId),
            $this->config->getDeliveryExcludedDates($storeId)
        );
    }

    /**
     * Store timezone
     *
     * @param int|null $storeId
     * @return DateTimeZone
     */
    public function getTimezone(?int $storeId = null): DateTimeZone
    {
        return new DateTimeZone(
            (string)$this->timezone->getConfigTimezone(ScopeInterface::SCOPE_STORE, $storeId) ?: 'UTC'
        );
    }
}
