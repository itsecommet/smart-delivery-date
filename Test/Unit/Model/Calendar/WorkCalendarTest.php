<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Test\Unit\Model\Calendar;

use DateTimeImmutable;
use Ecommet\SmartDeliveryDate\Exception\NoWorkingDayException;
use Ecommet\SmartDeliveryDate\Model\Calendar\WorkCalendar;
use PHPUnit\Framework\TestCase;

class WorkCalendarTest extends TestCase
{
    private const WEEKDAYS = [1, 2, 3, 4, 5];

    public function testWeekendAndExcludedDatesAreNotWorkingDays(): void
    {
        $calendar = new WorkCalendar(self::WEEKDAYS, [['date' => '2026-10-21', 'recurring' => false]]);

        $this->assertTrue($calendar->isWorkingDay(new DateTimeImmutable('2026-10-20')));
        $this->assertFalse($calendar->isWorkingDay(new DateTimeImmutable('2026-10-21')), 'excluded date');
        $this->assertFalse($calendar->isWorkingDay(new DateTimeImmutable('2026-10-24')), 'Saturday');
        $this->assertTrue($calendar->isWorkingDay(new DateTimeImmutable('2027-10-21')), 'not recurring');
    }

    public function testRecurringDateRepeatsEveryYear(): void
    {
        $calendar = new WorkCalendar(self::WEEKDAYS, [['date' => '2020-12-25', 'recurring' => true]]);

        $this->assertFalse($calendar->isWorkingDay(new DateTimeImmutable('2026-12-25')));
        $this->assertFalse($calendar->isWorkingDay(new DateTimeImmutable('2028-12-25')));
        $this->assertTrue($calendar->isWorkingDay(new DateTimeImmutable('2026-12-24')));
    }

    public function testFirstWorkingDayFromSkipsWeekendAndHoliday(): void
    {
        $calendar = new WorkCalendar(self::WEEKDAYS, [['date' => '2026-10-26', 'recurring' => false]]);

        // Saturday → Sunday → Monday (excluded) → Tuesday
        $this->assertSame(
            '2026-10-27',
            $calendar->firstWorkingDayFrom(new DateTimeImmutable('2026-10-24'))->format('Y-m-d')
        );
        $this->assertSame(
            '2026-10-23',
            $calendar->firstWorkingDayFrom(new DateTimeImmutable('2026-10-23'))->format('Y-m-d'),
            'a working day is returned unchanged'
        );
    }

    public function testAddWorkingDaysCountsOnlyWorkingDays(): void
    {
        $calendar = new WorkCalendar(self::WEEKDAYS, [['date' => '2026-10-26', 'recurring' => false]]);

        // Friday + 2 working days: Mon excluded, so Tue, Wed
        $this->assertSame(
            '2026-10-28',
            $calendar->addWorkingDays(new DateTimeImmutable('2026-10-23'), 2)->format('Y-m-d')
        );
        $this->assertSame(
            '2026-10-23',
            $calendar->addWorkingDays(new DateTimeImmutable('2026-10-23'), 0)->format('Y-m-d')
        );
    }

    public function testCalendarWithoutWorkingDaysThrowsInsteadOfLoopingForever(): void
    {
        $this->expectException(NoWorkingDayException::class);
        (new WorkCalendar([]))->firstWorkingDayFrom(new DateTimeImmutable('2026-10-23'));
    }
}
