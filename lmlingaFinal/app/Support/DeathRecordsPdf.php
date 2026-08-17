<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Http\Response;

/**
 * Minimal PDF 1.4 writer for the Health Records → Death listing export.
 * Used because the project has no existing PDF library and Composer cannot
 * install a second one in this environment.
 */
final class DeathRecordsPdf
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $filterLabels
     */
    public static function response(array $rows, array $filterLabels, DateTimeInterface $generatedAt): Response
    {
        $filename = 'death-records-'.$generatedAt->format('Ymd-His').'.pdf';

        return response(self::render($rows, $filterLabels, $generatedAt), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $filterLabels
     */
    public static function render(array $rows, array $filterLabels, DateTimeInterface $generatedAt): string
    {
        $pageWidth = 842.0;
        $pageHeight = 595.0;
        $margin = 36.0;
        $rowHeight = 22.0;
        $tableTop = 500.0;
        $columns = [
            ['key' => 'full_name', 'label' => 'Full Name', 'width' => 190.0],
            ['key' => 'age', 'label' => 'Age', 'width' => 50.0],
            ['key' => 'cause_of_death', 'label' => 'Cause of Death', 'width' => 200.0],
            ['key' => 'date_of_death', 'label' => 'Date of Death', 'width' => 140.0],
            ['key' => 'status_label', 'label' => 'Status', 'width' => 150.0],
        ];

        $meta = 'Generated '.$generatedAt->format('M j, Y g:i A').' · '.count($rows).' record(s)';
        if ($filterLabels !== []) {
            $meta .= ' · Filters: '.implode(' · ', $filterLabels);
        }

        $chunks = array_chunk($rows === [] ? [[]] : $rows, 18);
        $pageStreams = [];

        foreach ($chunks as $pageIndex => $chunk) {
            $commands = [];
            $commands[] = self::text(18, $margin, $pageHeight - 42, 'Death Records');
            $commands[] = self::text(10, $margin, $pageHeight - 58, $meta);
            $commands[] = self::tableHeader($columns, $margin, $tableTop);

            if ($rows === []) {
                $commands[] = self::text(11, $margin, $tableTop - 28, 'No death records match the selected filters.');
            } else {
                $y = $tableTop - $rowHeight;
                foreach ($chunk as $row) {
                    $commands[] = self::tableRow($columns, $row, $margin, $y, $rowHeight);
                    $y -= $rowHeight;
                }
            }

            $pageStreams[] = implode("\n", $commands);
        }

        return self::assemble($pageStreams, $pageWidth, $pageHeight);
    }

    /**
     * @param  list<array{key: string, label: string, width: float}>  $columns
     */
    private static function tableHeader(array $columns, float $x, float $y): string
    {
        $parts = [sprintf('0.49 0.83 0.60 rg %.2f %.2f %.2f 18 re f 0 g', $x, $y - 4, self::tableWidth($columns))];
        $cursor = $x + 4;
        foreach ($columns as $column) {
            $parts[] = self::text(9, $cursor, $y + 2, $column['label']);
            $cursor += $column['width'];
        }

        return implode("\n", $parts);
    }

    /**
     * @param  list<array{key: string, label: string, width: float}>  $columns
     * @param  array<string, mixed>  $row
     */
    private static function tableRow(array $columns, array $row, float $x, float $y, float $height): string
    {
        $width = self::tableWidth($columns);
        $parts = [sprintf('0.82 0.82 0.82 RG %.2f %.2f %.2f %.2f re S 0 G', $x, $y - 4, $width, $height)];
        $cursor = $x + 4;
        foreach ($columns as $column) {
            $value = trim((string) ($row[$column['key']] ?? ''));
            if ($column['key'] === 'full_name') {
                $meta = implode(' · ', array_filter([
                    (string) ($row['sex'] ?? ''),
                    (string) ($row['member_id'] ?? ''),
                ]));
                $parts[] = self::text(9, $cursor, $y + 8, $value);
                if ($meta !== '') {
                    $parts[] = self::text(8, $cursor, $y - 2, $meta);
                }
            } else {
                $parts[] = self::text(9, $cursor, $y + 4, $value);
            }
            $cursor += $column['width'];
        }

        return implode("\n", $parts);
    }

    /**
     * @param  list<array{width: float}>  $columns
     */
    private static function tableWidth(array $columns): float
    {
        $width = 0.0;
        foreach ($columns as $column) {
            $width += $column['width'];
        }

        return $width;
    }

    private static function text(int $size, float $x, float $y, string $value): string
    {
        return sprintf(
            'BT /F1 %d Tf %.2f %.2f Td (%s) Tj ET',
            $size,
            $x,
            $y,
            self::escape($value)
        );
    }

    private static function escape(string $value): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
        $safe = $converted !== false ? $converted : preg_replace('/[^\x20-\x7E]/', '?', $value) ?? $value;
        $safe = preg_replace('/[\r\n\t]+/', ' ', $safe) ?? $safe;

        return strtr($safe, [
            '\\' => '\\\\',
            '(' => '\\(',
            ')' => '\\)',
        ]);
    }

    /**
     * @param  list<string>  $pageStreams
     */
    private static function assemble(array $pageStreams, float $pageWidth, float $pageHeight): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $pageIds = [];
        $nextId = 4;

        foreach ($pageStreams as $stream) {
            $contentId = $nextId++;
            $pageId = $nextId++;
            $pageIds[] = $pageId;
            $objects[$contentId] = '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream";
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Contents %d 0 R /Resources << /Font << /F1 3 0 R >> >> >>',
                $pageWidth,
                $pageHeight,
                $contentId
            );
        }

        $kids = implode(' ', array_map(static fn (int $id): string => $id.' 0 R', $pageIds));
        $objects[2] = sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', $kids, count($pageIds));
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $maxId = max(array_keys($objects));
        $pdf .= sprintf("xref\n0 %d\n", $maxId + 1);
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id <= $maxId; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        }
        $pdf .= sprintf("trailer << /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF", $maxId + 1, $xref);

        return $pdf;
    }
}
