<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Observer;

use DateTimeImmutable;
use Ecommet\SmartDeliveryDate\Model\DateFormatter;
use Ecommet\SmartDeliveryDate\Model\DeliveryDateService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * email_order_set_template_vars_before: adds {{var ecommet_delivery_date}} to order e-mails
 */
class AddEmailVariables implements ObserverInterface
{
    /**
     * @param DateFormatter $dateFormatter
     */
    public function __construct(
        private readonly DateFormatter $dateFormatter
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Framework\DataObject $transport */
        $transport = $observer->getData('transportObject');
        /** @var \Magento\Sales\Model\Order|null $order */
        $order = $transport ? $transport->getData('order') : null;
        $value = $order ? (string)$order->getData(DeliveryDateService::FIELD) : '';
        if ($value === '') {
            return;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));
        if ($date !== false) {
            $transport->setData(
                DeliveryDateService::FIELD,
                $this->dateFormatter->format($date, (int)$order->getStoreId())
            );
        }
    }
}
