<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Plugin\Sales;

use Ecommet\SmartDeliveryDate\Model\DeliveryDateService;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Exposes the delivery date as extension_attributes.ecommet_delivery_date in the order API
 */
class AddDeliveryDateToOrder
{
    /**
     * @param OrderExtensionFactory $extensionFactory
     */
    public function __construct(
        private readonly OrderExtensionFactory $extensionFactory
    ) {
    }

    /**
     * Add the date to a single order
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order): OrderInterface
    {
        return $this->addDeliveryDate($order);
    }

    /**
     * Add the date to every order of a list
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $result
     * @return OrderSearchResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $result
    ): OrderSearchResultInterface {
        foreach ($result->getItems() as $order) {
            $this->addDeliveryDate($order);
        }

        return $result;
    }

    /**
     * Copy the column value into the extension attributes
     *
     * @param OrderInterface $order
     * @return OrderInterface
     */
    private function addDeliveryDate(OrderInterface $order): OrderInterface
    {
        $extension = $order->getExtensionAttributes() ?? $this->extensionFactory->create();
        /** @var \Magento\Sales\Model\Order $order */
        $extension->setEcommetDeliveryDate($order->getData(DeliveryDateService::FIELD));
        $order->setExtensionAttributes($extension);

        return $order;
    }
}
