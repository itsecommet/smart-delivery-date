<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

/**
 * Typed access to the module's store configuration
 */
class Config
{
    private const PREFIX = 'smartdeliverydate/';

    public const XML_ENABLED = 'general/enabled';
    public const XML_PROCESSING_DAYS_OF_WEEK = 'order_processing/working_days';
    public const XML_PROCESSING_TIME = 'order_processing/days_needed_for_processing_the_order';
    public const XML_CUTOFF_TIME = 'order_processing/process_order_today_time_limit';
    public const XML_PROCESSING_EXCLUDED_DATES = 'order_processing/excluded_processing_dates';
    public const XML_DELIVERY_DAYS_OF_WEEK = 'delivery_settings/delivery_work_days';
    public const XML_TRANSIT_TIME = 'delivery_settings/days_needed_for_delivery';
    public const XML_WINDOW_ENABLED = 'delivery_settings/enable_open_delivery_date';
    public const XML_WINDOW_LENGTH = 'delivery_settings/open_delivery_range_days';
    public const XML_DELIVERY_SAME_EXCLUDED =
    'delivery_settings/use_excluded_processing_dates_as_excluded_delivery_dates';
    public const XML_DELIVERY_EXCLUDED_DATES = 'delivery_settings/excluded_delivery_dates';
    public const XML_SELECTION_ENABLED = 'customer_selection/enabled';
    public const XML_SELECTION_REQUIRED = 'customer_selection/required';
    public const XML_SELECTION_MAX_DAYS = 'customer_selection/max_days_ahead';
    public const XML_SELECTION_LABEL = 'customer_selection/label';
    public const XML_SELECTION_NOTE = 'customer_selection/note';
    public const XML_SHOW_ON_PRODUCT = 'frontend/show_on_product';
    public const XML_SHOW_ON_CART = 'frontend/show_on_cart';
    public const XML_SHOW_ON_CHECKOUT = 'frontend/show_on_checkout';
    public const XML_HIDE_OUT_OF_STOCK = 'frontend/hide_when_out_of_stock';
    public const XML_SHOW_COUNTDOWN = 'frontend/show_time_limit';
    public const XML_DATE_FORMAT = 'frontend/date_format';
    public const XML_MESSAGE_COUNTDOWN = 'frontend/message_countdown';
    public const XML_MESSAGE = 'frontend/message';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $json
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Json $json
    ) {
    }

    /**
     * Whether the module is enabled for the store
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::XML_ENABLED, $storeId);
    }

    /**
     * Weekdays (0 = Sunday … 6 = Saturday) on which orders are processed
     *
     * @param int|null $storeId
     * @return int[]
     */
    public function getProcessingDaysOfWeek(?int $storeId = null): array
    {
        return $this->weekdays(self::XML_PROCESSING_DAYS_OF_WEEK, $storeId);
    }

    /**
     * Default number of processing days before an order is handed to the carrier
     *
     * @param int|null $storeId
     * @return int
     */
    public function getProcessingTime(?int $storeId = null): int
    {
        return max(0, (int)$this->value(self::XML_PROCESSING_TIME, $storeId));
    }

    /**
     * Same-day cut-off as [hour, minute, second]
     *
     * @param int|null $storeId
     * @return int[]
     */
    public function getCutoffTime(?int $storeId = null): array
    {
        $parts = array_map('intval', explode(',', (string)$this->value(self::XML_CUTOFF_TIME, $storeId)));
        $parts = array_pad(array_slice($parts, 0, 3), 3, 0);
        if ($parts === [0, 0, 0]) {
            return [23, 59, 59];
        }

        return [min(23, max(0, $parts[0])), min(59, max(0, $parts[1])), min(59, max(0, $parts[2]))];
    }

    /**
     * Dates on which orders are not processed
     *
     * @param int|null $storeId
     * @return array<int, array{date: string, recurring: bool}>
     */
    public function getProcessingExcludedDates(?int $storeId = null): array
    {
        return $this->excludedDates(self::XML_PROCESSING_EXCLUDED_DATES, $storeId);
    }

    /**
     * Weekdays (0 = Sunday … 6 = Saturday) on which the carrier delivers
     *
     * @param int|null $storeId
     * @return int[]
     */
    public function getDeliveryDaysOfWeek(?int $storeId = null): array
    {
        return $this->weekdays(self::XML_DELIVERY_DAYS_OF_WEEK, $storeId);
    }

    /**
     * Number of carrier days between hand-over and delivery
     *
     * @param int|null $storeId
     * @return int
     */
    public function getTransitTime(?int $storeId = null): int
    {
        return max(0, (int)$this->value(self::XML_TRANSIT_TIME, $storeId));
    }

    /**
     * Length of the delivery window in carrier days, 0 when a single date is shown
     *
     * @param int|null $storeId
     * @return int
     */
    public function getDeliveryWindowLength(?int $storeId = null): int
    {
        if (!$this->flag(self::XML_WINDOW_ENABLED, $storeId)) {
            return 0;
        }

        return max(0, (int)$this->value(self::XML_WINDOW_LENGTH, $storeId));
    }

    /**
     * Dates on which the carrier does not deliver
     *
     * @param int|null $storeId
     * @return array<int, array{date: string, recurring: bool}>
     */
    public function getDeliveryExcludedDates(?int $storeId = null): array
    {
        if ($this->flag(self::XML_DELIVERY_SAME_EXCLUDED, $storeId)) {
            return $this->getProcessingExcludedDates($storeId);
        }

        return $this->excludedDates(self::XML_DELIVERY_EXCLUDED_DATES, $storeId);
    }

    /**
     * Whether customers can choose a delivery date at checkout
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isSelectionEnabled(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->flag(self::XML_SELECTION_ENABLED, $storeId);
    }

    /**
     * Whether the customer must choose a delivery date
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isSelectionRequired(?int $storeId = null): bool
    {
        return $this->isSelectionEnabled($storeId) && $this->flag(self::XML_SELECTION_REQUIRED, $storeId);
    }

    /**
     * Number of calendar days after the earliest date that can be chosen
     *
     * @param int|null $storeId
     * @return int
     */
    public function getSelectionMaxDaysAhead(?int $storeId = null): int
    {
        return min(366, max(1, (int)$this->value(self::XML_SELECTION_MAX_DAYS, $storeId)));
    }

    /**
     * Custom label of the date field, empty for the default
     *
     * @param int|null $storeId
     * @return string
     */
    public function getSelectionLabel(?int $storeId = null): string
    {
        return trim((string)$this->value(self::XML_SELECTION_LABEL, $storeId));
    }

    /**
     * Note shown below the date field
     *
     * @param int|null $storeId
     * @return string
     */
    public function getSelectionNote(?int $storeId = null): string
    {
        return trim((string)$this->value(self::XML_SELECTION_NOTE, $storeId));
    }

    /**
     * Whether the estimate is shown on the given page type (product, cart or checkout)
     *
     * @param string $page
     * @param int|null $storeId
     * @return bool
     */
    public function isShownOn(string $page, ?int $storeId = null): bool
    {
        $paths = [
            'product' => self::XML_SHOW_ON_PRODUCT,
            'cart' => self::XML_SHOW_ON_CART,
            'checkout' => self::XML_SHOW_ON_CHECKOUT,
        ];

        return isset($paths[$page]) && $this->isEnabled($storeId) && $this->flag($paths[$page], $storeId);
    }

    /**
     * Whether the estimate is hidden for products that cannot be bought
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isHiddenWhenOutOfStock(?int $storeId = null): bool
    {
        return $this->flag(self::XML_HIDE_OUT_OF_STOCK, $storeId);
    }

    /**
     * Whether the cut-off countdown is shown
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isCountdownShown(?int $storeId = null): bool
    {
        return $this->flag(self::XML_SHOW_COUNTDOWN, $storeId);
    }

    /**
     * ICU date pattern for storefront dates
     *
     * @param int|null $storeId
     * @return string
     */
    public function getDateFormat(?int $storeId = null): string
    {
        return trim((string)$this->value(self::XML_DATE_FORMAT, $storeId)) ?: 'EEEE, MMMM d';
    }

    /**
     * Custom message with countdown, empty for the default
     *
     * @param int|null $storeId
     * @return string
     */
    public function getCountdownMessage(?int $storeId = null): string
    {
        return trim((string)$this->value(self::XML_MESSAGE_COUNTDOWN, $storeId));
    }

    /**
     * Custom message without countdown, empty for the default
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMessage(?int $storeId = null): string
    {
        return trim((string)$this->value(self::XML_MESSAGE, $storeId));
    }

    /**
     * Read a raw value
     *
     * @param string $path
     * @param int|null $storeId
     * @return mixed
     */
    private function value(string $path, ?int $storeId): mixed
    {
        return $this->scopeConfig->getValue(self::PREFIX . $path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Read a Yes/No value
     *
     * @param string $path
     * @param int|null $storeId
     * @return bool
     */
    private function flag(string $path, ?int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(self::PREFIX . $path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Read a weekday multiselect
     *
     * @param string $path
     * @param int|null $storeId
     * @return int[]
     */
    private function weekdays(string $path, ?int $storeId): array
    {
        $value = (string)$this->value($path, $storeId);
        if ($value === '') {
            return [];
        }
        $days = array_filter(array_map('trim', explode(',', $value)), 'is_numeric');

        return array_values(array_unique(array_map(static fn($day): int => (int)$day % 7, $days)));
    }

    /**
     * Read a serialized excluded-dates table
     *
     * @param string $path
     * @param int|null $storeId
     * @return array<int, array{date: string, recurring: bool}>
     */
    private function excludedDates(string $path, ?int $storeId): array
    {
        $value = (string)$this->value($path, $storeId);
        if ($value === '') {
            return [];
        }
        try {
            $rows = $this->json->unserialize($value);
        } catch (\InvalidArgumentException $e) {
            return [];
        }

        $dates = [];
        foreach ((array)$rows as $row) {
            $date = is_array($row) ? (string)($row['date'] ?? '') : '';
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $dates[] = ['date' => $date, 'recurring' => !empty($row['recurring'])];
            }
        }

        return $dates;
    }
}
