<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function visitors(): StreamedResponse
    {
        $filename = 'Data-Tamu-PT-Jafran-Indonesia-' . now()->format('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () {
            $spreadsheet = new Spreadsheet();

            // =========================
            // SHEET 1: RINGKASAN TAMU
            // =========================
            $summary = $spreadsheet->getActiveSheet();
            $summary->setTitle('Ringkasan Tamu');

            $summaryHeaders = [
                'No.',
                'Nama Tamu',
                'Nomor HP',
                'Perusahaan / Instansi',
                'Kategori',
                'Total Kunjungan',
                'Kunjungan Selesai',
                'Menunggu Persetujuan',
                'Disetujui / Berlangsung',
                'Ditolak',
                'Terdaftar Pada',
                'Kunjungan Terakhir',
                'Status Terakhir',
            ];

            $this->prepareSheet(
                $summary,
                'RINGKASAN DATA TAMU',
                'PT Jafran Indonesia | Data seluruh tamu terdaftar',
                $summaryHeaders,
                [8, 25, 20, 30, 18, 18, 20, 23, 25, 15, 22, 22, 25]
            );

            // =========================
            // SHEET 2: RIWAYAT KUNJUNGAN
            // =========================
            $history = $spreadsheet->createSheet();
            $history->setTitle('Riwayat Kunjungan');

            $historyHeaders = [
                'No.',
                'ID Kunjungan',
                'Nama Tamu',
                'Nomor HP',
                'Perusahaan / Instansi',
                'Kategori',
                'Keperluan Kunjungan',
                'Karyawan yang Dituju',
                'Departemen',
                'Status',
                'Waktu Pendaftaran',
                'Waktu Persetujuan',
                'Waktu Check-in',
                'Waktu Checkout',
                'Penilaian',
                'Kritik dan Saran',
                'Diinput Oleh',
                'Diproses Oleh',
            ];

            $this->prepareSheet(
                $history,
                'RIWAYAT KUNJUNGAN TAMU',
                'PT Jafran Indonesia | Detail seluruh riwayat kunjungan',
                $historyHeaders,
                [8, 16, 25, 20, 30, 18, 35, 25, 22, 22, 22, 22, 22, 22, 16, 45, 24, 24]
            );

            $summaryRow = 5;
            $historyRow = 5;
            $visitorNumber = 0;
            $historyNumber = 0;

            Visitor::query()
                ->with([
                    'visits' => function ($query) {
                        $query->with([
                            'employee',
                            'department',
                            'feedback',
                            'admin',
                            'inputBy',
                        ])
                        ->orderByDesc('created_at')
                        ->orderByDesc('id');
                    },
                ])
                ->orderBy('id')
                ->chunk(100, function ($visitors) use (
                    $summary,
                    $history,
                    &$summaryRow,
                    &$historyRow,
                    &$visitorNumber,
                    &$historyNumber
                ) {
                    foreach ($visitors as $visitor) {
                        $visitorNumber++;

                        $visits = $visitor->visits;
                        $total = $visits->count();
                        $completed = $visits->where('status', 'checked_out')->count();
                        $pending = $visits->where('status', 'pending')->count();
                        $approved = $visits->whereIn('status', ['approved', 'checked_in'])->count();
                        $rejected = $visits->where('status', 'rejected')->count();

                        $latestVisit = $visits->first();

                        $lastActivity = $latestVisit
                            ? (
                                $latestVisit->checked_out_at
                                ?? $latestVisit->checked_in_at
                                ?? $latestVisit->approved_at
                                ?? $latestVisit->submitted_at
                                ?? $latestVisit->created_at
                            )
                            : null;

                        // ---- Summary row ----
                        $summaryValues = [
                            $visitorNumber,
                            $visitor->name,
                            $visitor->phone_number ?? '-',
                            $visitor->company ?? '-',
                            $this->categoryLabel($visitor->category),
                            $total,
                            $completed,
                            $pending,
                            $approved,
                            $rejected,
                            $visitor->created_at,
                            $lastActivity,
                            $latestVisit
                                ? $this->statusLabel($latestVisit->status)
                                : 'Belum Pernah Berkunjung',
                        ];

                        foreach ($summaryValues as $column => $value) {
                            $cell = Coordinate::stringFromColumnIndex($column + 1) . $summaryRow;

                            if (in_array($column, [0, 5, 6, 7, 8, 9], true)) {
                                $this->writeNumber($summary, $cell, (int) $value);
                            } elseif (in_array($column, [10, 11], true)) {
                                $this->writeDate($summary, $cell, $value);
                            } else {
                                $this->writeText($summary, $cell, $value);
                            }
                        }

                        $summaryRow++;

                        // ---- History rows ----
                        foreach ($visits as $visit) {
                            $historyNumber++;

                            $feedback = $visit->feedback;

                            $historyValues = [
                                $historyNumber,
                                $visit->id,
                                $visitor->name,
                                $visitor->phone_number ?? '-',
                                $visitor->company ?? '-',
                                $this->categoryLabel($visitor->category),
                                $visit->purpose ?? '-',
                                $visit->employee?->name ?? '-',
                                $visit->department?->name ?? '-',
                                $this->statusLabel($visit->status),
                                $visit->submitted_at ?? $visit->created_at,
                                $visit->approved_at,
                                $visit->checked_in_at,
                                $visit->checked_out_at,
                                $feedback?->rating !== null
                                    ? $feedback->rating . ' / 5'
                                    : 'Belum Dinilai',
                                $feedback?->comment ?? '-',
                                $visit->inputBy?->name ?? '-',
                                $visit->admin?->name ?? '-',
                            ];

                            foreach ($historyValues as $column => $value) {
                                $cell = Coordinate::stringFromColumnIndex($column + 1) . $historyRow;

                                if (in_array($column, [0, 1], true)) {
                                    $this->writeNumber($history, $cell, (int) $value);
                                } elseif (in_array($column, [10, 11, 12, 13], true)) {
                                    $this->writeDate($history, $cell, $value);
                                } else {
                                    $this->writeText($history, $cell, $value);
                                }
                            }

                            // Tinggi baris untuk komentar panjang
                            if (strlen((string) ($feedback?->comment ?? '')) > 80) {
                                $history->getRowDimension($historyRow)->setRowHeight(45);
                            }

                            $historyRow++;
                        }
                    }
                });

            $this->finishSheet($summary, 'M', $summaryRow - 1);
            $this->finishSheet($history, 'R', $historyRow - 1);

            $spreadsheet->setActiveSheetIndex(0);

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function prepareSheet(
        Worksheet $sheet,
        string $title,
        string $subtitle,
        array $headers,
        array $widths
    ): void {
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->setCellValue('A1', $title);

        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->setCellValue('A2', $subtitle);

        $sheet->fromArray($headers, null, 'A4');

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4B5D34'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
            'font' => [
                'italic' => true,
                'size' => 10,
                'color' => ['rgb' => '666666'],
            ],
        ]);

        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '2E3524'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'C9A227'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D6D6D6'],
                ],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(4)->setRowHeight(38);

        foreach ($widths as $index => $width) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->freezePane('A5');
        $sheet->setShowGridlines(false);

        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 4);
    }

    private function finishSheet(Worksheet $sheet, string $lastColumn, int $lastRow): void
    {
        if ($lastRow < 4) {
            $lastRow = 4;
        }

        $sheet->setAutoFilter("A4:{$lastColumn}{$lastRow}");

        $sheet->getStyle("A5:{$lastColumn}{$lastRow}")->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_HAIR,
                    'color' => ['rgb' => 'DDDDDD'],
                ],
            ],
        ]);

        // Warna selang-seling baris
        for ($row = 5; $row <= $lastRow; $row++) {
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F2F5EE');
            }
        }
    }

    private function writeText(Worksheet $sheet, string $cell, $value): void
    {
        $sheet->setCellValueExplicit(
            $cell,
            (string) ($value ?? '-'),
            DataType::TYPE_STRING
        );
    }

    private function writeNumber(Worksheet $sheet, string $cell, int $value): void
    {
        $sheet->setCellValue($cell, $value);
    }

    private function writeDate(Worksheet $sheet, string $cell, $value): void
    {
        if (!$value) {
            $this->writeText($sheet, $cell, '-');
            return;
        }

        try {
            $date = Carbon::parse($value);
            $this->writeText($sheet, $cell, $date->format('d-m-Y H:i'));
        } catch (\Throwable $e) {
            $this->writeText($sheet, $cell, '-');
        }
    }

    private function categoryLabel(?string $category): string
    {
        return match ($category) {
            'prioritas' => 'Prioritas',
            'reguler' => 'Reguler',
            default => ucfirst($category ?? '-'),
        };
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'checked_in' => 'Sedang Berlangsung',
            'checked_out' => 'Selesai',
            'rejected' => 'Ditolak',
            default => ucfirst($status ?? '-'),
        };
    }
}
