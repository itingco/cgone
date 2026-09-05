<?php

namespace App\Services\Reports;

use App\Models\Reports\{ReportDefinition, ReportExecutionLog};
use App\Models\User;
use App\Services\Reports\Datasources\ReportDatasourceRegistry;
use App\Reports\Sql\StoredSqlReport;
use App\Services\Reports\Visual\{VisualReportCompiler,VisualReportResultBuilder,VisualReportRuntimeParameterService,VisualReportValidator};
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ReportExecutionService
{
    public function __construct(
        private readonly ReportAccessService $access,
        private readonly StandardReportRegistry $registry,
        private readonly ReportDatasourceRegistry $datasources,
        private readonly VisualReportValidator $visualValidator,
        private readonly VisualReportCompiler $visualCompiler,
        private readonly VisualReportResultBuilder $visualResults,
        private readonly VisualReportRuntimeParameterService $visualRuntime,
        private readonly StoredSqlReport $storedSql,
        private readonly MenuAuthorizationService $menuAuth,
        private readonly ReportRowLimitPolicy $rowLimits,
    ) {
    }

    public function run(
        User $user,
        ReportDefinition $definition,
        array $parameters,
        ?string $exportType = null,
        ?string $ipAddress = null,
    ): ReportResult {
        if (! $this->access->allows($user, $definition, 'view')) {
            throw new AuthorizationException('You do not have access to this report.');
        }

        if ($definition->report_type === ReportDefinition::TYPE_SQL
            && ! $this->menuAuth->allows($user, 'reports.sql', 'execute')) {
            throw new AuthorizationException('Advanced SQL execution is restricted to authorized IT/Administrator users.');
        }

        $started = now();
        $timer = hrtime(true);
        $loggedParameters = $definition->report_type === ReportDefinition::TYPE_SQL
            ? $this->storedSql->maskedParameters((array)$definition->definition_json, $parameters)
            : $parameters;

        $log = ReportExecutionLog::create([
            'report_definition_id' => $definition->id,
            'user_id' => $user->id,
            'database_name' => (string) DB::connection()->getDatabaseName(),
            'parameters_json' => $loggedParameters,
            'filters_json' => $definition->report_type===ReportDefinition::TYPE_VISUAL
                ? data_get($definition->definition_json,'filters',[])
                : $loggedParameters,
            'started_at' => $started,
            'export_type' => $exportType,
            'status' => 'RUNNING',
            'ip_address' => $ipAddress,
        ]);

        try {
            if ($definition->report_type === ReportDefinition::TYPE_STANDARD) {
                $standardCode = (string) data_get($definition->definition_json, 'standard_code', $definition->code);
                $originalScreenLimit = (int) config('reports.screen_row_limit',5000);
                config(['reports.screen_row_limit'=>$this->rowLimits->standard($exportType)]);
                try {
                    $result = $this->registry->resolve($standardCode)->run($parameters);
                } finally {
                    config(['reports.screen_row_limit'=>$originalScreenLimit]);
                }
            } elseif ($definition->report_type === ReportDefinition::TYPE_VISUAL) {
                $source = $this->datasources->resolve((string)data_get($definition->definition_json,'datasource'));
                $visualDefinition=$this->visualRuntime->apply((array)$definition->definition_json,$parameters);
                $visual = $this->visualValidator->validate($source,$visualDefinition);
                $log->update(['filters_json'=>$visual['filters']]);
                $rows = $this->visualCompiler->compile(
                    $source,
                    $visual,
                    preview:false,
                    rowLimit:$this->rowLimits->visual(false,$exportType),
                )->get();
                $result = $this->visualResults->build($definition->name,$visual,$rows);
            } elseif ($definition->report_type === ReportDefinition::TYPE_SQL) {
                $result = $this->storedSql->run(
                    $definition->name,
                    (array)$definition->definition_json,
                    $parameters,
                    $exportType ? (int)config('reports.sql_export_row_limit',25000) : (int)config('reports.screen_row_limit',5000),
                );
            } else {
                throw new \DomainException('Unsupported report type.');
            }

            $log->update([
                'finished_at' => now(),
                'duration_ms' => (int) round((hrtime(true) - $timer) / 1_000_000),
                'row_count' => $result->rowCount(),
                'status' => 'SUCCESS',
            ]);

            return $result;
        } catch (Throwable $e) {
            $log->update([
                'finished_at' => now(),
                'duration_ms' => (int) round((hrtime(true) - $timer) / 1_000_000),
                'row_count' => 0,
                'status' => 'FAILED',
                'error_message' => mb_substr($e->getMessage(), 0, 4000),
            ]);
            throw $e;
        }
    }
}
