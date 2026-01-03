<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReservationServiceTest extends TestCase
{
    private ReservationService $service;

    protected function setUp(): void
    {
        $this->service = new ReservationService();
    }

    #[Test]
    public function 境界が接する場合は重複しない(): void
    {
        $existing = [
            new TimeRange(
                new DateTimeImmutable('2024-01-01 10:00:00'),
                new DateTimeImmutable('2024-01-01 11:00:00')
            ),
        ];
        $request = new ReservationRequest(
            new TimeRange(
                new DateTimeImmutable('2024-01-01 11:00:00'),
                new DateTimeImmutable('2024-01-01 12:00:00')
            )
        );

        $result = $this->service->check($existing, $request);

        $this->assertTrue($result->canReserve);
        $this->assertNull($result->nextAvailableAt);
    }

    #[Test]
    public function 時間が重複する場合は予約不可(): void
    {
        $existing = [
            new TimeRange(
                new DateTimeImmutable('2024-01-01 10:00:00'),
                new DateTimeImmutable('2024-01-01 11:00:00')
            ),
        ];
        $request = new ReservationRequest(
            new TimeRange(
                new DateTimeImmutable('2024-01-01 10:30:00'),
                new DateTimeImmutable('2024-01-01 11:30:00')
            )
        );

        $result = $this->service->check($existing, $request);

        $this->assertFalse($result->canReserve);
        $this->assertEquals(
            new DateTimeImmutable('2024-01-01 11:00:00'),
            $result->nextAvailableAt
        );
    }

    #[Test]
    public function 連続する重複予約をスキップして次の空き時刻を返す(): void
    {
        $existing = [
            new TimeRange(
                new DateTimeImmutable('2024-01-01 10:00:00'),
                new DateTimeImmutable('2024-01-01 10:30:00')
            ),
            new TimeRange(
                new DateTimeImmutable('2024-01-01 10:30:00'),
                new DateTimeImmutable('2024-01-01 11:00:00')
            ),
            new TimeRange(
                new DateTimeImmutable('2024-01-01 11:15:00'),
                new DateTimeImmutable('2024-01-01 11:45:00')
            ),
        ];
        $request = new ReservationRequest(
            new TimeRange(
                new DateTimeImmutable('2024-01-01 10:15:00'),
                new DateTimeImmutable('2024-01-01 10:45:00')
            )
        );

        $result = $this->service->check($existing, $request);

        $this->assertFalse($result->canReserve);
        $this->assertEquals(
            new DateTimeImmutable('2024-01-01 11:00:00'),
            $result->nextAvailableAt
        );
    }

    #[Test]
    public function 重複しない場合はnextAvailableAtがnull(): void
    {
        $existing = [
            new TimeRange(
                new DateTimeImmutable('2024-01-01 10:00:00'),
                new DateTimeImmutable('2024-01-01 11:00:00')
            ),
        ];
        $request = new ReservationRequest(
            new TimeRange(
                new DateTimeImmutable('2024-01-01 12:00:00'),
                new DateTimeImmutable('2024-01-01 13:00:00')
            )
        );

        $result = $this->service->check($existing, $request);

        $this->assertTrue($result->canReserve);
        $this->assertNull($result->nextAvailableAt);
    }

    #[Test]
    public function 既存予約が空の場合は予約可能(): void
    {
        $request = new ReservationRequest(
            new TimeRange(
                new DateTimeImmutable('2024-01-01 10:00:00'),
                new DateTimeImmutable('2024-01-01 11:00:00')
            )
        );

        $result = $this->service->check([], $request);

        $this->assertTrue($result->canReserve);
        $this->assertNull($result->nextAvailableAt);
    }

    #[Test]
    public function 開始時刻と終了時刻が同じ場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);

        new TimeRange(
            new DateTimeImmutable('2024-01-01 10:00:00'),
            new DateTimeImmutable('2024-01-01 10:00:00')
        );
    }

    #[Test]
    public function 開始時刻が終了時刻より後の場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);

        new TimeRange(
            new DateTimeImmutable('2024-01-01 11:00:00'),
            new DateTimeImmutable('2024-01-01 10:00:00')
        );
    }

    #[Test]
    public function 既存予約がstartAt昇順でない場合は例外が発生する(): void
    {
        $existing = [
            new TimeRange(
                new DateTimeImmutable('2024-01-01 11:00:00'),
                new DateTimeImmutable('2024-01-01 12:00:00')
            ),
            new TimeRange(
                new DateTimeImmutable('2024-01-01 10:00:00'),
                new DateTimeImmutable('2024-01-01 11:00:00')
            ),
        ];
        $request = new ReservationRequest(
            new TimeRange(
                new DateTimeImmutable('2024-01-01 09:00:00'),
                new DateTimeImmutable('2024-01-01 10:00:00')
            )
        );

        $this->expectException(DomainException::class);

        $this->service->check($existing, $request);
    }

    #[Test]
    public function リクエストが既存予約の前にある場合は予約可能(): void
    {
        $existing = [
            new TimeRange(
                new DateTimeImmutable('2024-01-01 11:00:00'),
                new DateTimeImmutable('2024-01-01 12:00:00')
            ),
        ];
        $request = new ReservationRequest(
            new TimeRange(
                new DateTimeImmutable('2024-01-01 09:00:00'),
                new DateTimeImmutable('2024-01-01 10:00:00')
            )
        );

        $result = $this->service->check($existing, $request);

        $this->assertTrue($result->canReserve);
        $this->assertNull($result->nextAvailableAt);
    }
}
