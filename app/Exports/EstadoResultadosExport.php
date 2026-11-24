<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Carbon\Carbon;

class EstadoResultadosExport
{
    private Spreadsheet $spreadsheet;
    private $sheet;
    private int $row = 8;

    private const C_HEADER   = '1F4E79';
    private const C_SECTION  = '2E75B6';
    private const C_SUBSEC   = 'D6E4F0';
    private const C_INGRESO  = 'E8F5E9';
    private const C_COSTO    = 'FFF3E0';
    private const C_GASTO    = 'FFEBEE';
    private const C_FIN_GAS  = 'FFF8E1';
    private const C_OTRO_GAS = 'FCE4EC';
    private const C_TOTAL    = '1F4E79';
    private const NUM_FMT    = '#,##0.00';

    public function __construct(
        private array   $estructuraIngresos,
        private array   $estructuraGastos,
        private float   $totalIngresos,
        private float   $totalGastos,
        private float   $resultadoEjercicio,
        private object  $periodo,
        private ?object $empresa = null,
        private ?string $fechaInicio = null,
        private ?string $fechaFin = null,
    ) {}

    public function generate(): string
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Estado de Resultados');

        $this->configurarColumnas();
        $this->renderEncabezado();
        $this->renderColumnHeader();
        $this->renderCuerpo();
        $this->renderFirmas();
        $this->configurarPagina();

        return $this->guardar();
    }

    private function configurarColumnas(): void
    {
        $this->sheet->getColumnDimension('A')->setWidth(3);
        $this->sheet->getColumnDimension('B')->setWidth(12);
        $this->sheet->getColumnDimension('C')->setWidth(48);
        $this->sheet->getColumnDimension('D')->setWidth(18);
    }

    private function configurarPagina(): void
    {
        $this->sheet->freezePane('A8');
        $this->sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
    }

    private function renderEncabezado(): void
    {
        $nombreEmpresa = $this->empresa?->nombre ?? 'Empresa';
        $nitEmpresa    = $this->empresa?->nit ?? 'N/A';

        $ini = $this->fechaInicio
            ? Carbon::parse($this->fechaInicio)->translatedFormat('d \de F Y')
            : Carbon::parse($this->periodo->fecha_inicio)->translatedFormat('d \de F Y');

        $fin = $this->fechaFin
            ? Carbon::parse($this->fechaFin)->translatedFormat('d \de F Y')
            : Carbon::parse($this->periodo->fecha_cierre)->translatedFormat('d \de F Y');

        $lines = [
            1 => ['ESTADO DE RESULTADOS',              16, true,  'FFFFFF', 30],
            2 => [$nombreEmpresa,                       11, true,  'FFFFFF', 20],
            3 => ['NIT: ' . $nitEmpresa,                10, false, 'BDD7EE', 16],
            4 => ["Del {$ini} al {$fin}",               10, false, 'BDD7EE', 14],
            5 => ['(Expresado en Bolivianos)',          9, false, 'BDD7EE', 13],
        ];

        foreach ($lines as $r => [$txt, $sz, $bold, $color, $height]) {
            $this->sheet->mergeCells("A{$r}:D{$r}");
            $this->sheet->setCellValue("A{$r}", $txt);
            $this->sheet->getRowDimension($r)->setRowHeight($height);
            $s = $this->sheet->getStyle("A{$r}");
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_HEADER);
            $s->getFont()->setColor(new Color('FF' . $color))->setBold($bold)->setSize($sz)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        }

        $this->sheet->getRowDimension(6)->setRowHeight(5);
    }

    private function renderColumnHeader(): void
    {
        $this->sheet->getRowDimension(7)->setRowHeight(20);
        $this->sheet->mergeCells('A7:C7');

        foreach (['A7' => 'DESCRIPCIÓN', 'D7' => 'IMPORTE (Bs.)'] as $cell => $txt) {
            $this->sheet->setCellValue($cell, $txt);
            $s = $this->sheet->getStyle($cell);
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_SECTION);
            $s->getFont()->setColor(new Color('FFFFFFFF'))->setBold(true)->setSize(10)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // FIX: getColor() antes de setRGB()
            $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::C_HEADER);
        }
    }

    private function renderCuerpo(): void
    {
        $ingresos = $this->aplanar($this->estructuraIngresos);
        $gastos   = $this->aplanar($this->estructuraGastos);

        if (!empty($ingresos)) {
            $this->renderSeccion('INGRESOS', $ingresos, self::C_INGRESO, 'TOTAL INGRESOS', $this->totalIngresos);
            $this->spacer();
        }

        if (!empty($gastos)) {
            $this->renderGastosPorSubseccion($gastos);
            $this->spacer();
        }

        $this->renderResultado();
    }

    private function renderGastosPorSubseccion(array $gastos): void
    {
        $mapaColores = [
            'costo'      => self::C_COSTO,
            'venta'      => self::C_COSTO,
            'gasto'      => self::C_GASTO,
            'operativo'  => self::C_GASTO,
            'admin'      => self::C_GASTO,
            'financiero' => self::C_FIN_GAS,
            'otro'       => self::C_OTRO_GAS,
        ];

        $grupos = [];
        $grupoActual = null;

        foreach ($gastos as $item) {
            if ($item['_depth'] === 0 && !$item['permite_movimiento']) {
                $grupoActual = $item['nombre'];
                $grupos[$grupoActual] = ['raiz' => $item, 'items' => []];
            } elseif ($grupoActual !== null) {
                $grupos[$grupoActual]['items'][] = $item;
            }
        }

        foreach ($grupos as $nombre => $grupo) {
            $nombreLower = strtolower($nombre);
            $bg = self::C_GASTO;

            foreach ($mapaColores as $clave => $color) {
                if (str_contains($nombreLower, $clave)) {
                    $bg = $color;
                    break;
                }
            }

            $this->renderSeccion(
                strtoupper($nombre),
                $grupo['items'],
                $bg,
                'TOTAL ' . strtoupper($nombre),
                $grupo['raiz']['saldo']
            );
            $this->spacer();
        }

        if (empty($grupos) && !empty($gastos)) {
            $this->renderSeccion('GASTOS', $gastos, self::C_GASTO, 'TOTAL GASTOS', $this->totalGastos);
        }
    }

    private function renderSeccion(string $titulo, array $items, string $bg, string $totalLabel, float $totalVal, bool $showTotal = true): void
    {
        // Título de sección
        $this->sheet->getRowDimension($this->row)->setRowHeight(20);
        $this->sheet->mergeCells("A{$this->row}:C{$this->row}");
        $this->sheet->setCellValue("A{$this->row}", $titulo);
        $s = $this->sheet->getStyle("A{$this->row}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_SECTION);
        $s->getFont()->setColor(new Color('FFFFFFFF'))->setBold(true)->setSize(10)->setName('Arial');
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        // FIX: getColor() antes de setRGB()
        $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::C_HEADER);

        $this->sheet->getStyle("D{$this->row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_SECTION);
        $this->sheet->getStyle("D{$this->row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::C_HEADER);
        $this->row++;

        // Items de detalle
        foreach ($items as $item) {
            if ($item['_depth'] === 0 && !$item['permite_movimiento']) {
                continue;
            }

            $this->sheet->getRowDimension($this->row)->setRowHeight(17);
            $indent = (($item['_depth']) * 2) + 1;

            $this->celdaFondo("A{$this->row}", $bg);

            // Columna B (código)
            $this->sheet->setCellValue("B{$this->row}", $item['codigo']);
            $s = $this->sheet->getStyle("B{$this->row}");
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
            $s->getFont()->setSize(9)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B0BEC5');

            // Columna C (nombre)
            $this->sheet->setCellValue("C{$this->row}", $item['nombre']);
            $s = $this->sheet->getStyle("C{$this->row}");
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
            $s->getFont()->setSize(9)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setIndent($indent);
            $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B0BEC5');

            // Columna D (monto)
            if ($item['permite_movimiento']) {
                $this->sheet->setCellValue("D{$this->row}", $item['saldo']);
                $s = $this->sheet->getStyle("D{$this->row}");
                $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
                $s->getFont()->setSize(9)->setName('Arial');
                if ($item['saldo'] < 0) $s->getFont()->getColor()->setRGB('C0392B');
                $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
                $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
                $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B0BEC5');
            } else {
                if (abs($item['saldo']) > 0.001) {
                    $this->sheet->setCellValue("D{$this->row}", $item['saldo']);
                    $s = $this->sheet->getStyle("D{$this->row}");
                    $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
                    $s->getFont()->setSize(9)->setName('Arial')->setBold(true);
                    $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
                    $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
                    $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B0BEC5');
                } else {
                    $this->celdaFondo("D{$this->row}", $bg);
                }
            }

            $this->row++;
        }

        // Fila total
        if ($showTotal) {
            $this->sheet->getRowDimension($this->row)->setRowHeight(19);
            $this->sheet->mergeCells("A{$this->row}:C{$this->row}");
            $this->sheet->setCellValue("A{$this->row}", $totalLabel);
            $s = $this->sheet->getStyle("A{$this->row}");
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_SUBSEC);
            $s->getFont()->setBold(true)->setSize(9)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
            $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B0BEC5');

            $this->sheet->setCellValue("D{$this->row}", $totalVal);
            $s = $this->sheet->getStyle("D{$this->row}");
            $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_SUBSEC);
            $s->getFont()->setBold(true)->setSize(9)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
            $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
            $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B0BEC5');
            $this->row++;
        }
    }

    private function renderResultado(): void
    {
        $this->sheet->getRowDimension($this->row)->setRowHeight(24);
        $this->sheet->mergeCells("A{$this->row}:C{$this->row}");
        $this->sheet->setCellValue("A{$this->row}", 'RESULTADO DE LA GESTIÓN');

        $s = $this->sheet->getStyle("A{$this->row}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_TOTAL);
        $s->getFont()->setColor(new Color('FFFFFFFF'))->setBold(true)->setSize(11)->setName('Arial');
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        // FIX: getColor() antes de setRGB()
        $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::C_HEADER);

        $this->sheet->setCellValue("D{$this->row}", $this->resultadoEjercicio);
        $s = $this->sheet->getStyle("D{$this->row}");
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_TOTAL);
        $s->getFont()->setColor(new Color('FFFFFFFF'))->setBold(true)->setSize(11)->setName('Arial');
        if ($this->resultadoEjercicio < 0) $s->getFont()->getColor()->setRGB('FFCDD2');
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $s->getNumberFormat()->setFormatCode(self::NUM_FMT);
        $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::C_HEADER);
        $this->row++;
    }

    private function renderFirmas(): void
    {
        $this->row += 2;
        $this->sheet->getRowDimension($this->row)->setRowHeight(40);

        $nombreEmpresa = $this->empresa?->nombre ?? 'Empresa';

        $firmas = [
            ['A', 'B', $nombreEmpresa . "\nPROPIETARIO"],
            ['C', 'D', "Contador Autorizado\nCONTADOR"],
        ];

        foreach ($firmas as [$colIni, $colFin, $txt]) {
            $this->sheet->mergeCells("{$colIni}{$this->row}:{$colFin}{$this->row}");
            $this->sheet->setCellValue("{$colIni}{$this->row}", $txt);
            $s = $this->sheet->getStyle("{$colIni}{$this->row}");
            $s->getFont()->setItalic(true)->setSize(9)->setName('Arial');
            $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $s->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::C_HEADER);
        }
    }

    private function spacer(int $h = 5): void
    {
        $this->sheet->getRowDimension($this->row)->setRowHeight($h);
        $this->row++;
    }

    private function celdaFondo(string $cell, string $bg): void
    {
        $s = $this->sheet->getStyle($cell);
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        $s->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B0BEC5');
    }

    private function aplanar(array $cuentas, int $depth = 0): array
    {
        $resultado = [];
        foreach ($cuentas as $cuenta) {
            $hijos = $cuenta['hijos'] ?? [];
            unset($cuenta['hijos']);
            $cuenta['_depth'] = $depth;
            $resultado[] = $cuenta;
            if (!empty($hijos)) {
                $resultado = array_merge($resultado, $this->aplanar($hijos, $depth + 1));
            }
        }
        return $resultado;
    }

    private function guardar(): string
    {
        $filename = 'estado-resultados-' . $this->periodo->nombre . '-' . now()->timestamp . '.xlsx';
        $path = storage_path('app/temp/' . $filename);

        if (!file_exists(storage_path('app/temp/'))) {
            mkdir(storage_path('app/temp/'), 0755, true);
        }

        (new Xlsx($this->spreadsheet))->save($path);
        return $filename;
    }
}
