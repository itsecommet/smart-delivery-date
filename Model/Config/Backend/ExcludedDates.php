<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Model\Config\Backend;

use DateTimeImmutable;
use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Framework\Exception\LocalizedException;

/**
 * Normalises excluded dates to Y-m-d rows sorted by date, and rejects invalid dates
 */
class ExcludedDates extends ArraySerialized
{
    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        $rows = [];
        foreach ((array)$this->getValue() as $key => $row) {
            if ($key === '__empty' || !is_array($row) || trim((string)($row['date'] ?? '')) === '') {
                continue;
            }
            $date = $this->parseDate(trim((string)$row['date']));
            if ($date === null) {
                throw new LocalizedException(
                    __('"%1" is not a valid date. Use the date picker or the YYYY-MM-DD format.', $row['date'])
                );
            }
            $rows[$key] = [
                'date' => $date->format('Y-m-d'),
                'content' => trim((string)($row['content'] ?? '')),
                'recurring' => !empty($row['recurring']) ? '1' : '0',
            ];
        }
        uasort($rows, static fn(array $a, array $b): int => strcmp($a['date'], $b['date']));
        $this->setValue($rows);

        return parent::beforeSave();
    }

    /**
     * Parse Y-m-d (date input) or d/m/Y (version 1.x)
     *
     * @param string $value
     * @return DateTimeImmutable|null
     */
    private function parseDate(string $value): ?DateTimeImmutable
    {
        foreach (['!Y-m-d', '!d/m/Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            if ($date !== false && $date->format(ltrim($format, '!')) === $value) {
                return $date;
            }
        }

        return null;
    }
}
