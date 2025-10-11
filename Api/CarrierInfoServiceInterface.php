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

namespace Ecommet\SmartDeliveryDate\Api;

use DateTime;

interface CarrierInfoServiceInterface
{
    /**
     * Check for a given date if the that day there is carrier service usually
     *
     * @param DateTime $dateTime
     * @return bool
     */
    public function isCarrierWorkDay(DateTime $dateTime): bool;

    /**
     * Retrieve list of dates which there are no carrier service
     *
     * @return array
     */
    public function getExcludedDatesForCarrier(): array;

    /**
     * Check for a given date if that date is excluded for carrier service
     *
     * @param DateTime $dateTime
     * @return bool
     */
    public function isExcludedCarrierDate(DateTime $dateTime): bool;

    /**
     * Calculate the shipping date  for a given order processed date
     *
     * @param DateTime $orderProcessedDate
     * @return DateTime
     */
    public function getShippingDate(DateTime $orderProcessedDate): DateTime;

    /**
     * Retrieves the delivery day with delivery service for a given shipping date
     *
     * @param DateTime $shippingDate
     * @return DateTime
     */
    public function getDeliveryDate(DateTime $shippingDate): DateTime;

    /**
     * Retrieve delivery information for the order date given
     *
     * @param DateTime $orderProcessedDate
     * @return array
     */
    public function getDeliveryEstimation(DateTime $orderProcessedDate): array;
}
