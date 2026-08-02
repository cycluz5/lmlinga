<?php

namespace App\Http\Controllers\EnvironmentalHealth;

use App\Http\Controllers\Controller;
use App\Support\DemoHouseholdWaterSupply;
use App\Support\EnvironmentalHealthDashboard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnvironmentalHealthDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $filters = EnvironmentalHealthDashboard::normalizeFilters($request->query());
        $allRows = EnvironmentalHealthDashboard::rows();
        $filteredRows = EnvironmentalHealthDashboard::rows($filters);
        $statistics = EnvironmentalHealthDashboard::statistics($filteredRows);

        return view('pages.environmental-health.index', [
            'active' => 'environmental-health',
            'pageTitle' => 'Environmental Health',
            'pageSubtitle' => 'Track household water supply, sanitation and waste management status.',
            'rows' => $allRows,
            'filteredCount' => count($filteredRows),
            'statistics' => $statistics,
            'filters' => $filters,
            'zones' => EnvironmentalHealthDashboard::zonesFromRows($allRows),
            'streets' => EnvironmentalHealthDashboard::streetsFromRows($allRows),
            'waterLevels' => [
                DemoHouseholdWaterSupply::WATER_LEVEL_I => 'Level I',
                DemoHouseholdWaterSupply::WATER_LEVEL_II => 'Level II',
                DemoHouseholdWaterSupply::WATER_LEVEL_III => 'Level III',
                DemoHouseholdWaterSupply::WATER_LEVEL_OTHERS => 'Others',
            ],
            'sanitationOptions' => [
                'with_toilet' => 'With Toilet',
                'without_toilet' => 'Without Toilet',
            ],
            'totalUnfiltered' => count($allRows),
        ]);
    }

    public function export(Request $request): StreamedResponse|Response|View
    {
        $filters = EnvironmentalHealthDashboard::normalizeFilters($request->query());
        $format = strtolower(trim((string) $request->query('format', 'csv')));
        $rows = EnvironmentalHealthDashboard::rows($filters);
        $statistics = EnvironmentalHealthDashboard::statistics($rows);
        $filenameBase = 'environmental-health-'.now()->format('Ymd-His');

        return match ($format) {
            'excel' => $this->exportExcel($rows, $filenameBase),
            'pdf' => view('pages.environmental-health.export-pdf', [
                'rows' => $rows,
                'statistics' => $statistics,
                'filters' => $filters,
                'generatedAt' => now(),
            ]),
            default => $this->exportCsv($rows, $filenameBase),
        };
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function exportCsv(array $rows, string $filenameBase): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filenameBase.'.csv"',
        ];

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $this->exportColumnHeaders());

            foreach ($rows as $row) {
                fputcsv($handle, $this->exportRowValues($row));
            }

            fclose($handle);
        }, $filenameBase.'.csv', $headers);
    }

    /**
     * Excel-friendly HTML workbook (opens in Microsoft Excel without extra packages).
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function exportExcel(array $rows, string $filenameBase): Response
    {
        $headers = $this->exportColumnHeaders();
        $html = '<html><head><meta charset="UTF-8"></head><body><table border="1">';
        $html .= '<thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>'.e($header).'</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($this->exportRowValues($row) as $value) {
                $html .= '<td>'.e((string) $value).'</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></body></html>';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filenameBase.'.xls"',
        ]);
    }

    /**
     * @return list<string>
     */
    private function exportColumnHeaders(): array
    {
        return [
            'Household Number',
            'Household Head',
            'Zone',
            'Street',
            'Water Supply Level',
            'Sanitation Status',
            'Validation Status',
            'Overall Status',
            'Record Status',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function exportRowValues(array $row): array
    {
        return [
            (string) ($row['household_no'] ?? ''),
            (string) ($row['house_head'] ?? ''),
            (string) ($row['zone'] ?? ''),
            (string) ($row['street'] ?? ''),
            (string) ($row['water_supply_label'] ?? ''),
            (string) ($row['toilet_status_label'] ?? ''),
            (string) ($row['validation_label'] ?? ''),
            (string) ($row['overall_label'] ?? ''),
            (string) ($row['record_status_label'] ?? ''),
        ];
    }
}
