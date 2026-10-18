<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Model\Calendar;

use DateTimeImmutable;
use Ecommet\SmartDeliveryDate\Exception\NoWorkingDayException;

/**
 * Working days of one party (the merchant or the carrier): allowed weekdays minus excluded dates
 */
class WorkCalendar
{
    /**
     * Upper bound for any date search, so a calendar without working days cannot loop forever
     */
    private const MAX_SEARCH_DAYS = 3660;

    /**
     * @var array<int, true>
     */
    private array $weekdays;

    /**
     * @var array<string, true> Y-m-d
     */
    private array $dates = [];

    /**
     * @var array<string, true> m-d
     */
    private array $recurringDates = [];

    /**
     * @param int[] $weekdays 0 = Sunday … 6 = Saturday
     * @param array[] $excludedDates Rows of ["date" => "Y-m-d", "recurring" => bool]
     */
    public function __construct(array $weekdays, array $excludedDates = [])
    {
        $this->weekdays = array_fill_keys(array_map('intval', $weekdays), true);
        foreach ($excludedDates as $excluded) {
            if (!empty($excluded['recurring'])) {
                $this->recurringDates[substr($excluded['date'], 5)] = true;
            } else {
                $this->dates[$excluded['date']] = true;
            }
        }
    }

    /**
     * Whether work happens on the given day
     *
     * @param DateTimeImmutable $date
     * @return bool
     */
    public function isWorkingDay(DateTimeImmutable $date): bool
    {
        return isset($this->weekdays[(int)$date->format('w')])
            && !isset($this->dates[$date->format('Y-m-d')])
            && !isset($this->recurringDates[$date->format('m-d')]);
    }

    /**
     * The given day if it is a working day, otherwise the next working day
     *
     * @param DateTimeImmutable $date
     * @return DateTimeImmutable
     * @throws NoWorkingDayException
     */
    public function firstWorkingDayFrom(DateTimeImmutable $date): DateTimeImmutable
    {
        for ($i = 0; $i < self::MAX_SEARCH_DAYS; $i++) {
            if ($this->isWorkingDay($date)) {
                return $date;
            }
            $date = $date->modify('+1 day');
        }

        throw new NoWorkingDayException(__('No working day is configured within the next ten years.'));
    }

    /**
     * The working day that comes $count working days after the given day
     *
     * @param DateTimeImmutable $date
     * @param int $count
     * @return DateTimeImmutable
     * @throws NoWorkingDayException
     */
    public function addWorkingDays(DateTimeImmutable $date, int $count): DateTimeImmutable
    {
        for ($i = 0; $count > 0; $i++) {
            if ($i >= self::MAX_SEARCH_DAYS) {
                throw new NoWorkingDayException(__('No working day is configured within the next ten years.'));
            }
            $date = $date->modify('+1 day');
            if ($this->isWorkingDay($date)) {
                $count--;
            }
        }

        return $date;
    }
}
