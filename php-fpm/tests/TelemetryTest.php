<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TelemetryTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../web/api/telemetry.php';
    }

    public function testAcceptsWellFormedBasicPayload(): void
    {
        $result = telemetry_validate([
            'user_id'      => '3f9a1c2b4e7d4a8fa1b2c3d4e5f6a7b8',
            'event_type'   => 'app_start',
            'level'        => 'basic',
            'app_version'  => '2.4.2',
            'distribution' => 'dev',
            'os_version'   => 'Microsoft Windows NT 10.0.22631.0',
            'language'     => 'pl',
        ]);

        $this->assertNotNull($result);
        $this->assertSame('basic', $result['level']);
    }

    public function testDefaultsLevelToBasicWhenOmitted(): void
    {
        $result = telemetry_validate([
            'user_id'      => '3f9a1c2b4e7d4a8fa1b2c3d4e5f6a7b8',
            'event_type'   => 'app_start',
            'app_version'  => '2.4.2',
            'distribution' => 'dev',
            'os_version'   => 'Windows',
            'language'     => 'en',
        ]);

        $this->assertSame('basic', $result['level']);
    }

    public function testRejectsUnknownLevel(): void
    {
        $result = telemetry_validate([
            'user_id'      => '3f9a1c2b4e7d4a8fa1b2c3d4e5f6a7b8',
            'event_type'   => 'app_start',
            'level'        => 'premium',
            'app_version'  => '2.4.2',
            'distribution' => 'dev',
            'os_version'   => 'Windows',
            'language'     => 'en',
        ]);

        $this->assertNull($result);
    }

    public function testRejectsMalformedUserId(): void
    {
        $result = telemetry_validate([
            'user_id'      => 'not-a-guid',
            'event_type'   => 'app_start',
            'app_version'  => '2.4.2',
            'distribution' => 'dev',
            'os_version'   => 'Windows',
            'language'     => 'en',
        ]);

        $this->assertNull($result);
    }

    public function testFeatureValidationClampsOutOfRangeValues(): void
    {
        $feature = telemetry_validate_feature([
            'grid_rows'                 => 999,   // clamped to 20
            'gamepad_trigger_threshold' => -5,    // clamped to 1
            'total_key_presses'         => 'not a number', // -> default 0
        ]);

        $this->assertNotNull($feature);
        $this->assertSame(20, $feature['grid_rows']);
        $this->assertSame(1, $feature['gamepad_trigger_threshold']);
        $this->assertSame(0, $feature['total_key_presses']);
    }

    public function testFeatureValidationRejectsNonArray(): void
    {
        $this->assertNull(telemetry_validate_feature('not an object'));
        $this->assertNull(telemetry_validate_feature(null));
    }

    private function basicPayload(array $overrides = []): array
    {
        return $overrides + [
            'user_id'      => '3f9a1c2b4e7d4a8fa1b2c3d4e5f6a7b8',
            'event_type'   => 'app_start',
            'level'        => 'basic',
            'app_version'  => '2.4.2',
            'distribution' => 'dev',
            'os_version'   => 'Windows',
            'language'     => 'en',
        ];
    }

    public function testAggregateCountersDefaultToZeroWhenAbsent(): void
    {
        // Older clients that predate this change send no aggregate fields; the
        // event must still validate and the counters default to 0.
        $result = telemetry_validate($this->basicPayload());

        $this->assertNotNull($result);
        $this->assertSame(0, $result['total_input_events']);
        $this->assertSame(0, $result['total_uptime_seconds']);
    }

    public function testAggregateCountersClampNegativeToZero(): void
    {
        $result = telemetry_validate($this->basicPayload([
            'total_input_events'   => -1,
            'total_uptime_seconds' => -999,
        ]));

        $this->assertNotNull($result);
        $this->assertSame(0, $result['total_input_events']);
        $this->assertSame(0, $result['total_uptime_seconds']);
    }

    public function testAggregateCountersNonNumericBecomeZero(): void
    {
        $result = telemetry_validate($this->basicPayload([
            'total_input_events'   => 'lots',
            'total_uptime_seconds' => ['nope'],
        ]));

        $this->assertNotNull($result);
        $this->assertSame(0, $result['total_input_events']);
        $this->assertSame(0, $result['total_uptime_seconds']);
    }

    public function testAggregateCountersPassThroughValidValues(): void
    {
        $result = telemetry_validate($this->basicPayload([
            'total_input_events'   => 184213,
            'total_uptime_seconds' => 402117,
        ]));

        $this->assertNotNull($result);
        $this->assertSame(184213, $result['total_input_events']);
        $this->assertSame(402117, $result['total_uptime_seconds']);
    }

    public function testAggregateCountersAlsoPresentAtFullTier(): void
    {
        // The two top-level counters are sent at full tier too (redundant with
        // the feature object on purpose), so validation must surface them.
        $result = telemetry_validate($this->basicPayload([
            'level'                => 'full',
            'total_input_events'   => 10,
            'total_uptime_seconds' => 20,
        ]));

        $this->assertNotNull($result);
        $this->assertSame('full', $result['level']);
        $this->assertSame(10, $result['total_input_events']);
        $this->assertSame(20, $result['total_uptime_seconds']);
    }
}
