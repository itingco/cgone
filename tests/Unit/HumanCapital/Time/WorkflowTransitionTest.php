<?php
namespace Tests\Unit\HumanCapital\Time;
use App\Services\HumanCapital\Time\WorkflowTransition;
use DomainException;
use PHPUnit\Framework\TestCase;
final class WorkflowTransitionTest extends TestCase
{
    public function test_invalid_transition_is_rejected():void
    {
        $this->expectException(DomainException::class);
        (new WorkflowTransition())->assert('APPROVED',['DRAFT'],'SUBMITTED');
    }
}
