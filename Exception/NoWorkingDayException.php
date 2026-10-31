<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Thrown when the configuration leaves no processing or delivery day to estimate with
 */
class NoWorkingDayException extends LocalizedException
{
}
