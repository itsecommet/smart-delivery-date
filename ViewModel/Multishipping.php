<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\ViewModel;

use Ecommet\SmartDeliveryDate\Model\Config;
use Ecommet\SmartDeliveryDate\Model\DateFormatter;
use Ecommet\SmartDeliveryDate\Model\DeliveryDateService;
use Ecommet\SmartDeliveryDate\Observer\Multishipping\SaveDeliveryDates;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Model\Quote\Address;

/**
 * Per-address delivery date field and summary for multi-address checkout
 */
class Multishipping implements ArgumentInterface
{
    /**
     * @param Config $config
     * @param DeliveryDateService $deliveryDateService
     * @param DateFormatter $dateFormatter
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        private readonly Config $config,
        private readonly DeliveryDateService $deliveryDateService,
        private readonly DateFormatter $dateFormatter,
        private readonly CheckoutSession $checkoutSession
    ) {
    }

    /**
     * Field data for one shipping address, null when nothing should be shown
     *
     * @param Address $address
     * @return array{name: string, id: string, label: string, note: string, required: bool, estimate: string,
     *     latest: string, selected: string, options: array<int, array{value: string, label: string}>}|null
     */
    public function getAddressField(Address $address): ?array
    {
        $storeId = (int)$address->getQuote()->getStoreId();
        $estimate = $this->deliveryDateService->getEstimateForAddress($address);
        $showEstimate = $this->config->isShownOn('checkout', $storeId);
        $selectable = $this->config->isSelectionEnabled($storeId);
        if ($estimate === null || (!$showEstimate && !$selectable)) {
            return null;
        }

        $options = [];
        if ($selectable) {
            foreach ($this->deliveryDateService->getSelectableDates($estimate, $storeId) as $date) {
                $options[] = [
                    'value' => $date->format('Y-m-d'),
                    'label' => $this->dateFormatter->format($date, $storeId),
                ];
            }
        }
        $latest = $estimate->getLatestDelivery();

        return [
            'name' => SaveDeliveryDates::REQUEST_PARAM . '[' . (int)$address->getId() . ']',
            'id' => 'ecommet-delivery-date-' . (int)$address->getId(),
            'label' => $this->config->getSelectionLabel($storeId) ?: (string)__('Preferred Delivery Date'),
            'note' => $this->config->getSelectionNote($storeId),
            'required' => $this->config->isSelectionRequired($storeId),
            'estimate' => $showEstimate ? $this->dateFormatter->format($estimate->getEarliestDelivery(), $storeId) : '',
            'latest' => $showEstimate && $latest ? $this->dateFormatter->format($latest, $storeId) : '',
            'selected' => (string)$address->getData(DeliveryDateService::FIELD),
            'options' => $options,
        ];
    }

    /**
     * Chosen dates of the current multi-address checkout, for the review step
     *
     * @return array<int, array{address: string, date: string}>
     */
    public function getSelectedDates(): array
    {
        $quote = $this->checkoutSession->getQuote();
        $rows = [];
        foreach ($quote->getAllShippingAddresses() as $address) {
            $value = (string)$address->getData(DeliveryDateService::FIELD);
            $date = $value !== '' ? \DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10)) : false;
            if ($date !== false) {
                $rows[] = [
                    'address' => implode(', ', array_filter([
                        $address->getName(),
                        implode(' ', (array)$address->getStreet()),
                        $address->getCity(),
                    ])),
                    'date' => $this->dateFormatter->format($date, (int)$quote->getStoreId()),
                ];
            }
        }

        return $rows;
    }
}
