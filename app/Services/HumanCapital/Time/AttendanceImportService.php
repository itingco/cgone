<?php
namespace App\Services\HumanCapital\Time;

use App\Models\HumanCapital\Employee;
use App\Models\HumanCapital\Time\AttendanceRecord;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AttendanceImportService
{
    public function __construct(private readonly ScheduleResolver $schedules, private readonly ShiftTimeCalculator $calculator) {}

    public function importCsv(string $path, ?int $userId = null): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) throw new RuntimeException('Unable to open attendance import file.');
        $header = fgetcsv($handle);
        $expected = ['employee_code','work_date','check_in','check_out','source_reference'];
        if (! $header || array_map('trim', $header) !== $expected) {
            fclose($handle);
            throw new RuntimeException('CSV header must be: '.implode(',', $expected));
        }
        $processed = 0; $errors = [];
        DB::transaction(function () use ($handle, $userId, &$processed, &$errors): void {
            $line = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if (count($row) < 5) { $errors[] = "Line {$line}: incomplete columns."; continue; }
                [$employeeCode,$workDate,$checkIn,$checkOut,$sourceReference] = array_map('trim', $row);
                $employee = Employee::query()->where('employee_code', $employeeCode)->first();
                if (! $employee) { $errors[] = "Line {$line}: employee {$employeeCode} not found."; continue; }
                try {
                    $this->upsert((int)$employee->id, $workDate, $checkIn ?: null, $checkOut ?: null, 'IMPORT', $sourceReference, $userId);
                    $processed++;
                } catch (\Throwable $e) { $errors[] = "Line {$line}: {$e->getMessage()}"; }
            }
        });
        fclose($handle);
        return ['processed' => $processed, 'errors' => $errors];
    }

    public function upsert(int $employeeId, string $workDate, ?string $checkIn, ?string $checkOut, string $source = 'MANUAL', string $sourceReference = '', ?int $userId = null, ?string $notes = null): AttendanceRecord
    {
        $source = strtoupper($source);
        if (! in_array($source, ['MANUAL','IMPORT','MACHINE','API'], true)) throw new RuntimeException('Invalid attendance source.');
        $resolved = $this->schedules->forEmployeeDate($employeeId, $workDate);
        $shift = $resolved?->shift;
        $metrics = $this->calculator->calculate(
            $workDate,
            $shift?->start_time,
            $shift?->end_time,
            (bool)($shift?->cross_day ?? false),
            (int)($shift?->break_minutes ?? 0),
            (int)($shift?->grace_late_minutes ?? 0),
            (bool)($shift?->overtime_eligible ?? false),
            $checkIn,
            $checkOut,
        );
        $status = $resolved?->isOff ? 'OFF' : (($checkIn || $checkOut) ? (($checkIn && $checkOut) ? 'PRESENT' : 'INCOMPLETE') : 'ABSENT');
        return AttendanceRecord::updateOrCreate(
            ['employee_id'=>$employeeId,'work_date'=>$workDate,'source'=>$source,'source_reference'=>$sourceReference],
            [
                'shift_id'=>$shift?->id,
                'scheduled_in'=>$metrics['scheduled_in'], 'scheduled_out'=>$metrics['scheduled_out'],
                'scheduled_break_minutes'=>(int)($shift?->break_minutes ?? 0),
                'scheduled_grace_late_minutes'=>(int)($shift?->grace_late_minutes ?? 0),
                'scheduled_overtime_eligible'=>(bool)($shift?->overtime_eligible ?? false),
                'raw_check_in'=>$checkIn, 'raw_check_out'=>$checkOut,
                'late_minutes'=>$metrics['late_minutes'], 'early_leave_minutes'=>$metrics['early_leave_minutes'],
                'working_minutes'=>$metrics['working_minutes'], 'overtime_candidate_minutes'=>$metrics['overtime_candidate_minutes'],
                'attendance_status'=>$status, 'notes'=>$notes, 'created_by'=>$userId,
            ]
        );
    }
}
