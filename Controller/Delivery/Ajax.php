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

namespace Ecommet\SmartDeliveryDate\Controller\Delivery;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Ecommet\SmartDeliveryDate\Helper\Configuration;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Ecommet\SmartDeliveryDate\Api\DeliveryDateServiceInterface;

class Ajax implements ActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Configuration
     */
    protected $helper;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var DeliveryDateServiceInterface
     */
    protected $deliveryDateService;

    /**
     * Ajax constructor.
     *
     * @param JsonFactory $jsonFactory
     * @param RequestInterface $request
     * @param Configuration $helper
     * @param TimezoneInterface $timezone
     * @param DeliveryDateServiceInterface $deliveryDateService
     */
    public function __construct(
        JsonFactory $jsonFactory,
        RequestInterface $request,
        Configuration $helper,
        TimezoneInterface $timezone,
        DeliveryDateServiceInterface $deliveryDateService
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->helper = $helper;
        $this->timezone = $timezone;
        $this->deliveryDateService = $deliveryDateService;
    }

    /**
     * Execute the action to retrieve delivery dates and cutoff time.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $now = $this->timezone->date();
            $cutoffTime = $this->getCutoffTime();
            $deliveryDates = $this->deliveryDateService->getDeliveryDates($now);

            $datesFormatted = array_map(function ($date) {
                return [
                    'iso' => $date->format(\DateTime::ATOM),
                    'label' => $date->format('D d \o\f F'),
                ];
            }, $deliveryDates);

            return $result->setData([
                'success' => true,
                'cutoffTime' => $cutoffTime,
                'timeLimit' => $this->getTimeLimit(),
                'deliveryDates' => $datesFormatted,
            ]);
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => __('An error occurred while processing your request.'),
            ];
        }

        return $result->setData($response);
    }

    /**
     * Check if delivery date feature is enabled.
     *
     * @return bool
     */
    public function getTimeLimit(): string
    {
        return $this->helper->getProcessOrderTodayTimeLimit();
    }

    /**
     * Get ISO-formatted cutoff datetime.
     *
     * @return string
     */
    public function getCutoffTime(): string
    {
        $cutoffTime = $this->getTimeLimit(); // Expected format: HH:mm:ss
        $now = $this->timezone->date(); // Uses store timezone

        $cutoffDateTime = clone $now;
        $cutoffDateTime->setTime(...explode(':', $cutoffTime));

        if ($now > $cutoffDateTime) {
            $cutoffDateTime->modify('+1 day');
        }

        return $cutoffDateTime->format(\DateTime::ATOM);
    }

    /**
     * Get delivery date range adjusted to store timezone.
     *
     * @return array
     */
    public function getDeliveryInformation(): array
    {
        $orderDate = $this->timezone->date();
        $deliveryDates = $this->deliveryDateService->getDeliveryDates($orderDate);

        return array_map(function (\DateTime $date) {
            return $this->timezone->date($date);
        }, $deliveryDates);
    }
}
