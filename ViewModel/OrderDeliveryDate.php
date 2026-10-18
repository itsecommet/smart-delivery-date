<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\ViewModel;

use DateTimeImmutable;
use Ecommet\SmartDeliveryDate\Model\DateFormatter;
use Ecommet\SmartDeliveryDate\Model\DeliveryDateService;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Model\Order;

/**
 * Chosen delivery date of the order being viewed (customer account and admin)
 */
class OrderDeliveryDate implements ArgumentInterface
{
    /**
     * @param Registry $registry
     * @param DateFormatter $dateFormatter
     */
    public function __construct(
        private readonly Registry $registry,
        private readonly DateFormatter $dateFormatter
    ) {
    }

    /**
     * Formatted date, empty when the order has none
     *
     * @return string
     */
    public function getFormattedDate(): string
    {
        // The registry is deprecated but still the only way core exposes the current order to blocks.
        $order = $this->registry->registry('current_order');
        if (!$order instanceof Order) {
            return '';
        }
        $value = (string)$order->getData(DeliveryDateService::FIELD);
        $date = $value !== '' ? DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10)) : false;

        return $date !== false ? $this->dateFormatter->format($date, (int)$order->getStoreId()) : '';
    }
}
