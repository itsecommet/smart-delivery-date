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

use Ecommet\SmartDeliveryDate\Api\CarrierInfoServiceInterface;
use Ecommet\SmartDeliveryDate\Api\CompanyProcessInfoServiceInterface;
use DateTime;

class DeliveryDateService implements \Ecommet\SmartDeliveryDate\Api\DeliveryDateServiceInterface
{
    /**
     * @var CarrierInfoServiceInterface
     */
    private CarrierInfoServiceInterface $carrierInfoService;

    /**
     * @var CompanyProcessInfoServiceInterface
     */
    private CompanyProcessInfoServiceInterface $companyProcessInfoService;

    /**
     * DeliveryDateService constructor.
     *
     * @param CarrierInfoServiceInterface        $carrierInfoService
     * @param CompanyProcessInfoServiceInterface $companyProcessInfoService
     */
    public function __construct(
        CarrierInfoServiceInterface        $carrierInfoService,
        CompanyProcessInfoServiceInterface $companyProcessInfoService
    ) {
        $this->carrierInfoService = $carrierInfoService;
        $this->companyProcessInfoService = $companyProcessInfoService;
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryDates(DateTime $orderDateTime): array
    {
        $orderProcessedDate = $this->companyProcessInfoService->getOrderProcessedDate($orderDateTime);
        $deliveryInformation = $this->carrierInfoService->getDeliveryEstimation($orderProcessedDate);

        return $deliveryInformation[CarrierInfoService::DELIVERY_DATES_KEY];
    }
}
