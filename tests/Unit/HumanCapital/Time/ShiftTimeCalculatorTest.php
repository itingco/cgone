<?php
namespace Tests\Unit\HumanCapital\Time;
use App\Services\HumanCapital\Time\ShiftTimeCalculator;
use PHPUnit\Framework\TestCase;
final class ShiftTimeCalculatorTest extends TestCase
{
    public function test_normal_shift_calculates_late_work_and_overtime_minutes():void
    {
        $r=(new ShiftTimeCalculator())->calculate('2026-09-01','08:00','17:00',false,60,5,true,'2026-09-01 08:10:00','2026-09-01 17:30:00');
        $this->assertSame(5,$r['late_minutes']);$this->assertSame(500,$r['working_minutes']);$this->assertSame(30,$r['overtime_candidate_minutes']);
    }
    public function test_cross_day_shift_rolls_scheduled_out_to_next_date():void
    {
        $r=(new ShiftTimeCalculator())->calculate('2026-09-01','22:00','06:00',true,60,0,true,'2026-09-01 22:00:00','2026-09-02 06:30:00');
        $this->assertSame('2026-09-02 06:00:00',$r['scheduled_out']);$this->assertSame(450,$r['working_minutes']);$this->assertSame(30,$r['overtime_candidate_minutes']);
    }
}
