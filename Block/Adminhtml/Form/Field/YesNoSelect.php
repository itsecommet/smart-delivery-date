<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

/**
 * No/Yes select for a dynamic-rows cell
 */
class YesNoSelect extends Select
{
    /**
     * Name of the input, set by the dynamic-rows field
     *
     * @param string $value
     * @return $this
     */
    public function setInputName(string $value): self
    {
        return $this->setName($value);
    }

    /**
     * ID of the input, set by the dynamic-rows field
     *
     * @param string $value
     * @return $this
     */
    public function setInputId(string $value): self
    {
        return $this->setId($value);
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions([
                ['value' => '0', 'label' => __('No')],
                ['value' => '1', 'label' => __('Yes')],
            ]);
        }
        $this->setClass('admin__control-select');

        return parent::_toHtml();
    }
}
