<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Model;

use DateTimeInterface;
use IntlDateFormatter;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Formats delivery dates in the store locale with the configured pattern
 */
class DateFormatter
{
    /**
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly Config $config,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Human-readable date, e.g. "Friday, October 23"
     *
     * @param DateTimeInterface $date
     * @param int|null $storeId
     * @return string
     */
    public function format(DateTimeInterface $date, ?int $storeId = null): string
    {
        $locale = (string)$this->scopeConfig->getValue(
            DirectoryHelper::XML_PATH_DEFAULT_LOCALE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $formatter = new IntlDateFormatter(
            $locale ?: 'en_US',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $date->getTimezone(),
            IntlDateFormatter::GREGORIAN,
            $this->config->getDateFormat($storeId)
        );
        $label = $formatter->format($date);

        return $label === false ? $date->format('Y-m-d') : $label;
    }
}
