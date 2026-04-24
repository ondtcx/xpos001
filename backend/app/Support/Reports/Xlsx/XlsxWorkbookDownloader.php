<?php

namespace App\Support\Reports\Xlsx;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class XlsxWorkbookDownloader
{
    /**
     * @param  array<int, array{name:string, headings:array<int, string>, rows:array<int, array<int, mixed>>, money_columns?:array<int, int>, quantity_columns?:array<int, int>}>  $sheets
     */
    public function download(string $fileName, array $sheets): StreamedResponse
    {
        return response()->streamDownload(function () use ($sheets): void {
            $spreadsheet = new Spreadsheet();
            $spreadsheet->removeSheetByIndex(0);

            foreach ($sheets as $sheetIndex => $sheetData) {
                $sheet = $spreadsheet->createSheet($sheetIndex);
                $sheet->setTitle($sheetData['name']);

                $headings = $sheetData['headings'];
                $rows = $sheetData['rows'];
                $moneyColumns = $sheetData['money_columns'] ?? [];
                $quantityColumns = $sheetData['quantity_columns'] ?? [];

                foreach ($headings as $columnIndex => $heading) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1) . '1', $heading);
                }

                foreach ($rows as $rowIndex => $row) {
                    foreach ($row as $columnIndex => $value) {
                        $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1) . ($rowIndex + 2), $value);
                    }
                }

                $lastColumn = Coordinate::stringFromColumnIndex(count($headings));
                $lastRow = max(count($rows) + 1, 2);

                $sheet->freezePane('A2');
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4F46E5'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);

                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');

                foreach ($moneyColumns as $columnIndex) {
                    $column = Coordinate::stringFromColumnIndex($columnIndex + 1);
                    $sheet->getStyle("{$column}2:{$column}{$lastRow}")->getNumberFormat()->setFormatCode('$#,##0.00');
                }

                foreach ($quantityColumns as $columnIndex) {
                    $column = Coordinate::stringFromColumnIndex($columnIndex + 1);
                    $sheet->getStyle("{$column}2:{$column}{$lastRow}")->getNumberFormat()->setFormatCode('0.000');
                }

                for ($columnIndex = 1; $columnIndex <= count($headings); $columnIndex++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
                }
            }

            $spreadsheet->setActiveSheetIndex(0);
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
