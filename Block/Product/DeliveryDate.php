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

namespace Ecommet\SmartDeliveryDate\Block\Product;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Ecommet\SmartDeliveryDate\Helper\Configuration;
use Magento\Framework\View\Element\Template;
use Ecommet\SmartDeliveryDate\Api\DeliveryDateServiceInterface;

class DeliveryDate extends Template
{
    /**
     * @var Configuration
     */
    private Configuration $helperConfiguration;

    /**
     * @var DeliveryDateServiceInterface
     */
    private DeliveryDateServiceInterface $deliveryDateService;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezone;

    /**
     * Constructor
     *
     * @param DeliveryDateServiceInterface $deliveryDateService
     * @param TimezoneInterface $timezone
     * @param Configuration $configuration
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        DeliveryDateServiceInterface $deliveryDateService,
        TimezoneInterface $timezone,
        Configuration     $configuration,
        Template\Context  $context,
        array             $data = []
    ) {
        $this->deliveryDateService = $deliveryDateService;
        $this->timezone = $timezone;
        $this->helperConfiguration = $configuration;
        parent::__construct($context, $data);
    }

    /**
     * Check if delivery date feature is enabled
     *
     * @return bool
     */
    public function isEnableDeliveryDate(): bool
    {
        return $this->helperConfiguration->isEnabled();
    }

    /**
     * Check if show time limit is enabled
     *
     * @return bool
     */
    public function showTimeLimit(): bool
    {
        return $this->helperConfiguration->showTimeLimit();
    }

    /**
     * Get the time limit for processing orders today
     *
     * @return string
     */
    public function getTimeLimit(): string
    {
        return $this->helperConfiguration->getProcessOrderTodayTimeLimit();
    }

    /**
     * Get the cutoff time for processing orders today
     *
     * @return string
     */
    public function getCutoffTime(): string
    {
        $cutoffTime = $this->getTimeLimit();
        $timezone = new \DateTimeZone('Asia/Kolkata'); // or use store timezone
        $now = new \DateTime('now', $timezone);

        // Combine today's date with cutoff time
        $cutoffDateTime = new \DateTime($now->format('Y-m-d') . ' ' . $cutoffTime, $timezone);

        // If cutoff has already passed today → move to tomorrow
        if ($now > $cutoffDateTime) {
            $cutoffDateTime->modify('+1 day');
        }

        return $cutoffDateTime->format(\DateTime::ATOM);
    }

    /**
     * Check if open delivery date is enabled
     *
     * @return bool
     */
    public function isOpenDeliveryDate(): bool
    {
        return $this->helperConfiguration->isEnabledOpenDeliveryDate();
    }

    /**
     * Get the range of open delivery days
     *
     * @return int
     */
    public function getDeliveryInformation(): array
    {
        $estimatedOrderDate = $this->timezone->date();
        $deliveryDates = $this->deliveryDateService->getDeliveryDates($estimatedOrderDate);

        $dates = [];
        foreach ($deliveryDates as $deliveryDate) {
            $dates[] = $this->timezone->date(new \DateTime($deliveryDate->format('Y-m-d H:i:s')));
        }

        return $dates;
    }
}
