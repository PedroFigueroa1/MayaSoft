<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * FlujoEfectivoExport
 *
 * Genera un Excel con DOS TABLAS EN PARALELO, idéntico al diseño Vue:
 *   Cols A-F  → Estado de Origen y Aplicación de Fondos
 *   Col  G    → separador
 *   Cols H-I  → Estado de Flujo de Efectivo
 */
class FlujoEfectivoExport
{
    private Spreadsheet $spreadsheet;
    private $sheet;

    // Punteros de fila independientes
    private int $rowLeft  = 6;
    private int $rowRight = 6;

    // Paleta
    private const C_DARK    = '1F4E79';
    private const C_BLUE    = '2E75B6';
    private const C_BLUE2   = '1565C0';
    private const C_PURPLE  = '6A1B9A';
    private const C_ORANGE  = 'E65100';
    private const C_DARK_BG = '2D3748';
    private const C_FINAL   = '145A32';
    private const C_SUB     = 'D6E4F0';
    private const C_ACT_A   = 'F7FBFF';
    private const C_ACT_P   = 'FFF8F0';
    private const C_ACT_PN  = 'F9F0FF';
    private const C_OP      = 'E8F5E9';
    private const C_INV     = 'E3F2FD';
    private const C_FIN     = 'FFF3E0';
    private const C_SALDO   = 'E8EAF6';
    private const NUM_FMT   = '#,##0.00';

    public function __construct(
        private array  $origenAplicacion,
        private array  $actividadesOperativas,
        private array  $actividadesInversion,
        private array  $actividadesFinanciamiento,
        private float  $totalOperativas,
        private float  $totalInversion,
        private float  $totalFinanciamiento,
        private float  $flujoNeto,
        private float  $saldoInicial,
        private float  $saldoFinal,
        private object $periodo,
        private object $empresa,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // ENTRY POINT
    // ─────────────────────────────────────────────────────────────────────────

    public function generate(): string
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Flujo de Efectivo');

        $this->configurarColumnas();
        $this->renderHeaders();
        $this->renderColTitles();
        $this->renderTablaIzquierda();
        $this->renderTablaDerecha();
        $this->renderFirmas();
        $this->configurarPagina();

        return $this->guardar();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CONFIGURACIÓN
    // ─────────────────────────────────────────────────────────────────────────

    private function configurarColumnas(): void
    {
        // A=margen, B=nombre cuenta, C=saldo ini, D=saldo fin, E=origen, F=aplicacion
        // G=separador, H=concepto flujo, I=importe
        $widths = ['A'=>2,'B'=>28,'C'=>13,'D'=>13,'E'=>12,'F'=>12,'G'=>2,'H'=>42,'I'=>16];
        foreach ($widths as $col => $w) {
            $this->sheet->getColumnDimension($col)->setWidth($w);
        }
        //$this->sheet->getSheetView()->setShowGridLines(false);
        $this->sheet->setShowGridlines(false);
    }

    private function configurarPagina(): void
    {
        $this->sheet->freezePane('A6');
        $this->sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4);
        $margins = $this->sheet->getPageMargins();
        $margins->setLeft(0.4)->setRight(0.4)->setTop(0.5)->setBottom(0.5);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENCABEZADOS PRINCIPALES (filas 1-4)
    // ─────────────────────────────────────────────────────────────────────────

    private function renderHeaders(): void
    {
        $fechaFin = \Carbon\Carbon::parse($this->periodo->fecha_cierre)
            ->translatedFormat('d/m/Y');

        // Fila 1: títulos de cada tabla
        $this->sheet->getRowDimension(1)->setRowHeight(28);
        $this->mergeStyle('A1:F1', 'ESTADO DE ORIGEN Y APLICACIÓN DE FONDOS',
            self::C_DARK, 'FFFFFF', 11, true, 'center');
        $this->mergeStyle('H1:I1', 'ESTADO DE FLUJO DE EFECTIVO',
            self::C_DARK, 'FFFFFF', 11, true, 'center');

        // Fila 2: subtítulos
        $this->sheet->getRowDimension(2)->setRowHeight(14);
        $this->mergeStyle("A2:F2", "Al {$fechaFin}  |  (Expresado en Bolivianos)",
            self::C_DARK, 'BDD7EE', 8, false, 'center');
        $this->mergeStyle("H2:I2", "Por el Ejercicio Terminado al {$fechaFin}",
            self::C_DARK, 'BDD7EE', 8, false, 'center');

        // Fila 3: empresa
        $this->sheet->getRowDimension(3)->setRowHeight(14);
        $nombre = $this->empresa->nombre ?? '';
        $nit    = $this->empresa->nit    ?? '';
        $per    = $this->periodo->nombre ?? '';
        $this->mergeStyle("A3:F3", "{$nombre}   NIT: {$nit}   {$per}",
            self::C_SUB, self::C_DARK, 9, true, 'center');
        $this->mergeStyle("H3:I3", "{$nombre}   |   {$per}",
            self::C_SUB, self::C_DARK, 9, true, 'center');

        // Fila 4: espaciador
        $this->sheet->getRowDimension(4)->setRowHeight(4);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TÍTULOS DE COLUMNAS (fila 5)
    // ─────────────────────────────────────────────────────────────────────────

    private function renderColTitles(): void
    {
        $this->sheet->getRowDimension(5)->setRowHeight(18);

        $leftCols = ['A'=>'','B'=>'DESCRIPCIÓN','C'=>'SALDO INI.','D'=>'SALDO FIN.',
                     'E'=>'ORIGEN','F'=>'APLICACIÓN'];
        foreach ($leftCols as $col => $txt) {
            $this->setCellStyle("{$col}5", $txt, self::C_BLUE, 'FFFFFF', 8, true, 'center');
        }

        $rightCols = ['H'=>'CONCEPTO','I'=>'IMPORTE (Bs.)'];
        foreach ($rightCols as $col => $txt) {
            $this->setCellStyle("{$col}5", $txt, self::C_BLUE, 'FFFFFF', 8, true, 'center');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TABLA IZQUIERDA: Origen / Aplicación
    // ─────────────────────────────────────────────────────────────────────────

    private function renderTablaIzquierda(): void
    {
        // ── ACTIVOS ──────────────────────────────────────────────────────────
        $this->secHdrLeft('ACTIVO', self::C_DARK);
        $this->subSecLeft('CORRIENTE');

        $corriente = array_filter($this->origenAplicacion,
            fn($r) => $r['tipo'] === 'A' && str_starts_with($r['codigo'], '1.1'));
        foreach ($corriente as $item) {
            $this->dataRowLeft($item, self::C_ACT_A);
        }

        $this->subSecLeft('NO CORRIENTE');
        $noCorriente = array_filter($this->origenAplicacion,
            fn($r) => $r['tipo'] === 'A' && str_starts_with($r['codigo'], '1.2'));
        foreach ($noCorriente as $item) {
            $this->dataRowLeft($item, self::C_ACT_A);
        }

        $totalAIni = array_sum(array_column(
            array_filter($this->origenAplicacion, fn($r)=>$r['tipo']==='A'), 'saldo_ini'));
        $totalAFin = array_sum(array_column(
            array_filter($this->origenAplicacion, fn($r)=>$r['tipo']==='A'), 'saldo_fin'));
        $this->totalRowLeft('TOTAL ACTIVO', $totalAIni, $totalAFin);

        // ── PASIVO CORRIENTE ─────────────────────────────────────────────────
        $this->secHdrLeft('PASIVO CORRIENTE', self::C_ORANGE);
        $pasivoCte = array_filter($this->origenAplicacion,
            fn($r) => $r['tipo'] === 'P' && str_starts_with($r['codigo'], '2.1'));
        foreach ($pasivoCte as $item) {
            $this->dataRowLeft($item, self::C_ACT_P);
        }

        $pasivoLp = array_filter($this->origenAplicacion,
            fn($r) => $r['tipo'] === 'P' && str_starts_with($r['codigo'], '2.2'));

        if (!empty($pasivoLp)) {
            $this->subSecLeft('LARGO PLAZO');
            foreach ($pasivoLp as $item) {
                $this->dataRowLeft($item, self::C_ACT_P);
            }
        }

        $totalPIni = array_sum(array_column(
            array_filter($this->origenAplicacion, fn($r)=>$r['tipo']==='P'), 'saldo_ini'));
        $totalPFin = array_sum(array_column(
            array_filter($this->origenAplicacion, fn($r)=>$r['tipo']==='P'), 'saldo_fin'));
        $this->totalRowLeft('TOTAL PASIVO', $totalPIni, $totalPFin);

        // ── PATRIMONIO ───────────────────────────────────────────────────────
        $this->secHdrLeft('PATRIMONIO', self::C_PURPLE);
        $patrimonio = array_filter($this->origenAplicacion, fn($r) => $r['tipo'] === 'PN');
        foreach ($patrimonio as $item) {
            $this->dataRowLeft($item, self::C_ACT_PN);
        }

        $totalPNIni = array_sum(array_column(
            array_filter($this->origenAplicacion, fn($r)=>$r['tipo']==='PN'), 'saldo_ini'));
        $totalPNFin = array_sum(array_column(
            array_filter($this->origenAplicacion, fn($r)=>$r['tipo']==='PN'), 'saldo_fin'));

        $totalOrigen     = array_sum(array_column($this->origenAplicacion, 'origen'));
        $totalAplicacion = array_sum(array_column($this->origenAplicacion, 'aplicacion'));

        $this->grandTotalRowLeft(
            $totalPIni + $totalPNIni,
            $totalPFin + $totalPNFin,
            $totalOrigen,
            $totalAplicacion
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TABLA DERECHA: Flujo de Efectivo
    // ─────────────────────────────────────────────────────────────────────────

    private function renderTablaDerecha(): void
    {
        // ── OPERATIVAS ───────────────────────────────────────────────────────
        $this->secHdrRight('FLUJO DEL EFECTIVO GENERADO POR ACTIVIDADES OPERATIVAS', self::C_BLUE);

        $prevAjuste = false;
        foreach ($this->actividadesOperativas as $act) {
            if ($act['es_ajuste'] && !$prevAjuste) {
                $this->ajustesLabel(self::C_OP);
            }
            $this->itemRowRight($act['concepto'], $act['monto'], $act['tipo'], self::C_OP, $act['es_ajuste']);
            $prevAjuste = $act['es_ajuste'];
        }
        $this->totalRowRight('TOTAL EFECTIVO POR OPERACIONES', $this->totalOperativas);
        $this->spacerRight();

        // ── INVERSIÓN ────────────────────────────────────────────────────────
        $this->secHdrRight('FLUJO DEL EFECTIVO GENERADO POR ACTIVIDADES DE INVERSIÓN', self::C_BLUE2);
        if (empty($this->actividadesInversion)) {
            $this->sinMovimientosRight(self::C_INV);
        } else {
            foreach ($this->actividadesInversion as $act) {
                $this->itemRowRight($act['concepto'], $act['monto'], $act['tipo'], self::C_INV);
            }
        }
        $this->totalRowRight('TOTAL EFECTIVO POR INVERSIÓN', $this->totalInversion);
        $this->spacerRight();

        // ── FINANCIAMIENTO ───────────────────────────────────────────────────
        $this->secHdrRight('FLUJO DEL EFECTIVO GENERADO POR ACTIVIDADES DE FINANCIAMIENTO', self::C_PURPLE);
        if (empty($this->actividadesFinanciamiento)) {
            $this->sinMovimientosRight(self::C_FIN);
        } else {
            foreach ($this->actividadesFinanciamiento as $act) {
                $this->itemRowRight($act['concepto'], $act['monto'], $act['tipo'], self::C_FIN);
            }
        }
        $this->totalRowRight('TOTAL EFECTIVO POR FINANCIAMIENTO', $this->totalFinanciamiento);
        $this->spacerRight();

        // ── RESUMEN FINAL ────────────────────────────────────────────────────
        $this->resultadoRowRight('DIFERENCIA EFECTIVO GENERADA EN LA GESTIÓN',
            $this->flujoNeto, self::C_DARK_BG);
        $this->saldoRowRight('Saldo Inicial de Efectivo', $this->saldoInicial, self::C_SALDO);
        $this->saldoRowRight('EFECTIVO AL FINAL DE LA GESTIÓN', $this->saldoFinal, self::C_FINAL, true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRMAS
    // ─────────────────────────────────────────────────────────────────────────

    private function renderFirmas(): void
    {
        $fr = max($this->rowLeft, $this->rowRight) + 2;
        $this->sheet->getRowDimension($fr)->setRowHeight(40);

        $nombre = $this->empresa->nombre ?? '';

        foreach ([['A','F','Contador Autorizado'."\n".'CONTADOR'],
                  ['H','I', $nombre."\n".'PROPIETARIO']] as [$c1, $c2, $txt]) {
            $this->sheet->mergeCells("{$c1}{$fr}:{$c2}{$fr}");
            $cell = $this->sheet->getCell("{$c1}{$fr}");
            $cell->setValue($txt);
            $s = $this->sheet->getStyle("{$c1}{$fr}");
            $s->getFont()->setItalic(true)->setSize(8)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $s->getBorders()->getTop()
                ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::C_DARK);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIMITIVES — TABLA IZQUIERDA
    // ─────────────────────────────────────────────────────────────────────────

    private function secHdrLeft(string $txt, string $bg): void
    {
        $r = $this->rowLeft;
        $this->sheet->getRowDimension($r)->setRowHeight(18);
        $this->sheet->mergeCells("A{$r}:F{$r}");
        $this->sheet->getCell("A{$r}")->setValue($txt);
        $s = $this->sheet->getStyle("A{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setColor(new Color('FFFFFFFF'))->setBold(true)->setSize(9)->setName('Arial');
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        $s->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::C_DARK);
        $this->rowLeft++;
    }

    private function subSecLeft(string $txt): void
    {
        $r = $this->rowLeft;
        $this->sheet->getRowDimension($r)->setRowHeight(14);
        $this->sheet->mergeCells("A{$r}:F{$r}");
        $this->sheet->getCell("A{$r}")->setValue($txt);
        $s = $this->sheet->getStyle("A{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_SUB);
        $s->getFont()->setColor(new Color('FF2E75B6'))->setSize(8)->setName('Arial');
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)->setIndent(2);
        $this->applyThinBorder("A{$r}:F{$r}");
        $this->rowLeft++;
    }

    private function dataRowLeft(array $item, string $bg): void
    {
        $r = $this->rowLeft;
        $this->sheet->getRowDimension($r)->setRowHeight(14);

        // Columna A (margen)
        $s = $this->sheet->getStyle("A{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $this->applyThinBorder("A{$r}");

        // B: nombre
        $this->sheet->getCell("B{$r}")->setValue($item['nombre']);
        $s = $this->sheet->getStyle("B{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setSize(8)->setName('Arial');
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)->setIndent(3);
        $this->applyThinBorder("B{$r}");

        // C: saldo ini, D: saldo fin
        foreach (['C' => $item['saldo_ini'], 'D' => $item['saldo_fin']] as $col => $val) {
            $this->sheet->getCell("{$col}{$r}")->setValue($val);
            $s = $this->sheet->getStyle("{$col}{$r}");
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
            $s->getFont()->setSize(8)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
            $this->applyThinBorder("{$col}{$r}");
        }

        // E: origen (verde)
        $this->sheet->getCell("E{$r}")->setValue($item['origen'] ?: null);
        $s = $this->sheet->getStyle("E{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setSize(8)->setName('Arial');
        if ($item['origen']) {
            $s->getFont()->setColor(new Color('FF1A7A4A'));
            $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
        }
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $this->applyThinBorder("E{$r}");

        // F: aplicacion (rojo)
        $this->sheet->getCell("F{$r}")->setValue($item['aplicacion'] ?: null);
        $s = $this->sheet->getStyle("F{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setSize(8)->setName('Arial');
        if ($item['aplicacion']) {
            $s->getFont()->setColor(new Color('FFC0392B'));
            $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
        }
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $this->applyThinBorder("F{$r}");

        $this->rowLeft++;
    }

    private function totalRowLeft(string $label, float $ini, float $fin): void
    {
        $r = $this->rowLeft;
        $this->sheet->getRowDimension($r)->setRowHeight(16);
        $this->sheet->mergeCells("A{$r}:B{$r}");
        $this->sheet->getCell("A{$r}")->setValue($label);
        $s = $this->sheet->getStyle("A{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_SUB);
        $s->getFont()->setBold(true)->setSize(9)->setName('Arial')
            ->setColor(new Color('FF1F4E79'));
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        $this->applyThinBorder("A{$r}:B{$r}");

        foreach (['C'=>$ini,'D'=>$fin] as $col=>$val) {
            $this->sheet->getCell("{$col}{$r}")->setValue($val);
            $s = $this->sheet->getStyle("{$col}{$r}");
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_SUB);
            $s->getFont()->setBold(true)->setSize(9)->setName('Arial')
                ->setColor(new Color('FF1F4E79'));
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
            $this->applyThinBorder("{$col}{$r}");
        }
        foreach (['E','F'] as $col) {
            $s = $this->sheet->getStyle("{$col}{$r}");
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_SUB);
            $this->applyThinBorder("{$col}{$r}");
        }
        $this->rowLeft++;
    }

    private function grandTotalRowLeft(float $ini, float $fin, float $orig, float $aplic): void
    {
        $r = $this->rowLeft;
        $this->sheet->getRowDimension($r)->setRowHeight(18);
        $this->sheet->mergeCells("A{$r}:B{$r}");
        $this->sheet->getCell("A{$r}")->setValue('TOTAL PASIVO Y PATRIMONIO');
        $s = $this->sheet->getStyle("A{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_DARK);
        $s->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(9)->setName('Arial');
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        $this->applyMedBorder("A{$r}:B{$r}");

        foreach (['C'=>$ini,'D'=>$fin,'E'=>$orig,'F'=>$aplic] as $col=>$val) {
            $this->sheet->getCell("{$col}{$r}")->setValue($val);
            $s = $this->sheet->getStyle("{$col}{$r}");
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_DARK);
            $s->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(9)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
            $this->applyMedBorder("{$col}{$r}");
        }
        $this->rowLeft++;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIMITIVES — TABLA DERECHA
    // ─────────────────────────────────────────────────────────────────────────

    private function secHdrRight(string $txt, string $bg): void
    {
        $r = $this->rowRight;
        $this->sheet->getRowDimension($r)->setRowHeight(18);
        $this->sheet->mergeCells("H{$r}:I{$r}");
        $this->sheet->getCell("H{$r}")->setValue($txt);
        $s = $this->sheet->getStyle("H{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setColor(new Color('FFFFFFFF'))->setBold(true)->setSize(9)->setName('Arial');
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        $this->applyMedBorder("H{$r}:I{$r}");
        $this->rowRight++;
    }

    private function ajustesLabel(string $bg): void
    {
        $r = $this->rowRight;
        $this->sheet->getRowDimension($r)->setRowHeight(12);
        $this->sheet->mergeCells("H{$r}:I{$r}");
        $this->sheet->getCell("H{$r}")->setValue('Ajustes:');
        $s = $this->sheet->getStyle("H{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setItalic(true)->setSize(7)->setName('Arial')
            ->setColor(new Color('FF6B6B6B'));
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)->setIndent(2);
        $this->applyThinBorder("H{$r}:I{$r}");
        $this->rowRight++;
    }

    private function itemRowRight(
        string $concepto, float $monto, string $tipo, string $bg, bool $esAjuste = false
    ): void {
        $r = $this->rowRight;
        $this->sheet->getRowDimension($r)->setRowHeight(14);
        $this->sheet->mergeCells("H{$r}:H{$r}"); // single col, no merge needed
        $val = $tipo === 'entrada' ? $monto : -$monto;
        $indent = $esAjuste ? 4 : 1;

        $this->sheet->getCell("H{$r}")->setValue($concepto);
        $s = $this->sheet->getStyle("H{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setSize(8)->setName('Arial')->setItalic($esAjuste);
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)->setIndent($indent);
        $this->applyThinBorder("H{$r}");

        $this->sheet->getCell("I{$r}")->setValue($val);
        $s = $this->sheet->getStyle("I{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setSize(8)->setName('Arial');
        if ($val < 0) $s->getFont()->setColor(new Color('FFC0392B'));
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
        $this->applyThinBorder("I{$r}");
        $this->rowRight++;
    }

    private function sinMovimientosRight(string $bg): void
    {
        $r = $this->rowRight;
        $this->sheet->getRowDimension($r)->setRowHeight(13);
        $this->sheet->mergeCells("H{$r}:I{$r}");
        $this->sheet->getCell("H{$r}")->setValue('(Sin movimientos en el período)');
        $s = $this->sheet->getStyle("H{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setItalic(true)->setSize(8)->setName('Arial')
            ->setColor(new Color('FF9E9E9E'));
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)->setIndent(2);
        $this->applyThinBorder("H{$r}:I{$r}");
        $this->rowRight++;
    }

    private function totalRowRight(string $label, float $monto): void
    {
        $r = $this->rowRight;
        $this->sheet->getRowDimension($r)->setRowHeight(16);
        $this->sheet->mergeCells("H{$r}:H{$r}");
        $this->sheet->getCell("H{$r}")->setValue($label);
        $s = $this->sheet->getStyle("H{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_SUB);
        $s->getFont()->setBold(true)->setSize(9)->setName('Arial')
            ->setColor(new Color('FF1F4E79'));
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        $this->applyThinBorder("H{$r}");

        $this->sheet->getCell("I{$r}")->setValue($monto);
        $s = $this->sheet->getStyle("I{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_SUB);
        $s->getFont()->setBold(true)->setSize(9)->setName('Arial');
        $s->getFont()->setColor(new Color($monto < 0 ? 'FFC0392B' : 'FF1A5276'));
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
        $this->applyThinBorder("I{$r}");
        $this->rowRight++;
    }

    private function spacerRight(int $h = 5): void
    {
        $this->sheet->getRowDimension($this->rowRight)->setRowHeight($h);
        $this->rowRight++;
    }

    private function resultadoRowRight(string $label, float $monto, string $bg): void
    {
        $r = $this->rowRight;
        $this->sheet->getRowDimension($r)->setRowHeight(20);
        $this->sheet->mergeCells("H{$r}:H{$r}");
        $this->sheet->getCell("H{$r}")->setValue($label);
        $s = $this->sheet->getStyle("H{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(10)->setName('Arial');
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        $this->applyMedBorder("H{$r}");

        $this->sheet->getCell("I{$r}")->setValue($monto);
        $s = $this->sheet->getStyle("I{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setBold(true)->setSize(10)->setName('Arial');
        $s->getFont()->setColor(new Color($monto < 0 ? 'FFFFCDD2' : 'FFFFFFFF'));
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
        $this->applyMedBorder("I{$r}");
        $this->rowRight++;
    }

    private function saldoRowRight(string $label, float $monto, string $bg, bool $final = false): void
    {
        $r = $this->rowRight;
        $this->sheet->getRowDimension($r)->setRowHeight($final ? 18 : 15);
        $this->sheet->getCell("H{$r}")->setValue($label);
        $s = $this->sheet->getStyle("H{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setBold($final)->setSize(9)->setName('Arial');
        if ($final) $s->getFont()->setColor(new Color('FFFFFFFF'));
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        $final ? $this->applyMedBorder("H{$r}") : $this->applyThinBorder("H{$r}");

        $this->sheet->getCell("I{$r}")->setValue($monto);
        $s = $this->sheet->getStyle("I{$r}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setBold($final)->setSize(9)->setName('Arial');
        if ($final) $s->getFont()->setColor(new Color('FFFFFFFF'));
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
        $final ? $this->applyMedBorder("I{$r}") : $this->applyThinBorder("I{$r}");
        $this->rowRight++;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS GENÉRICOS
    // ─────────────────────────────────────────────────────────────────────────

    private function mergeStyle(
        string $range, string $value, string $bg, string $fg,
        int $sz, bool $bold, string $halign
    ): void {
        $this->sheet->mergeCells($range);
        [$start] = explode(':', $range);
        $this->sheet->getCell($start)->setValue($value);
        $s = $this->sheet->getStyle($range);
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setBold($bold)->setSize($sz)->setName('Arial')
            ->setColor(new Color('FF'.$fg));
        $s->getAlignment()->setHorizontal($halign === 'center'
            ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function setCellStyle(
        string $cell, string $value, string $bg, string $fg,
        int $sz, bool $bold, string $halign
    ): void {
        $this->sheet->getCell($cell)->setValue($value);
        $s = $this->sheet->getStyle($cell);
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getFont()->setBold($bold)->setSize($sz)->setName('Arial')
            ->setColor(new Color('FF'.$fg));
        $s->getAlignment()->setHorizontal($halign === 'center'
            ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $this->applyThinBorder($cell);
    }

    private function applyThinBorder(string $range): void
    {
        $thin = Border::BORDER_THIN;
        $this->sheet->getStyle($range)->getBorders()
            ->getAllBorders()->setBorderStyle($thin)->getColor()->setRGB('B0BEC5');
    }

    private function applyMedBorder(string $range): void
    {
        $this->sheet->getStyle($range)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::C_DARK);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GUARDAR
    // ─────────────────────────────────────────────────────────────────────────

    private function guardar(): string
    {
        $per      = preg_replace('/[^A-Za-z0-9\-_]/', '_', $this->periodo->nombre ?? 'flujo');
        $filename = "flujo-efectivo-{$per}.xlsx";
        $dir      = storage_path('app/temp/');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        (new Xlsx($this->spreadsheet))->save($dir . $filename);
        return $filename;
    }
}
