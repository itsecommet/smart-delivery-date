<?php

/**
 * Smart Delivery Date Module
 *
 * Provides estimated delivery date calculation based on working days,
 * holidays, processing times. Includes
 * admin configuration, frontend display, and integration with checkout.
 *
 * @category   Ecommet
 * @package    Ecommet_SmartDeliveryDate
 * @author     Ecommet Solutions <ecommet.solutions@gmail.com>
 * @copyright  Copyright © Ecommet Solutions. All rights reserved.
 * @license    https://ecommet.in/license Commercial License
 * @link       https://ecommet.in
 */

namespace Ecommet\SmartDeliveryDate\Helper;

use DateTime;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Configuration extends AbstractHelper
{
    public const XML_PATH_SECTION = 'smartdeliverydate/';
    public const XML_PATH_GROUP_GENERAL = 'general/';
    public const XML_PATH_GROUP_PROCESSING = 'order_processing/';
    public const XML_PATH_GROUP_DELIVERY = 'delivery_settings/';
    public const XML_PATH_GROUP_FRONTEND = 'frontend/';

    public const XML_PATH_FIELD_STATUS = 'enabled';

    public const XML_PATH_FIELD_WORKING_DAYS = 'working_days';
    public const XML_PATH_FIELD_DAYS_NEEDED_FOR_PROCESSING = 'days_needed_for_processing_the_order';
    public const XML_PATH_FIELD_PROCESS_ORDER_TODAY_TIME_LIMIT = 'process_order_today_time_limit';
    public const XML_PATH_FIELD_EXCLUDED_PROCESSING_DATES = 'excluded_processing_dates';

    public const XML_PATH_FIELD_DAYS_WITH_DELIVERY = 'delivery_work_days';
    public const XML_PATH_FIELD_DAYS_NEEDED_FOR_DELIVERY = 'days_needed_for_delivery';
    public const XML_PATH_FIELD_ENABLE_OPEN_DELIVERY_DATE = 'enable_open_delivery_date';
    public const XML_PATH_FIELD_OPEN_DELIVERY_RANGE_DAYS = 'open_delivery_range_days';

    // phpcs:ignore Generic.Files.LineLength.TooLong
    public const XML_PATH_USE_EXCLUDED_PROCESSING_AS_DELIVERY = 'use_excluded_processing_dates_as_excluded_delivery_dates';
    public const XML_PATH_FIELD_EXCLUDED_DELIVERY_DATES = 'excluded_delivery_dates';

    public const XML_PATH_FIELD_SHOW_TIME_LIMIT = 'show_time_limit';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Configuration constructor.
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     * @param Context $context
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        SerializerInterface   $serializer,
        Context               $context
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
    }

    /**
     * Retrieve config value
     *
     * @param string $fieldPath
     * @param int|null $storeId
     * @return mixed
     */
    protected function getConfigValue(string $fieldPath, int $storeId)
    {
        return $this->scopeConfig->getValue(
            $fieldPath,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve a boolean system field config value
     *
     * @param string $fieldPath
     * @param bool $defaultValue
     * @return bool
     */
    protected function getBoolConfigValue(string $fieldPath, bool $defaultValue = false): bool
    {
        $fieldValue = $defaultValue;

        try {
            $storeId = $this->storeManager->getStore()->getId();
            $value = $this->getConfigValue($fieldPath, (int)$storeId);

            if ($value !== null) {
                $fieldValue = (bool)$value;
            }
        } catch (NoSuchEntityException $e) {
            $this->_logger->critical($e->getMessage());
        }
        return $fieldValue;
    }

    /**
     * Retrieves a boolean system field config value
     *
     * @param string $fieldPath
     * @param int $defaultValue
     * @return int
     */
    protected function getIntConfigValue(string $fieldPath, int $defaultValue = 0): int
    {
        $intValue = $defaultValue;
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $value = $this->getConfigValue($fieldPath, (int)$storeId);
            if ($value !== null) {
                $intValue = (int)$value;
            }
        } catch (NoSuchEntityException $e) {
            $this->_logger->critical($e->getMessage());
        }
        return $intValue;
    }

    /**
     * Retrieves array from string config value (multiselect,...)
     *
     * @param string $fieldPath
     * @param string $separator
     * @return array
     */
    protected function getArrayFromStringConfigValue(string $fieldPath, string $separator = ','): array
    {
        $data = [];
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $value = $this->getConfigValue($fieldPath, (int)$storeId);
            if ($value !== null) {
                $data = explode($separator, $value);
            }
        } catch (NoSuchEntityException $e) {
            $this->_logger->critical($e->getMessage());
        }
        return $data;
    }

    /**
     * Retrieve excluded dates
     *
     * @param string $fieldPath
     * @return array
     */
    protected function getDatesFromSerializedConfigDates(string $fieldPath): array
    {
        $dates = [];

        try {
            $storeId = $this->storeManager->getStore()->getId();
            $value = $this->getConfigValue($fieldPath, (int)$storeId);
            if ($value !== null) {
                $dates = $this->getDatesFromSerializedDates($value);
            }
        } catch (NoSuchEntityException $e) {
            $this->_logger->critical($e->getMessage());
        }
        return $dates;
    }

    /**
     * Retrieve if the extension is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        $fieldPath = self::XML_PATH_SECTION
            . self::XML_PATH_GROUP_GENERAL
            . self::XML_PATH_FIELD_STATUS;
        return $this->getBoolConfigValue($fieldPath);
    }

    /**
     * Retrieve the list of work days of the company
     *
     * @return array
     */
    public function getWorkingDays(): array
    {
        $fieldPath = self::XML_PATH_SECTION
            . self::XML_PATH_GROUP_PROCESSING
            . self::XML_PATH_FIELD_WORKING_DAYS;
        return $this->getArrayFromStringConfigValue($fieldPath);
    }

    /**
     * Retrieve number of days needed for delivery
     *
     * @return int
     */
    public function getDaysNeededForProcessing(): int
    {
        $fieldPath = self::XML_PATH_SECTION
            . self::XML_PATH_GROUP_PROCESSING
            . self::XML_PATH_FIELD_DAYS_NEEDED_FOR_PROCESSING;
        return $this->getIntConfigValue($fieldPath);
    }

    /**
     * Retrieve process order today time limit value
     *
     * @return string
     */
    public function getProcessOrderTodayTimeLimit(): string
    {
        $timeLimit = '23:59:59';
        $fieldPath = self::XML_PATH_SECTION
            . self::XML_PATH_GROUP_PROCESSING
            . self::XML_PATH_FIELD_PROCESS_ORDER_TODAY_TIME_LIMIT;

        try {
            $storeId = $this->storeManager->getStore()->getId();
            $value = $this->getConfigValue($fieldPath, (int)$storeId);
            if ($value !== null) {
                $timeLimit = str_replace(',', ':', $value);
            }
        } catch (NoSuchEntityException $e) {
            $this->_logger->critical($e->getMessage());
        }

        return $timeLimit;
    }

    /**
     * Retrieve excluded dates
     *
     * @return array
     */
    public function getExcludedProcessingDates(): array
    {
        $fieldPath = self::XML_PATH_SECTION
            . self::XML_PATH_GROUP_PROCESSING
            . self::XML_PATH_FIELD_EXCLUDED_PROCESSING_DATES;
        return $this->getDatesFromSerializedConfigDates($fieldPath);
    }

    /**
     * Days with Delivery
     */
    public function getDeliveryWorkDays(): array
    {
        $fieldPath = self::XML_PATH_SECTION
            . self::XML_PATH_GROUP_DELIVERY
            . self::XML_PATH_FIELD_DAYS_WITH_DELIVERY;
        return $this->getArrayFromStringConfigValue($fieldPath);
    }

    /**
     * Retrieve number of days needed for delivery
     *
     * @return int
     */
    public function getDaysNeededForDelivery(): int
    {
        $fieldPath = self::XML_PATH_SECTION
            . self::XML_PATH_GROUP_DELIVERY
            . self::XML_PATH_FIELD_DAYS_NEEDED_FOR_DELIVERY;
        return $this->getIntConfigValue($fieldPath);
    }

    /**
     * Retrieves if the Open delivery date feature is enable
     *
     * @return bool
     */
    public function isEnabledOpenDeliveryDate(): bool
    {
        $fieldPath = self::XML_PATH_SECTION
            . self::XML_PATH_GROUP_DELIVERY
            . self::XML_PATH_FIELD_ENABLE_OPEN_DELIVERY_DATE;
        return $this->getBoolConfigValue($fieldPath);
    }

    /**
     * Retrieves open delivery range days value
     *
     * @return int
     */
    public function getOpenDeliveryRangeDays(): int
    {
        $fieldPath = self::XML_PATH_SECTION
            . self::XML_PATH_GROUP_DELIVERY
            . self::XML_PATH_FIELD_OPEN_DELIVERY_RANGE_DAYS;
        return $this->getIntConfigValue($fieldPath);
    }

    /**
     * Retrieves use excluded processing dates as excluded delivery dates config value
     *
     * @return bool
     */
    public function useExcludedProcessingDatesAsExcludedDeliveryDates(): bool
    {
        $fieldPath = self::XML_PATH_SECTION
            . self::XML_PATH_GROUP_DELIVERY
            . self::XML_PATH_USE_EXCLUDED_PROCESSING_AS_DELIVERY;

        return $this->getBoolConfigValue($fieldPath);
    }

    /**
     * Retrieves excluded delivery dates configuration
     *
     * @return array
     */
    public function getExcludedDeliveryDates(): array
    {
        $fieldPath = self::XML_PATH_SECTION
            . self::XML_PATH_GROUP_DELIVERY
            . self::XML_PATH_FIELD_EXCLUDED_DELIVERY_DATES;
        return $this->getDatesFromSerializedConfigDates($fieldPath);
    }

    /**
     * Retrieve Show time limit config value
     *
     * @return bool
     */
    public function showTimeLimit(): bool
    {
        $fieldPath = self::XML_PATH_SECTION
            . self::XML_PATH_GROUP_FRONTEND
            . self::XML_PATH_FIELD_SHOW_TIME_LIMIT;
        return $this->getBoolConfigValue($fieldPath);
    }

    /**
     * Transforms serialized date string into Datetime objects
     *
     * @param string|null $datesString
     * @return array
     */
    private function getDatesFromSerializedDates(?string $datesString): array
    {
        if ($datesString === null) {
            return [];
        }

        $decodedValue = (array)$this->serializer->unserialize($datesString);
        $dates = [];

        foreach ($decodedValue as $key => $data) {
            if ($key === '__empty' || empty($data['date'])) {
                continue;
            }

            $date = \DateTime::createFromFormat('Y-m-d', $data['date']);
            if ($date !== false) {
                $dates[] = $date;
            }
        }

        return $dates;
    }
}
