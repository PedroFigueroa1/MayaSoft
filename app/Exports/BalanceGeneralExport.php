<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

class BalanceGeneralExport
{
    private Spreadsheet $spreadsheet;
    private $sheet;
    private int $row = 7;

    // Colores
    private const C_HEADER  = '1F4E79';
    private const C_SECTION = '2E75B6';
    private const C_SUBSEC  = 'D6E4F0';
    private const C_ACTIVO  = 'EBF3FB';
    private const C_PASIVO  = 'FFF8EC';
    private const C_PAT     = 'F5EEF8';
    private const NUM_FMT   = '#,##0.00';

    public function __construct(
        private array  $estructuraActivos,
        private array  $estructuraPasivos,
        private array  $estructuraPatrimonio,
        private float  $totalActivos,
        private float  $totalPasivos,
        private float  $totalPatrimonio,
        private float  $totalPasivosPatrimonio,
        private float  $utilidadEjercicio,
        private object $periodo,
        private object $empresa,
    ) {}

    public function generate(): string
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Balance General');

        $this->configurarColumnas();
        $this->renderEncabezado();
        $this->renderColumnHeaders();
        $this->renderCuerpo();
        $this->renderTotales();
        $this->renderFirmas();
        $this->configurarPagina();

        return $this->guardar();
    }

    // ----------------------------------------------------------------
    // CONFIGURACIÓN
    // ----------------------------------------------------------------

    private function configurarColumnas(): void
    {
        $this->sheet->getColumnDimension('A')->setWidth(2)->setVisible(false);
        $this->sheet->getColumnDimension('B')->setWidth(36);
        $this->sheet->getColumnDimension('C')->setWidth(18);
        $this->sheet->getColumnDimension('D')->setWidth(2)->setVisible(false);
        $this->sheet->getColumnDimension('E')->setWidth(36);
        $this->sheet->getColumnDimension('F')->setWidth(18);
    }

    private function configurarPagina(): void
    {
        $this->sheet->freezePane('B7');
        $this->sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
    }

    // ----------------------------------------------------------------
    // ENCABEZADO
    // ----------------------------------------------------------------

    private function renderEncabezado(): void
    {
        $lines = [
            1 => ['BALANCE GENERAL',                                            16, true,  'FFFFFF', 30],
            2 => [$this->empresa->nombre ?? 'Empresa',                          11, true,  'FFFFFF', 20],
            3 => ['NIT: ' . ($this->empresa->nit ?? 'N/A'),                     10, false, 'BDD7EE', 16],
            4 => ['Al ' . now()->format('d/m/Y') . '  |  (Expresado en Bolivianos)', 10, false, 'BDD7EE', 16],
        ];

        foreach ($lines as $r => [$txt, $sz, $bold, $color, $height]) {
            $this->sheet->mergeCells("A{$r}:F{$r}");
            $this->sheet->setCellValue("A{$r}", $txt);
            $this->sheet->getRowDimension($r)->setRowHeight($height);
            $s = $this->sheet->getStyle("A{$r}");
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_HEADER);
            $s->getFont()->setColor(new Color('FF'.$color))->setBold($bold)->setSize($sz)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        }

        $this->sheet->getRowDimension(5)->setRowHeight(6);
    }

    // ----------------------------------------------------------------
    // HEADERS DE COLUMNAS
    // ----------------------------------------------------------------

    private function renderColumnHeaders(): void
    {
        $this->sheet->getRowDimension(6)->setRowHeight(22);

        foreach (['B6' => 'ACTIVOS', 'C6' => 'IMPORTE', 'E6' => 'PASIVOS Y PATRIMONIO', 'F6' => 'IMPORTE'] as $cell => $txt) {
            $this->sheet->setCellValue($cell, $txt);
            $s = $this->sheet->getStyle($cell);
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_SECTION);
            $s->getFont()->setColor(new Color('FFFFFFFF'))->setBold(true)->setSize(10)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::C_HEADER);
        }
    }

    // ----------------------------------------------------------------
    // CUERPO
    // ----------------------------------------------------------------

    private function renderCuerpo(): void
    {
        $activosFull = $this->armarLadoActivo();
        $pasivosFull = $this->armarLadoPasivo();

        $maxRows = max(count($activosFull), count($pasivosFull));

        for ($i = 0; $i < $maxRows; $i++) {
            $this->sheet->getRowDimension($this->row)->setRowHeight(18);

            $itemA = $activosFull[$i] ?? null;
            $itemP = $pasivosFull[$i] ?? null;

            $this->renderFilaActivo($itemA);
            $this->renderFilaPasivo($itemP);

            $this->row++;
        }
    }

    private function armarLadoActivo(): array
    {
        $activos = $this->aplanar($this->estructuraActivos);

        $corrientes   = array_sum(array_column(array_filter($activos, fn($a) => ($a['nivel'] ?? 1) == 1), 'saldo'));
        $noCorrientes = 0; // ampliar si tienes no corrientes

        return array_merge(
            [['_header' => true, '_seccion' => 'ACTIVOS CORRIENTES',    'saldo' => $corrientes]],
            $activos,
            [['_header' => true, '_seccion' => 'ACTIVOS NO CORRIENTES', 'saldo' => $noCorrientes]],
        );
    }

    private function armarLadoPasivo(): array
    {
        $pasivos    = $this->aplanar($this->estructuraPasivos);
        $patrimonio = $this->aplanar($this->estructuraPatrimonio, true);

        return array_merge(
            [['_header' => true, '_seccion' => 'PASIVOS CORRIENTES',    'saldo' => $this->totalPasivos]],
            $pasivos,
            [['_header' => true, '_seccion' => 'PASIVOS NO CORRIENTES', 'saldo' => 0]],
            [['_header' => true, '_seccion' => 'PATRIMONIO NETO',       'saldo' => $this->totalPatrimonio]],
            $patrimonio,
        );
    }

    private function renderFilaActivo(?array $item): void
    {
        $r = $this->row;

        if ($item === null) {
            foreach (['B', 'C'] as $col) {
                $s = $this->sheet->getStyle("{$col}{$r}");
                $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_ACTIVO);
                $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B0BEC5');
            }
            return;
        }

        if (!empty($item['_header'])) {
            $this->sheet->getRowDimension($r)->setRowHeight(20);
            $this->setCeldaSubseccion("B{$r}", $item['_seccion']);
            $this->setCeldaMonto("C{$r}", $item['saldo'], self::C_SUBSEC, true);
            return;
        }

        $indent = (($item['_depth'] ?? 0) + 1) * 2;
        $this->setCeldaNombre("B{$r}", $item['nombre'], self::C_ACTIVO, $indent);
        $this->setCeldaMonto("C{$r}", $item['saldo'], self::C_ACTIVO, false);
    }

    private function renderFilaPasivo(?array $item): void
    {
        $r = $this->row;

        if ($item === null) {
            foreach (['E', 'F'] as $col) {
                $s = $this->sheet->getStyle("{$col}{$r}");
                $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_PASIVO);
                $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B0BEC5');
            }
            return;
        }

        if (!empty($item['_header'])) {
            $this->sheet->getRowDimension($r)->setRowHeight(20);
            $this->setCeldaSubseccion("E{$r}", $item['_seccion']);
            $this->setCeldaMonto("F{$r}", $item['saldo'], self::C_SUBSEC, true);
            return;
        }

        $bg     = !empty($item['_pat']) ? self::C_PAT : self::C_PASIVO;
        $indent = (($item['_depth'] ?? 0) + 1) * 2;
        $this->setCeldaNombre("E{$r}", $item['nombre'], $bg, $indent);
        $this->setCeldaMonto("F{$r}", $item['saldo'], $bg, false, $item['saldo'] < 0);
    }

    // ----------------------------------------------------------------
    // TOTALES
    // ----------------------------------------------------------------

    private function renderTotales(): void
    {
        $r = $this->row;
        $this->sheet->getRowDimension($r)->setRowHeight(24);

        $totalPP = $this->totalPasivosPatrimonio + $this->utilidadEjercicio;

        foreach ([
            'B' => 'TOTAL ACTIVO',
            'C' => $this->totalActivos,
            'E' => 'TOTAL PASIVO Y PATRIMONIO',
            'F' => $totalPP,
        ] as $col => $val) {
            $cell = "{$col}{$r}";
            $this->sheet->setCellValue($cell, $val);
            $s = $this->sheet->getStyle($cell);
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_HEADER);
            $s->getFont()->setColor(new Color('FFFFFFFF'))->setBold(true)->setSize(10)->setName('Arial');
            $s->getAlignment()
                ->setHorizontal(in_array($col, ['C', 'F']) ? Alignment::HORIZONTAL_RIGHT : Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setIndent(1);
            if (is_numeric($val)) $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
            $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::C_HEADER);
        }

        foreach (['A', 'D'] as $col) {
            $this->sheet->getStyle("{$col}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_HEADER);
        }

        $this->row++;
    }

    // ----------------------------------------------------------------
    // FIRMAS
    // ----------------------------------------------------------------

    private function renderFirmas(): void
    {
        $r = $this->row + 1;
        $this->sheet->getRowDimension($r)->setRowHeight(40);

        $firmas = [
            'B' => ['C', "Mariel Fátima Céspedes R.\nCONTADOR"],
            'E' => ['F', ($this->empresa->nombre ?? '') . "\nPROPIETARIO"],
        ];

        foreach ($firmas as $col => [$colFin, $txt]) {
            $this->sheet->mergeCells("{$col}{$r}:{$colFin}{$r}");
            $this->sheet->setCellValue("{$col}{$r}", $txt);
            $s = $this->sheet->getStyle("{$col}{$r}");
            $s->getFont()->setItalic(true)->setSize(9)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $s->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::C_HEADER);
        }
    }

    // ----------------------------------------------------------------
    // GUARDAR
    // ----------------------------------------------------------------

    private function guardar(): string
    {
        $filename = 'balance-general-' . $this->periodo->nombre . '.xlsx';
        $path     = storage_path('app/temp/' . $filename);

        if (!file_exists(storage_path('app/temp/'))) {
            mkdir(storage_path('app/temp/'), 0755, true);
        }

        (new Xlsx($this->spreadsheet))->save($path);

        return $filename;
    }

    // ----------------------------------------------------------------
    // HELPERS DE ESTILO
    // ----------------------------------------------------------------

    private function setCeldaSubseccion(string $cell, string $txt): void
    {
        $this->sheet->setCellValue($cell, $txt);
        $s = $this->sheet->getStyle($cell);
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_SUBSEC);
        $s->getFont()->setBold(true)->setSize(10)->setName('Arial');
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B0BEC5');
    }

    private function setCeldaNombre(string $cell, string $txt, string $bg, int $indent): void
    {
        $this->sheet->setCellValue($cell, $txt);
        $s = $this->sheet->getStyle($cell);
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setSize(9)->setName('Arial');
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setIndent($indent);
        $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B0BEC5');
    }

    private function setCeldaMonto(string $cell, float $val, string $bg, bool $bold, bool $rojo = false): void
    {
        $this->sheet->setCellValue($cell, $val);
        $s = $this->sheet->getStyle($cell);
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setBold($bold)->setSize($bold ? 10 : 9)->setName('Arial');
        if ($rojo) $s->getFont()->getColor()->setRGB('C0392B');
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
        $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B0BEC5');
    }

    private function aplanar(array $cuentas, bool $esPat = false, int $depth = 0): array
    {
        $resultado = [];
        foreach ($cuentas as $cuenta) {
            $hijos = $cuenta['hijos'] ?? [];
            unset($cuenta['hijos']);
            $cuenta['_depth'] = $depth;
            if ($esPat) $cuenta['_pat'] = true;
            $resultado[] = $cuenta;
            if (!empty($hijos)) {
                $resultado = array_merge($resultado, $this->aplanar($hijos, $esPat, $depth + 1));
            }
        }
        return $resultado;
    }
}
