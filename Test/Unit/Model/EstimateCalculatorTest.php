<?php

/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Ecommet\SmartDeliveryDate\Test\Unit\Model;

use DateTimeImmutable;
use DateTimeZone;
use Ecommet\SmartDeliveryDate\Model\Config;
use Ecommet\SmartDeliveryDate\Model\EstimateCalculator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Dates in the comments: 2026-10-19 is a Monday, 2026-10-23 a Friday.
 */
class EstimateCalculatorTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $values;

    /**
     * @var string
     */
    private string $timezone = 'America/New_York';

    protected function setUp(): void
    {
        $this->values = [
            'general/enabled' => '1',
            'order_processing/working_days' => '1,2,3,4,5',
            'order_processing/days_needed_for_processing_the_order' => '1',
            'order_processing/process_order_today_time_limit' => '14,00,00',
            'order_processing/excluded_processing_dates' => '',
            'delivery_settings/delivery_work_days' => '1,2,3,4,5',
            'delivery_settings/days_needed_for_delivery' => '2',
            'delivery_settings/enable_open_delivery_date' => '0',
            'delivery_settings/open_delivery_range_days' => '2',
            'delivery_settings/use_excluded_processing_dates_as_excluded_delivery_dates' => '1',
            'delivery_settings/excluded_delivery_dates' => '',
            'customer_selection/enabled' => '1',
            'customer_selection/max_days_ahead' => '14',
        ];
    }

    public function testOrderBeforeCutoffStartsToday(): void
    {
        // Mon 10:00 → processed Tue → ships Tue → delivered Thu
        $estimate = $this->calculator()->estimate(1, null, $this->at('2026-10-19 10:00'));

        $this->assertSame('2026-10-20', $estimate->getProcessedDate()->format('Y-m-d'));
        $this->assertSame('2026-10-20', $estimate->getShippingDate()->format('Y-m-d'));
        $this->assertSame('2026-10-22', $estimate->getEarliestDelivery()->format('Y-m-d'));
        $this->assertNull($estimate->getLatestDelivery());
        $this->assertSame(4 * 3600, $estimate->getSecondsToCutoff());
    }

    public function testOrderAfterCutoffStartsNextWorkingDay(): void
    {
        // Mon 15:00 → starts Tue → processed Wed → delivered Fri
        $estimate = $this->calculator()->estimate(1, null, $this->at('2026-10-19 15:00'));

        $this->assertSame('2026-10-23', $estimate->getEarliestDelivery()->format('Y-m-d'));
        $this->assertSame('2026-10-20 14:00', $estimate->getNextCutoff()->format('Y-m-d H:i'));
    }

    public function testCutoffUsesStoreTimezoneNotServerTimezone(): void
    {
        // 17:30 UTC is 13:30 in New York: still before the 14:00 cut-off.
        $now = new DateTimeImmutable('2026-10-19 17:30', new DateTimeZone('UTC'));
        $estimate = $this->calculator()->estimate(1, null, $now);

        $this->assertSame('2026-10-22', $estimate->getEarliestDelivery()->format('Y-m-d'));
        $this->assertSame('America/New_York', $estimate->getEarliestDelivery()->getTimezone()->getName());
    }

    public function testWeekendOrderStartsMonday(): void
    {
        // Sat → starts Mon → processed Tue → delivered Thu
        $estimate = $this->calculator()->estimate(1, null, $this->at('2026-10-24 09:00'));

        $this->assertSame('2026-10-29', $estimate->getEarliestDelivery()->format('Y-m-d'));
        $this->assertSame('2026-10-26 14:00', $estimate->getNextCutoff()->format('Y-m-d H:i'));
    }

    public function testExcludedDatesAreSkippedForProcessingAndDelivery(): void
    {
        $this->values['order_processing/excluded_processing_dates'] = json_encode([
            '_1' => ['date' => '2026-10-20', 'content' => 'Closed', 'recurring' => '0'],
            '_2' => ['date' => '2026-10-22', 'content' => 'Holiday', 'recurring' => '0'],
        ]);

        // Mon 10:00 → processed Wed (Tue excluded) → ships Wed → +2 carrier days: Thu excluded → Fri, Mon
        $estimate = $this->calculator()->estimate(1, null, $this->at('2026-10-19 10:00'));

        $this->assertSame('2026-10-21', $estimate->getProcessedDate()->format('Y-m-d'));
        $this->assertSame('2026-10-26', $estimate->getEarliestDelivery()->format('Y-m-d'));
    }

    /**
     * The worked example in docs/USER_GUIDE.md, section 5
     */
    #[DataProvider('userGuideExampleProvider')]
    public function testUserGuideWorkedExample(string $placed, string $expected): void
    {
        $this->values['order_processing/excluded_processing_dates'] = json_encode([
            '_1' => ['date' => '2026-10-20', 'recurring' => '0'],
        ]);

        $estimate = $this->calculator()->estimate(1, null, $this->at($placed));

        $this->assertSame($expected, $estimate->getEarliestDelivery()->format('Y-m-d'));
    }

    public static function userGuideExampleProvider(): array
    {
        return [
            'before cut-off' => ['2026-10-19 10:00', '2026-10-23'],
            'after cut-off' => ['2026-10-19 15:00', '2026-10-26'],
            'weekend' => ['2026-10-24 09:00', '2026-10-29'],
        ];
    }

    public function testSeparateCarrierExcludedDates(): void
    {
        $this->values['delivery_settings/use_excluded_processing_dates_as_excluded_delivery_dates'] = '0';
        $this->values['delivery_settings/excluded_delivery_dates'] = json_encode([
            '_1' => ['date' => '2026-10-22', 'recurring' => '0'],
        ]);

        $estimate = $this->calculator()->estimate(1, null, $this->at('2026-10-19 10:00'));

        $this->assertSame('2026-10-23', $estimate->getEarliestDelivery()->format('Y-m-d'));
    }

    public function testProductProcessingTimeOverridesDefault(): void
    {
        // 0 processing days: Mon 10:00 → ships Mon → delivered Wed
        $estimate = $this->calculator()->estimate(1, 0, $this->at('2026-10-19 10:00'));

        $this->assertSame('2026-10-21', $estimate->getEarliestDelivery()->format('Y-m-d'));
    }

    public function testDeliveryWindow(): void
    {
        $this->values['delivery_settings/enable_open_delivery_date'] = '1';

        // Thu + 2 carrier days → Mon
        $estimate = $this->calculator()->estimate(1, null, $this->at('2026-10-19 10:00'));

        $this->assertSame('2026-10-22', $estimate->getEarliestDelivery()->format('Y-m-d'));
        $this->assertSame('2026-10-26', $estimate->getLatestDelivery()->format('Y-m-d'));
    }

    public function testSelectableDatesSkipExcludedAndNonDeliveryDays(): void
    {
        $this->values['order_processing/excluded_processing_dates'] = json_encode([
            '_1' => ['date' => '2026-10-27', 'recurring' => '0'],
        ]);
        $calculator = $this->calculator();
        $estimate = $calculator->estimate(1, null, $this->at('2026-10-19 10:00'));

        $dates = array_map(
            static fn(DateTimeImmutable $date): string => $date->format('Y-m-d'),
            $calculator->getSelectableDates($estimate, 1)
        );

        // Earliest Thu 22nd, +14 days → up to Thu 5 Nov; no weekends, no 27th
        $this->assertSame('2026-10-22', $dates[0]);
        $this->assertSame('2026-11-05', end($dates));
        $this->assertNotContains('2026-10-24', $dates);
        $this->assertNotContains('2026-10-25', $dates);
        $this->assertNotContains('2026-10-27', $dates);
        $this->assertNotContains('2026-10-21', $dates, 'before the earliest date');
        $this->assertCount(10, $dates);
    }

    /**
     * @param string $time
     * @return DateTimeImmutable
     */
    private function at(string $time): DateTimeImmutable
    {
        return new DateTimeImmutable($time, new DateTimeZone($this->timezone));
    }

    /**
     * @return EstimateCalculator
     */
    private function calculator(): EstimateCalculator
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            fn(string $path) => $this->values[substr($path, strlen('smartdeliverydate/'))] ?? null
        );
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            fn(string $path) => !empty($this->values[substr($path, strlen('smartdeliverydate/'))])
        );
        $timezone = $this->createStub(TimezoneInterface::class);
        $timezone->method('getConfigTimezone')->willReturn($this->timezone);

        return new EstimateCalculator(new Config($scopeConfig, new Json()), $timezone);
    }
}
