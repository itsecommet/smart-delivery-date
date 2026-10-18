<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Observer\Multishipping;

use Ecommet\SmartDeliveryDate\Model\DeliveryDateService;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * checkout_controller_multishipping_shipping_post: validates and stores one date per shipping address.
 * An exception sends the customer back to the shipping step with the message.
 */
class SaveDeliveryDates implements ObserverInterface
{
    public const REQUEST_PARAM = 'ecommet_delivery_date';

    /**
     * @param DeliveryDateService $deliveryDateService
     */
    public function __construct(
        private readonly DeliveryDateService $deliveryDateService
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        /** @var RequestInterface $request */
        $request = $observer->getData('request');
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');
        $dates = (array)$request->getParam(self::REQUEST_PARAM, []);

        foreach ($quote->getAllShippingAddresses() as $address) {
            $date = $dates[$address->getId()] ?? null;
            $value = $this->deliveryDateService->validate(
                is_string($date) ? $date : null,
                $this->deliveryDateService->getEstimateForAddress($address),
                (int)$quote->getStoreId()
            );
            // Saved with the quote by Multishipping::setShippingMethods() right after this event.
            $address->setData(DeliveryDateService::FIELD, $value);
        }
    }
}
