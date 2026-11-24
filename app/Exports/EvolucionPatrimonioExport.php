<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

class EvolucionPatrimonioExport
{
    private Spreadsheet $spreadsheet;
    private $sheet;

    private const C_HEADER = '1F4E79';
    private const C_SECTION= '2E75B6';
    private const C_SALDO  = 'E8F5E9';
    private const C_MOV    = 'FFF8EC';
    private const C_TOTAL  = '1F4E79';
    private const NUM_FMT  = '#,##0.00';

    // Columnas del patrimonio boliviano
    private const COLUMNAS = [
        'Capital Social',
        'Ajuste de Capital',
        'Utilidad/Pérdida de la Gestión',
        'Resultados Acumulados',
        'Ajuste Global al Patrimonio',
        'Reserva Legal',
        'TOTAL',
    ];

    public function __construct(
        private array  $saldoInicial,   // ['capital'=>x, 'ajuste_capital'=>x, ...]
        private array  $movimientos,    // [['descripcion'=>'...', 'capital'=>x, ...], ...]
        private array  $saldoFinal,
        private object $periodo,
        private object $empresa,
    ) {}

    public function generate(): string
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Evolución Patrimonio');

        $this->configurarColumnas();
        $this->renderEncabezado();
        $this->renderColumnHeaders();
        $this->renderCuerpo();
        $this->renderFirmas();
        $this->configurarPagina();

        return $this->guardar();
    }

    private function configurarColumnas(): void
    {
        $anchos = ['A' => 38, 'B' => 14, 'C' => 14, 'D' => 18, 'E' => 16, 'F' => 16, 'G' => 14, 'H' => 16];
        foreach ($anchos as $col => $w) {
            $this->sheet->getColumnDimension($col)->setWidth($w);
        }
    }

    private function configurarPagina(): void
    {
        $this->sheet->freezePane('A6');
        $this->sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
    }

    private function renderEncabezado(): void
    {
        $fechaCierre = \Carbon\Carbon::parse($this->periodo->fecha_cierre)->translatedFormat('d \de F Y');

        $lines = [
            1 => ['ESTADO DE EVOLUCIÓN DEL PATRIMONIO',                         14, true,  28],
            2 => [($this->empresa->nombre ?? '') . '  |  NIT: ' . ($this->empresa->nit ?? ''), 9, false, 16],
            3 => ["Al {$fechaCierre}  |  (Expresado en Bolivianos)",             9, false, 14],
        ];

        foreach ($lines as $r => [$txt, $sz, $bold, $h]) {
            $this->sheet->mergeCells("A{$r}:H{$r}");
            $this->sheet->setCellValue("A{$r}", $txt);
            $this->sheet->getRowDimension($r)->setRowHeight($h);
            $s = $this->sheet->getStyle("A{$r}");
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_HEADER);
            $s->getFont()->setColor(new Color('FFFFFFFF'))->setBold($bold)->setSize($sz)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        }

        $this->sheet->getRowDimension(4)->setRowHeight(5);
    }

    private function renderColumnHeaders(): void
    {
        $this->sheet->getRowDimension(5)->setRowHeight(42);

        $headers = ['DESCRIPCIÓN', ...self::COLUMNAS];
        foreach ($headers as $i => $txt) {
            $col = $i + 1;
            $cell = $this->sheet->getCellByColumnAndRow($col, 5);
            $cell->setValue($txt);
            $s = $this->sheet->getStyleByColumnAndRow($col, 5);
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_SECTION);
            $s->getFont()->setColor(new Color('FFFFFFFF'))->setBold(true)->setSize(10)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::C_HEADER);
        }
    }

    private function renderCuerpo(): void
    {
        $row = 6;

        // Saldo inicial
        $fechaInicio = \Carbon\Carbon::parse($this->periodo->fecha_inicio)->subDay()->format('d/m/Y');
        $this->renderFila($row, "Saldos al {$fechaInicio}", $this->saldoInicial, self::C_SALDO, true, false);
        $row++;

        // Spacer
        $this->sheet->getRowDimension($row)->setRowHeight(4);
        $row++;

        // Movimientos
        foreach ($this->movimientos as $mov) {
            $desc = $mov['descripcion'];
            unset($mov['descripcion']);
            $this->renderFila($row, $desc, $mov, self::C_MOV, false, false);
            $row++;
        }

        // Spacer
        $this->sheet->getRowDimension($row)->setRowHeight(4);
        $row++;

        // Saldo final
        $fechaFin = \Carbon\Carbon::parse($this->periodo->fecha_cierre)->format('d/m/Y');
        $this->renderFila($row, "Saldo al {$fechaFin}", $this->saldoFinal, self::C_TOTAL, true, true);
        $row++;

        // Guardar row para firmas
        $this->lastRow = $row;
    }

    private int $lastRow = 10;

    private function renderFila(int $row, string $desc, array $valores, string $bg, bool $bold, bool $isTotal): void
    {
        $this->sheet->getRowDimension($row)->setRowHeight($isTotal ? 22 : ($bold ? 20 : 17));

        // Descripción
        $this->sheet->setCellValue("A{$row}", $desc);
        $s = $this->sheet->getStyle("A{$row}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setBold($bold)->setSize($bold ? 10 : 9)->setName('Arial');
        if ($isTotal) $s->getFont()->setColor(new Color('FFFFFFFF'));
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setIndent($isTotal ? 1 : 2);
        $s->getBorders()->getAllBorders()->setBorderStyle($isTotal ? Border::BORDER_MEDIUM : Border::BORDER_THIN)->getColor()->setRGB($isTotal ? self::C_HEADER : 'B0BEC5');

        // Columnas de valores — orden fijo
        $keys = ['capital_social', 'ajuste_capital', 'utilidad_gestion', 'resultados_acumulados', 'ajuste_global', 'reserva_legal', 'total'];

        foreach ($keys as $i => $key) {
            $col = $i + 2;
            $val = $valores[$key] ?? null;

            $cell = $this->sheet->getCellByColumnAndRow($col, $row);
            $cell->setValue($val);

            $s = $this->sheet->getStyleByColumnAndRow($col, $row);
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
            $s->getBorders()->getAllBorders()->setBorderStyle($isTotal ? Border::BORDER_MEDIUM : Border::BORDER_THIN)->getColor()->setRGB($isTotal ? self::C_HEADER : 'B0BEC5');

            if ($val !== null && is_numeric($val)) {
                $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
                $colorFuente = 'FFFFFFFF'; // blanco para total
                if (!$isTotal) {
                    $colorFuente = $val < 0 ? 'FFC0392B' : 'FF000000';
                } else {
                    $colorFuente = $val < 0 ? 'FFFFCDD2' : 'FFFFFFFF';
                }
                $s->getFont()->setColor(new Color($colorFuente))->setBold($bold)->setSize($bold ? 10 : 9)->setName('Arial');
            } else {
                $s->getFont()->setSize(9)->setName('Arial');
                if ($isTotal) $s->getFont()->setColor(new Color('FFFFFFFF'));
            }
        }
    }

    private function renderFirmas(): void
    {
        $row = $this->lastRow + 1;
        $this->sheet->getRowDimension($row)->setRowHeight(40);

        $medium = Border::BORDER_MEDIUM;

        $this->sheet->mergeCells("A{$row}:D{$row}");
        $this->sheet->setCellValue("A{$row}", "Contador Autorizado\nCONTADOR");
        $s = $this->sheet->getStyle("A{$row}");
        $s->getFont()->setItalic(true)->setSize(9)->setName('Arial');
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $s->getBorders()->getTop()->setBorderStyle($medium)->getColor()->setRGB(self::C_HEADER);

        $this->sheet->mergeCells("E{$row}:H{$row}");
        $this->sheet->setCellValue("E{$row}", ($this->empresa->nombre ?? '') . "\nPROPIETARIO");
        $s = $this->sheet->getStyle("E{$row}");
        $s->getFont()->setItalic(true)->setSize(9)->setName('Arial');
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $s->getBorders()->getTop()->setBorderStyle($medium)->getColor()->setRGB(self::C_HEADER);
    }

    private function guardar(): string
    {
        $filename = 'evolucion-patrimonio-' . $this->periodo->nombre . '.xlsx';
        $path     = storage_path('app/temp/' . $filename);
        if (!file_exists(storage_path('app/temp/'))) mkdir(storage_path('app/temp/'), 0755, true);
        (new Xlsx($this->spreadsheet))->save($path);
        return $filename;
    }
}
