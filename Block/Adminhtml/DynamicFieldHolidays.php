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

namespace Ecommet\SmartDeliveryDate\Block\Adminhtml;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\DataObject;

class DynamicFieldHolidays extends AbstractFieldArray
{
    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn('date', ['label' => __('Date'), 'class' => 'js-date-excluded-datepicker']);
        $this->addColumn('content', ['label' => __('Info')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Date');
        parent::_prepareToRender();
    }

    /**
     * Prepare existing row data object - Convert backend date format "2018-01-12" to front format "12/01/2018"
     *
     * @param DataObject $row
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $key = 'date';

        if (!$row->hasData($key)) {
            return;
        }

        $dateValue = $row->getData($key);
        $sourceDate = \DateTime::createFromFormat('Y-m-d', $dateValue);

        if (!$sourceDate) {
            return; // Invalid date format, skip processing
        }

        $renderedDate = $sourceDate->format('d/m/Y');
        $row->setData($key, $renderedDate);

        $columnValues = $row->getData('column_values') ?? [];
        $columnValues[$this->_getCellInputElementId($row->getData('_id'), $key)] = $renderedDate;
        $row->setData('column_values', $columnValues);
    }

    /**
     * Get the grid and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = parent::_getElementHtml($element);

        $dayNames = json_encode([
            (string)__('Sunday'),
            (string)__('Monday'),
            (string)__('Tuesday'),
            (string)__('Wednesday'),
            (string)__('Thursday'),
            (string)__('Friday'),
            (string)__('Saturday')
        ]);

        $monthNames = json_encode([
            (string)__('January'),
            (string)__('February'),
            (string)__('March'),
            (string)__('April'),
            (string)__('May'),
            (string)__('June'),
            (string)__('July'),
            (string)__('August'),
            (string)__('September'),
            (string)__('October'),
            (string)__('November'),
            (string)__('December')
        ]);

        $monthNamesShort = json_encode([
            (string)__('Jan'),
            (string)__('Feb'),
            (string)__('Mar'),
            (string)__('Apr'),
            (string)__('May'),
            (string)__('Jun'),
            (string)__('Jul'),
            (string)__('Aug'),
            (string)__('Sep'),
            (string)__('Oct'),
            (string)__('Nov'),
            (string)__('Dec')
        ]);

        $dayNamesShort = json_encode([
            (string)__('Sun'),
            (string)__('Mon'),
            (string)__('Tue'),
            (string)__('Wed'),
            (string)__('Thu'),
            (string)__('Fri'),
            (string)__('Sat')
        ]);

        $dayNamesMin = json_encode([
            (string)__('Su'),
            (string)__('Mo'),
            (string)__('Tu'),
            (string)__('We'),
            (string)__('Th'),
            (string)__('Fr'),
            (string)__('Sa')
        ]);

        $script = <<<HTML
            <script type="text/javascript">
            require(["jquery", "jquery/ui"], function (jq) {
                jq(function(){
                    function bindDatePicker() {
                        setTimeout(function() {
                            jq(".js-date-excluded-datepicker").datepicker({
                                dateFormat: "dd/mm/yy",
                                firstDay: 1,
                                dayNames: $dayNames,
                                monthNames: $monthNames,
                                monthNamesShort: $monthNamesShort,
                                dayNamesShort: $dayNamesShort,
                                dayNamesMin: $dayNamesMin
                            });
                        }, 50);
                    }
                    bindDatePicker();
                    jq("button.action-add").on("click", function() {
                        bindDatePicker();
                    });
                });
            });
            </script>
        HTML;

        $html .= $script;

        return $html;
    }
}
