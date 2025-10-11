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

namespace Ecommet\SmartDeliveryDate\Block\Adminhtml\Config\Backend;

use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;

class ArraySerializedHolidays extends ArraySerialized
{
    /**
     * Process the value before saving it to the database.
     *
     * This method processes the array of holidays, ensuring that each date is in the correct format
     * and that empty entries are ignored.
     *
     * @return $this
     */
    public function beforeSave()
    {
        $processedValues = [];

        foreach ((array)$this->getValue() as $key => $data) {
            if ($key === '__empty' || empty($data['date'])) {
                continue;
            }

            $date = \DateTime::createFromFormat('d/m/Y', $data['date']);
            if (!$date) {
                continue; // Invalid date format, skip
            }

            $processedValues[$key] = [
                'date' => $date->format('Y-m-d'),
                'content' => $data['content'] ?? '',
            ];
        }

        $this->setValue($processedValues);
        return parent::beforeSave();
    }
}
