<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Model\Checkout;

use Ecommet\SmartDeliveryDate\Model\Config;
use Ecommet\SmartDeliveryDate\Model\DeliveryDateService;
use Ecommet\SmartDeliveryDate\Model\EstimatePresenter;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;

/**
 * window.checkoutConfig.ecommetDeliveryDate for the one-page checkout
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @param CheckoutSession $checkoutSession
     * @param Config $config
     * @param DeliveryDateService $deliveryDateService
     * @param EstimatePresenter $presenter
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly Config $config,
        private readonly DeliveryDateService $deliveryDateService,
        private readonly EstimatePresenter $presenter,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        $quote = $this->checkoutSession->getQuote();
        $storeId = (int)$quote->getStoreId();
        $estimate = $this->deliveryDateService->getEstimateForQuote($quote);
        if ($estimate === null) {
            return ['ecommetDeliveryDate' => ['active' => false]];
        }

        $dates = array_map(
            static fn(\DateTimeImmutable $date): string => $date->format('Y-m-d'),
            $this->deliveryDateService->getSelectableDates($estimate, $storeId)
        );
        $selected = (string)$quote->getData(DeliveryDateService::FIELD);

        return [
            'ecommetDeliveryDate' => [
                'active' => true,
                'url' => $this->urlBuilder->getUrl('smartdelivery/estimate/index', ['_query' => ['cart' => 1]]),
                'showEstimate' => $this->config->isShownOn('checkout', $storeId),
                'estimate' => $this->presenter->present($estimate, $storeId),
                'phrases' => $this->presenter->getPhrases($storeId),
                'selection' => [
                    'enabled' => $this->config->isSelectionEnabled($storeId),
                    'required' => $this->config->isSelectionRequired($storeId),
                    'label' => $this->config->getSelectionLabel($storeId) ?: (string)__('Preferred Delivery Date'),
                    'note' => $this->config->getSelectionNote($storeId),
                    'dates' => $dates,
                    'selected' => in_array($selected, $dates, true) ? $selected : '',
                ],
            ],
        ];
    }
}
