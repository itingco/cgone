<?php

namespace App\Services\Reports\Export;

use App\Models\Reports\ReportDefinition;
use App\Models\User;
use App\Services\Reports\ReportResult;
use RuntimeException;

final class ReportPdfExporter
{
    public function content(
        ReportDefinition $definition,
        ReportResult $result,
        array $parameters,
        User $user,
        string $databaseName,
    ): string {
        if(!class_exists(\Dompdf\Dompdf::class)){
            throw new RuntimeException('PDF export requires dompdf/dompdf. Run: composer require dompdf/dompdf:^3.1');
        }

        $orientation=count($result->columns)>7?'landscape':'portrait';
        $html=view('reports.export.pdf',compact(
            'definition','result','parameters','user','databaseName','orientation'
        ))->render();

        $options=new \Dompdf\Options();
        $options->set('defaultFont','DejaVu Sans');
        $options->set('isRemoteEnabled',false);
        $options->set('isHtml5ParserEnabled',true);

        $pdf=new \Dompdf\Dompdf($options);
        $pdf->loadHtml($html,'UTF-8');
        $pdf->setPaper('A4',$orientation);
        $pdf->render();
        return $pdf->output();
    }
}
