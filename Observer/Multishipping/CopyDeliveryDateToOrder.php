<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Observer\Multishipping;

use Ecommet\SmartDeliveryDate\Model\DeliveryDateService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * checkout_type_multishipping_create_orders_single: re-checks each address date and copies it to its order.
 * The event fires for every order before any of them is placed, so an exception places nothing.
 */
class CopyDeliveryDateToOrder implements ObserverInterface
{
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
        /** @var \Magento\Quote\Model\Quote\Address $address */
        $address = $observer->getData('address');
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getData('order');

        if ($address->getAddressType() !== \Magento\Quote\Model\Quote\Address::ADDRESS_TYPE_SHIPPING) {
            return;
        }

        $date = $this->deliveryDateService->validate(
            $address->getData(DeliveryDateService::FIELD),
            $this->deliveryDateService->getEstimateForAddress($address),
            (int)$address->getQuote()->getStoreId()
        );
        $order->setData(DeliveryDateService::FIELD, $date);
    }
}
