<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Admin table of excluded dates: date, description, repeat every year
 */
class ExcludedDates extends AbstractFieldArray
{
    /**
     * @var DateInput|null
     */
    private ?DateInput $dateRenderer = null;

    /**
     * @var YesNoSelect|null
     */
    private ?YesNoSelect $recurringRenderer = null;

    /**
     * @inheritdoc
     */
    protected function _prepareToRender()
    {
        $this->addColumn('date', [
            'label' => __('Date'),
            'renderer' => $this->getDateRenderer(),
        ]);
        $this->addColumn('content', [
            'label' => __('Description'),
            'class' => 'admin__control-text',
        ]);
        $this->addColumn('recurring', [
            'label' => __('Every Year'),
            'renderer' => $this->getRecurringRenderer(),
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = (string)__('Add Date');
    }

    /**
     * Pre-select the saved "Every year" value
     *
     * @param DataObject $row
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $hash = $this->getRecurringRenderer()->calcOptionHash($row->getData('recurring') ? '1' : '0');
        $row->setData('option_extra_attrs', ['option_' . $hash => 'selected="selected"']);
    }

    /**
     * Date column renderer
     *
     * @return DateInput
     * @throws LocalizedException
     */
    private function getDateRenderer(): DateInput
    {
        if ($this->dateRenderer === null) {
            /** @var DateInput $renderer */
            $renderer = $this->getLayout()->createBlock(
                DateInput::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->dateRenderer = $renderer;
        }

        return $this->dateRenderer;
    }

    /**
     * "Every year" column renderer
     *
     * @return YesNoSelect
     * @throws LocalizedException
     */
    private function getRecurringRenderer(): YesNoSelect
    {
        if ($this->recurringRenderer === null) {
            /** @var YesNoSelect $renderer */
            $renderer = $this->getLayout()->createBlock(
                YesNoSelect::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->recurringRenderer = $renderer;
        }

        return $this->recurringRenderer;
    }
}
