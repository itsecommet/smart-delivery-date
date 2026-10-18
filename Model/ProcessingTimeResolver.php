<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * Processing time for a product or cart, honouring the per-product attribute
 */
class ProcessingTimeResolver
{
    public const ATTRIBUTE_CODE = 'ecommet_processing_days';

    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Processing days for a single product
     *
     * @param ProductInterface $product
     * @param int|null $storeId
     * @return int
     */
    public function forProduct(ProductInterface $product, ?int $storeId = null): int
    {
        return $this->getOverride($product) ?? $this->config->getProcessingTime($storeId);
    }

    /**
     * Processing days for a cart: the slowest item decides
     *
     * @param CartInterface $quote
     * @return int
     */
    public function forQuote(CartInterface $quote): int
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        return $this->forItems($quote->getAllVisibleItems(), (int)$quote->getStoreId());
    }

    /**
     * Processing days for the items shipped to one address (multi-address checkout)
     *
     * @param Address $address
     * @return int
     */
    public function forAddress(Address $address): int
    {
        return $this->forItems($address->getAllVisibleItems(), (int)$address->getQuote()->getStoreId());
    }

    /**
     * Processing days for a list of items: the slowest item decides
     *
     * @param AbstractItem[] $items
     * @param int $storeId
     * @return int
     */
    private function forItems(array $items, int $storeId): int
    {
        $default = $this->config->getProcessingTime($storeId);
        $days = [];
        foreach ($items as $item) {
            $override = null;
            foreach ($item->getChildren() as $child) {
                $override = $override ?? ($child->getProduct() ? $this->getOverride($child->getProduct()) : null);
            }
            if ($override === null && $item->getProduct()) {
                $override = $this->getOverride($item->getProduct());
            }
            $days[] = $override ?? $default;
        }

        return $days ? max($days) : $default;
    }

    /**
     * Per-product value, null when not set
     *
     * @param ProductInterface $product
     * @return int|null
     */
    private function getOverride(ProductInterface $product): ?int
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $value = $product->getData(self::ATTRIBUTE_CODE);
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return max(0, (int)$value);
    }
}
