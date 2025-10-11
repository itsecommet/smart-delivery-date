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

interface DeliveryDateServiceInterface
{
    /**
     * Retrieve delivery dates for a given date (or for an order placed at this moment)
     *
     * @param DateTime|null $orderDateTime
     * @return array
     */
    public function getDeliveryDates(DateTime $orderDateTime): array;
}
