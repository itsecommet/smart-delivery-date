<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Plugin\Checkout;

use Ecommet\SmartDeliveryDate\Model\DeliveryDateService;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Validates the delivery date sent with the shipping step and stores it on the cart
 */
class SaveDeliveryDate
{
    /**
     * @param CartRepositoryInterface $cartRepository
     * @param DeliveryDateService $deliveryDateService
     */
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly DeliveryDateService $deliveryDateService
    ) {
    }

    /**
     * Validate and store the date before the shipping information (and the cart) is saved
     *
     * @param ShippingInformationManagementInterface $subject
     * @param int $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return null
     * @throws InputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagementInterface $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->cartRepository->getActive((int)$cartId);
        $extension = $addressInformation->getExtensionAttributes();
        $date = $extension ? $extension->getEcommetDeliveryDate() : null;

        try {
            $value = $this->deliveryDateService->validate(
                $date,
                $this->deliveryDateService->getEstimateForQuote($quote),
                (int)$quote->getStoreId()
            );
        } catch (LocalizedException $e) {
            throw new InputException(__($e->getMessage()), $e);
        }
        $quote->setData(DeliveryDateService::FIELD, $value);

        return null;
    }
}
