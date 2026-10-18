<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Controller\Estimate;

use Ecommet\SmartDeliveryDate\Model\Config;
use Ecommet\SmartDeliveryDate\Model\DeliveryDateService;
use Ecommet\SmartDeliveryDate\Model\Estimate;
use Ecommet\SmartDeliveryDate\Model\EstimatePresenter;
use Ecommet\SmartDeliveryDate\Model\ProcessingTimeResolver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * GET smartdelivery/estimate/index?product_id=… or ?cart=1
 *
 * Product and cart pages are full-page cached, so the time-dependent estimate is fetched here.
 */
class Index implements HttpGetActionInterface
{
    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param DeliveryDateService $deliveryDateService
     * @param EstimatePresenter $presenter
     * @param ProcessingTimeResolver $processingTimeResolver
     * @param ProductRepositoryInterface $productRepository
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $config,
        private readonly DeliveryDateService $deliveryDateService,
        private readonly EstimatePresenter $presenter,
        private readonly ProcessingTimeResolver $processingTimeResolver,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CheckoutSession $checkoutSession
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);

        $storeId = 0;
        try {
            $storeId = (int)$this->storeManager->getStore()->getId();
            $estimate = $this->request->getParam('cart')
                ? $this->getCartEstimate($storeId)
                : $this->getProductEstimate((int)$this->request->getParam('product_id'), $storeId);
        } catch (LocalizedException $e) {
            $estimate = null;
        }

        if ($estimate === null) {
            return $result->setData(['success' => false]);
        }

        $data = ['success' => true, 'estimate' => $this->presenter->present($estimate, $storeId)];
        if ($this->request->getParam('cart') && $this->config->isSelectionEnabled($storeId)) {
            $data['selectableDates'] = array_map(
                static fn(\DateTimeImmutable $date): string => $date->format('Y-m-d'),
                $this->deliveryDateService->getSelectableDates($estimate, $storeId)
            );
        }

        return $result->setData($data);
    }

    /**
     * Estimate for one product
     *
     * @param int $productId
     * @param int $storeId
     * @return Estimate|null
     */
    private function getProductEstimate(int $productId, int $storeId): ?Estimate
    {
        if ($productId <= 0 || !$this->config->isShownOn('product', $storeId)) {
            return null;
        }
        try {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->productRepository->getById($productId, false, $storeId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
        if ($product->isVirtual()
            || ($this->config->isHiddenWhenOutOfStock($storeId) && !$product->isSalable())
        ) {
            return null;
        }

        return $this->deliveryDateService->safeEstimate(
            $storeId,
            $this->processingTimeResolver->forProduct($product, $storeId)
        );
    }

    /**
     * Estimate for the current cart
     *
     * @param int $storeId
     * @return Estimate|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCartEstimate(int $storeId): ?Estimate
    {
        if (!$this->config->isShownOn('cart', $storeId) && !$this->config->isShownOn('checkout', $storeId)) {
            return null;
        }
        $quote = $this->checkoutSession->getQuote();
        if (!$quote->getItemsCount()) {
            return null;
        }

        return $this->deliveryDateService->getEstimateForQuote($quote);
    }
}
