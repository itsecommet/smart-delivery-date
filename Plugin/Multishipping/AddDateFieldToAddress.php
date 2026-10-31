<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Plugin\Multishipping;

use Magento\Framework\DataObject;
use Magento\Multishipping\Block\Checkout\Shipping;

/**
 * Renders the delivery date field in every address box of the multi-address shipping step.
 * Core leaves getItemsBoxTextAfter() empty as an extension point; Luma and Hyvä templates both call it.
 */
class AddDateFieldToAddress
{
    public const BLOCK_NAME = 'ecommet.delivery.date.multishipping';

    /**
     * Append the field
     *
     * @param Shipping $subject
     * @param string|null $result
     * @param DataObject $address
     * @return string
     */
    public function afterGetItemsBoxTextAfter(Shipping $subject, $result, DataObject $address): string
    {
        $block = $subject->getLayout()->getBlock(self::BLOCK_NAME);
        if (!$block) {
            return (string)$result;
        }

        return $result . $block->setData('address', $address)->toHtml();
    }
}
