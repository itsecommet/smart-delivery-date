<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\ViewModel;

use Ecommet\SmartDeliveryDate\Model\Config;
use Ecommet\SmartDeliveryDate\Model\EstimatePresenter;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Storefront estimate widget settings for product, cart and multi-address checkout pages
 */
class Estimate implements ArgumentInterface
{
    /**
     * @param Config $config
     * @param EstimatePresenter $presenter
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param Registry $registry
     * @param Json $json
     */
    public function __construct(
        private readonly Config $config,
        private readonly EstimatePresenter $presenter,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $urlBuilder,
        private readonly Registry $registry,
        private readonly Json $json
    ) {
    }

    /**
     * Whether the widget is shown on the page type (product, cart or checkout)
     *
     * @param string $page
     * @return bool
     */
    public function isVisible(string $page): bool
    {
        if (!$this->config->isShownOn($page, $this->getStoreId())) {
            return false;
        }

        return $page !== 'product' || $this->getProduct() !== null;
    }

    /**
     * Widget settings as JSON
     *
     * @param string $page
     * @return string
     */
    public function getConfigJson(string $page): string
    {
        $params = $page === 'product'
            ? ['product_id' => (int)$this->getProduct()?->getId()]
            : ['cart' => 1];

        return $this->json->serialize([
            'url' => $this->urlBuilder->getUrl('smartdelivery/estimate/index', ['_query' => $params]),
            'phrases' => $this->presenter->getPhrases($this->getStoreId()),
        ]);
    }

    /**
     * Product of the current product page
     *
     * @return Product|null
     */
    private function getProduct(): ?Product
    {
        // The registry is deprecated but still the only way core exposes the current product to blocks.
        $product = $this->registry->registry('current_product');

        return $product instanceof Product ? $product : null;
    }

    /**
     * Current store ID
     *
     * @return int
     */
    private function getStoreId(): int
    {
        return (int)$this->storeManager->getStore()->getId();
    }
}
