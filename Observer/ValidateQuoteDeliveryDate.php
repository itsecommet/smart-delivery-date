<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Observer;

use Ecommet\SmartDeliveryDate\Model\DeliveryDateService;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * sales_model_service_quote_submit_before: re-checks the chosen date at the moment the order is placed
 * (a date can expire between the shipping step and the place-order click) and copies it to the order.
 */
class ValidateQuoteDeliveryDate implements ObserverInterface
{
    /**
     * @param DeliveryDateService $deliveryDateService
     * @param State $appState
     */
    public function __construct(
        private readonly DeliveryDateService $deliveryDateService,
        private readonly State $appState
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getData('order');
        $date = $quote->getData(DeliveryDateService::FIELD);

        // Orders created by an admin are not restricted; the date is kept if one was set.
        if (!$this->isAdminArea()) {
            $date = $this->deliveryDateService->validate(
                $date,
                $this->deliveryDateService->getEstimateForQuote($quote),
                (int)$quote->getStoreId()
            );
        }

        $order->setData(DeliveryDateService::FIELD, $date ?: null);
    }

    /**
     * Whether the order is being created in the admin panel
     *
     * @return bool
     */
    private function isAdminArea(): bool
    {
        try {
            return $this->appState->getAreaCode() === Area::AREA_ADMINHTML;
        } catch (LocalizedException $e) {
            return false;
        }
    }
}
