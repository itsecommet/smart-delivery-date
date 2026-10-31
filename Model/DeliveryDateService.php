<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Model;

use DateTimeImmutable;
use Ecommet\SmartDeliveryDate\Exception\NoWorkingDayException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address;
use Psr\Log\LoggerInterface;

/**
 * Estimates and validates the customer-selected delivery date of a cart or a shipping address
 */
class DeliveryDateService
{
    /**
     * Column on quote, quote_address, sales_order and sales_order_grid
     */
    public const FIELD = 'ecommet_delivery_date';

    /**
     * @param Config $config
     * @param EstimateCalculator $calculator
     * @param ProcessingTimeResolver $processingTimeResolver
     * @param DateFormatter $dateFormatter
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Config $config,
        private readonly EstimateCalculator $calculator,
        private readonly ProcessingTimeResolver $processingTimeResolver,
        private readonly DateFormatter $dateFormatter,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Estimate for the whole cart, null when the module is off, the cart is virtual or the setup is incomplete
     *
     * @param CartInterface $quote
     * @return Estimate|null
     */
    public function getEstimateForQuote(CartInterface $quote): ?Estimate
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $storeId = (int)$quote->getStoreId();
        if (!$this->config->isEnabled($storeId) || $quote->isVirtual()) {
            return null;
        }

        return $this->safeEstimate($storeId, $this->processingTimeResolver->forQuote($quote));
    }

    /**
     * Estimate for the items shipped to one address (multi-address checkout)
     *
     * @param Address $address
     * @return Estimate|null
     */
    public function getEstimateForAddress(Address $address): ?Estimate
    {
        $storeId = (int)$address->getQuote()->getStoreId();
        if (!$this->config->isEnabled($storeId) || !$address->getAllVisibleItems()) {
            return null;
        }

        return $this->safeEstimate($storeId, $this->processingTimeResolver->forAddress($address));
    }

    /**
     * Estimate that never throws; configuration problems are logged
     *
     * @param int $storeId
     * @param int|null $processingTime
     * @return Estimate|null
     */
    public function safeEstimate(int $storeId, ?int $processingTime = null): ?Estimate
    {
        try {
            return $this->calculator->estimate($storeId, $processingTime);
        } catch (NoWorkingDayException $e) {
            $this->logger->warning('Ecommet_SmartDeliveryDate: ' . $e->getMessage(), ['store' => $storeId]);
            return null;
        }
    }

    /**
     * Dates the customer may choose
     *
     * @param Estimate $estimate
     * @param int $storeId
     * @return DateTimeImmutable[]
     */
    public function getSelectableDates(Estimate $estimate, int $storeId): array
    {
        return $this->calculator->getSelectableDates($estimate, $storeId);
    }

    /**
     * Validate a chosen date and return it as Y-m-d, or null when no date is stored
     *
     * @param string|null $date Y-m-d
     * @param Estimate|null $estimate
     * @param int $storeId
     * @return string|null
     * @throws LocalizedException
     */
    public function validate(?string $date, ?Estimate $estimate, int $storeId): ?string
    {
        if (!$this->config->isSelectionEnabled($storeId) || $estimate === null) {
            return null;
        }

        $date = trim((string)$date);
        if ($date === '') {
            if ($this->config->isSelectionRequired($storeId)) {
                throw new LocalizedException(__('Please choose a delivery date.'));
            }
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $estimate->getEarliestDelivery()->getTimezone());
        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new LocalizedException(__('The delivery date "%1" is not a valid date.', $date));
        }

        foreach ($this->getSelectableDates($estimate, $storeId) as $selectable) {
            if ($selectable->format('Y-m-d') === $date) {
                return $date;
            }
        }

        throw new LocalizedException(
            __(
                'We cannot deliver on %1. Please choose another delivery date (earliest: %2).',
                $this->dateFormatter->format($parsed, $storeId),
                $this->dateFormatter->format($estimate->getEarliestDelivery(), $storeId)
            )
        );
    }
}
