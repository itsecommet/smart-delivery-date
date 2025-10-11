<?php

/**
 * Smart Delivery Date Module
 *
 * Provides estimated delivery date calculation based on working days,
 * holidays, processing times. Includes
 * admin configuration, frontend display, and integration with checkout.
 *
 * @category   Ecommet
 * @package    Ecommet_SmartDeliveryDate
 * @author     Ecommet Solutions <ecommet.solutions@gmail.com>
 * @copyright  Copyright © Ecommet Solutions. All rights reserved.
 * @license    https://ecommet.in/license Commercial License
 * @link       https://ecommet.in
 */

namespace Ecommet\SmartDeliveryDate\Model;

use DateInterval;
use DateTime;
use Ecommet\SmartDeliveryDate\Helper\Configuration;

class CarrierInfoService implements \Ecommet\SmartDeliveryDate\Api\CarrierInfoServiceInterface
{
    public const SHIPPING_DATE_KEY = 'shippingDate';
    public const OPEN_DELIVERY_KEY = 'openDelivery';
    public const DELIVERY_DATES_KEY = 'deliveryDates';

    /**
     * @var Configuration
     */
    private Configuration $helperConfiguration;

    /**
     * CarrierInfoService constructor.
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->helperConfiguration = $configuration;
    }

    /**
     * @inheritDoc
     */
    public function isCarrierWorkDay(DateTime $dateTime): bool
    {
        $day = $dateTime->format('w');
        if (in_array($day, $this->helperConfiguration->getDeliveryWorkDays())) {
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getExcludedDatesForCarrier(): array
    {
        if ($this->helperConfiguration->useExcludedProcessingDatesAsExcludedDeliveryDates()) {
            return $this->helperConfiguration->getExcludedProcessingDates();
        } else {
            return $this->helperConfiguration->getExcludedDeliveryDates();
        }
    }

    /**
     * @inheritDoc
     */
    public function isExcludedCarrierDate(DateTime $dateTime): bool
    {
        $date = $dateTime->format("d/m/Y");

        foreach ($this->getExcludedDatesForCarrier() as $excludedDate) {
            if ($date == $excludedDate->format("d/m/Y")) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getShippingDate(DateTime $orderProcessedDate): DateTime
    {
        return $this->calculateFutureCarrierWorkDate($orderProcessedDate, 0);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryDate(DateTime $shippingDate): DateTime
    {
        $daysNeededForDelivery = $this->helperConfiguration->getDaysNeededForDelivery();
        return $this->calculateFutureCarrierWorkDate($shippingDate, $daysNeededForDelivery);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryEstimation(DateTime $orderProcessedDate): array
    {
        $shippingDate = $this->getShippingDate($orderProcessedDate);
        $deliveryDate = $this->getDeliveryDate($shippingDate);

        $deliveryDates = [$deliveryDate];

        if ($this->helperConfiguration->isEnabledOpenDeliveryDate()) {
            $deliveryDates[] = $this->calculateFutureCarrierWorkDate(
                $deliveryDate,
                $this->helperConfiguration->getOpenDeliveryRangeDays()
            );
        }

        return [
            self::SHIPPING_DATE_KEY  => $shippingDate,
            self::OPEN_DELIVERY_KEY  => $this->helperConfiguration->isEnabledOpenDeliveryDate(),
            self::DELIVERY_DATES_KEY => $deliveryDates,
        ];
    }

    /**
     * Calculate the future carrier work date based on the given date and number of carrier work days from that date.
     *
     * @param DateTime $datetime
     * @param int $numCarrierWorkDaysFromGivenDate
     * @return DateTime
     */
    private function calculateFutureCarrierWorkDate(Datetime $datetime, int $numCarrierWorkDaysFromGivenDate): DateTime
    {
        $carrierWorkDate = clone $datetime;

        if ($numCarrierWorkDaysFromGivenDate == 0) {
            while (!($this->isCarrierWorkDay($carrierWorkDate) && !$this->isExcludedCarrierDate($carrierWorkDate))) {
                $carrierWorkDate->add(new DateInterval('P1D'));
            }
            return $carrierWorkDate;
        }

        while ($numCarrierWorkDaysFromGivenDate > 0) {
            $carrierWorkDate->add(new DateInterval('P1D'));
            if ($this->isCarrierWorkDay($carrierWorkDate) && !$this->isExcludedCarrierDate($carrierWorkDate)) {
                $numCarrierWorkDaysFromGivenDate--;
            }
        }

        return $carrierWorkDate;
    }
}
