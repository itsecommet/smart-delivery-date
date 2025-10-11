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

class CompanyProcessInfoService implements \Ecommet\SmartDeliveryDate\Api\CompanyProcessInfoServiceInterface
{
    /**
     * @var Configuration
     */
    private Configuration $helperConfiguration;

    /**
     * CompanyProcessInfoService constructor.
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
    public function isCompanyWorkDay(DateTime $dateTime): bool
    {
        return in_array($dateTime->format('w'), $this->helperConfiguration->getWorkingDays(), true);
    }

    /**
     * @inheritDoc
     */
    public function isExcludedCompanyProcessingDate(DateTime $dateTime): bool
    {
        $date = $dateTime->format('d/m/Y');

        foreach ($this->helperConfiguration->getExcludedProcessingDates() as $excludedDate) {
            if ($excludedDate->format('d/m/Y') === $date) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getFirstCompanyWorkDateSinceDate(DateTime $dateTime, bool $includeGivenDateTime = true): DateTime
    {
        $date = clone $dateTime;

        if (!$includeGivenDateTime) {
            $date->add(new DateInterval('P1D'));
        }

        while (!$this->isCompanyWorkDay($date) || $this->isExcludedCompanyProcessingDate($date)) {
            $date->add(new DateInterval('P1D'));
        }

        return $date;
    }

    /**
     * @inheritDoc
     */
    public function getOrderProcessedDate(DateTime $orderDate): DateTime
    {
        $timezone = new \DateTimeZone('Asia/Kolkata'); // or store timezone
        $processLimitDateTime = new DateTime($orderDate->format('Y-m-d') . ' '
            . $this->helperConfiguration->getProcessOrderTodayTimeLimit(), $timezone);
        $daysNeeded = (int)$this->helperConfiguration->getDaysNeededForProcessing();

        $processedDate = clone $orderDate;

        // Check if order placed after cutoff
        if ($orderDate > $processLimitDateTime) {
            // order placed after cutoff → move to next available workday
            $processedDate->add(new DateInterval('P1D'));
            $processedDate = $this->getFirstCompanyWorkDateSinceDate($processedDate);
        } else {
            // order placed before cutoff → first possible day is today
            $processedDate = $this->getFirstCompanyWorkDateSinceDate($processedDate);
        }

        // Now add processing days
        while ($daysNeeded > 0) {
            $processedDate->add(new DateInterval('P1D'));
            $processedDate = $this->getFirstCompanyWorkDateSinceDate($processedDate);
            $daysNeeded--;
        }

        return $processedDate;
    }
}
