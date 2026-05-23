<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HealthzTest extends TestCase
{
    public function testPayloadHasOkStatus(): void
    {
        require_once __DIR__ . '/../web/api/healthz.php';

        $payload = healthz_payload();

        $this->assertSame('ok', $payload['status']);
    }

    public function testPayloadIdentifiesService(): void
    {
        require_once __DIR__ . '/../web/api/healthz.php';

        $payload = healthz_payload();

        $this->assertSame('php-fpm', $payload['service']);
    }

    public function testPayloadIsJsonSerializable(): void
    {
        require_once __DIR__ . '/../web/api/healthz.php';

        $json = json_encode(healthz_payload());

        $this->assertNotFalse($json);
        $this->assertJson($json);
    }
}
