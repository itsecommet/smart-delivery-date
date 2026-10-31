<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Model;

/**
 * Turns an estimate into the plain data the storefront scripts render (Luma, Hyvä and checkout)
 */
class EstimatePresenter
{
    /**
     * @param Config $config
     * @param DateFormatter $dateFormatter
     */
    public function __construct(
        private readonly Config $config,
        private readonly DateFormatter $dateFormatter
    ) {
    }

    /**
     * Data for one estimate
     *
     * @param Estimate $estimate
     * @param int|null $storeId
     * @return array<string, mixed>
     */
    public function present(Estimate $estimate, ?int $storeId = null): array
    {
        $latest = $estimate->getLatestDelivery();

        return [
            'earliest' => $estimate->getEarliestDelivery()->format('Y-m-d'),
            'earliestLabel' => $this->dateFormatter->format($estimate->getEarliestDelivery(), $storeId),
            'latest' => $latest ? $latest->format('Y-m-d') : null,
            'latestLabel' => $latest ? $this->dateFormatter->format($latest, $storeId) : null,
            'secondsToCutoff' => $estimate->getSecondsToCutoff(),
        ];
    }

    /**
     * Translated message templates; {placeholders} are filled in by the storefront script
     *
     * @param int|null $storeId
     * @return array<string, mixed>
     */
    public function getPhrases(?int $storeId = null): array
    {
        return [
            'showCountdown' => $this->config->isCountdownShown($storeId),
            'messageCountdown' => $this->config->getCountdownMessage($storeId)
                ?: (string)__('Order within {countdown} and get it {delivery}'),
            'message' => $this->config->getMessage($storeId)
                ?: (string)__('Order now and get it {delivery}'),
            'single' => (string)__('on {date}'),
            'range' => (string)__('between {from} and {to}'),
            'units' => [
                'd' => (string)__('%1d', '{n}'),
                'h' => (string)__('%1h', '{n}'),
                'm' => (string)__('%1m', '{n}'),
                's' => (string)__('%1s', '{n}'),
            ],
        ];
    }
}
