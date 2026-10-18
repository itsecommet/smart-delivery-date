<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\AbstractBlock;

/**
 * Native date input for a dynamic-rows cell. Stores Y-m-d and needs no inline script (CSP safe).
 *
 * @method string getInputName()
 * @method string getInputId()
 * @method string getColumnName()
 */
class DateInput extends AbstractBlock
{
    /**
     * @inheritdoc
     */
    protected function _toHtml(): string
    {
        // <%- … %> is filled in by the dynamic-rows JS template.
        return sprintf(
            '<input type="date" id="%s" name="%s" value="<%%- %s %%>" class="admin__control-text required-entry" '
                . 'required />',
            $this->_escaper->escapeHtmlAttr((string)$this->getInputId()),
            $this->_escaper->escapeHtmlAttr((string)$this->getInputName()),
            $this->_escaper->escapeHtmlAttr((string)$this->getColumnName())
        );
    }
}
