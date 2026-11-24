<?php

namespace App\Exports;

use App\Models\Periodo;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;

class SumasSaldosExport implements WithEvents, WithTitle, ShouldAutoSize
{
    protected $periodoId;
    protected $periodo;
    protected $datos;
    protected $empresaNit;
    protected $empresaNombre;

    public function __construct($periodoId, $nit = null, $nombreEmpresa = null)
    {
        $this->periodoId = $periodoId;
        $this->periodo = Periodo::findOrFail($periodoId);
        $this->empresaNit = $nit ?? ($this->periodo->empresa->nit ?? 'NIT NO DEFINIDO');
        $this->empresaNombre = $nombreEmpresa ?? ($this->periodo->empresa->nombre ?? 'EMPRESA');
        $this->datos = $this->obtenerDatos();
    }

    /**
     * Divide el código "1.1.1.001.001" en sus 5 partes.
     */
    private function parsearCodigo(string $codigo): array
    {
        $partes = explode('.', $codigo);
        return [
            $partes[0] ?? '',
            $partes[1] ?? '',
            $partes[2] ?? '',
            $partes[3] ?? '',
            $partes[4] ?? '',
        ];
    }

    /**
     * Obtener datos en TIEMPO REAL desde cuentas y detalle_asientos.
     * Ya NO usa la tabla saldos_cuentas.
     */
    private function obtenerDatos(): Collection
    {
        // 1. Subquery: movimientos del período agrupados por cuenta
        $movimientos = DB::table('detalle_asientos as da')
            ->join('asientos as a', 'a.id', '=', 'da.asiento_id')
            ->where('a.periodo_id', $this->periodoId)
            ->select(
                'da.cuenta_id',
                DB::raw('COALESCE(SUM(da.debe), 0)  as total_debe'),
                DB::raw('COALESCE(SUM(da.haber), 0) as total_haber')
            )
            ->groupBy('da.cuenta_id');

        // 2. Unir con cuentas y traer solo las que tienen movimiento
        $cuentas = DB::table('cuentas as c')
            ->leftJoinSub($movimientos, 'mov', function ($join) {
                $join->on('mov.cuenta_id', '=', 'c.id');
            })
            ->where(function ($q) {
                $q->where('mov.total_debe', '>', 0)
                  ->orWhere('mov.total_haber', '>', 0);
            })
            ->orderBy('c.codigo', 'asc')
            ->select(
                'c.id',
                'c.codigo',
                'c.nombre',
                'c.tipo',
                DB::raw('COALESCE(mov.total_debe, 0)  as total_debe'),
                DB::raw('COALESCE(mov.total_haber, 0) as total_haber')
            )
            ->get();

        return $cuentas->map(function ($cta) {
            [$clase, $grupo, $subgrupo, $cuentaPrincipal, $cuentaAnalitica] = $this->parsearCodigo($cta->codigo);

            $debe  = floatval($cta->total_debe);
            $haber = floatval($cta->total_haber);

            // Calcular saldos finales según naturaleza de la cuenta
            $saldoFinalDebe  = 0;
            $saldoFinalHaber = 0;

            switch ($cta->tipo) {
                case 'A': // ACTIVO
                case 'G': // GASTO
                    $neto = $debe - $haber;
                    if ($neto > 0) {
                        $saldoFinalDebe = $neto;
                    } elseif ($neto < 0) {
                        $saldoFinalHaber = abs($neto);
                    }
                    break;

                case 'P':  // PASIVO
                case 'PN': // PATRIMONIO
                case 'I':  // INGRESO
                    $neto = $haber - $debe;
                    if ($neto > 0) {
                        $saldoFinalHaber = $neto;
                    } elseif ($neto < 0) {
                        $saldoFinalDebe = abs($neto);
                    }
                    break;
            }

            // Formato del Excel original: Deudor/Acreedor como neto
            $saldoDeudor   = 0;
            $saldoAcreedor = 0;
            $netoSaldo     = $saldoFinalDebe - $saldoFinalHaber;

            if ($netoSaldo > 0) {
                $saldoDeudor = $netoSaldo;
            } elseif ($netoSaldo < 0) {
                $saldoAcreedor = abs($netoSaldo);
            }

            return [
                'clase'            => $clase,
                'grupo'            => $grupo,
                'subgrupo'         => $subgrupo,
                'cuenta_principal' => $cuentaPrincipal,
                'cuenta_analitica' => $cuentaAnalitica,
                'nombre'           => $cta->nombre,
                'debe_sumas'       => $debe,
                'haber_sumas'      => $haber,
                'saldo_deudor'     => $saldoDeudor,
                'saldo_acreedor'   => $saldoAcreedor,
            ];
        });
    }

    public function title(): string
    {
        return 'Sumas y Saldos';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $row = 1;

                // NIT
                $sheet->mergeCells("A{$row}:J{$row}");
                $sheet->setCellValue("A{$row}", "NIT: {$this->empresaNit}");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                ]);
                $row++;

                // Razón social
                $sheet->mergeCells("A{$row}:J{$row}");
                $sheet->setCellValue("A{$row}", "DE: {$this->empresaNombre}");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                ]);
                $row += 2;

                // Título
                $sheet->mergeCells("A{$row}:J{$row}");
                $sheet->setCellValue("A{$row}", "BALANCE DE COMPROBACIÓN DE SUMAS Y SALDOS");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => 'center']
                ]);
                $row++;

                // Subtítulo
                $sheet->mergeCells("A{$row}:J{$row}");
                $sheet->setCellValue("A{$row}", "(EXPRESADO EN BOLIVIANOS)");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10],
                    'alignment' => ['horizontal' => 'center']
                ]);
                $row += 2;

                // Periodo (corregido: fecha_cierre en vez de fecha_fin)
                $sheet->mergeCells("A{$row}:J{$row}");
                $sheet->setCellValue("A{$row}", "Periodo: {$this->periodo->nombre} - {$this->periodo->fecha_inicio} al {$this->periodo->fecha_cierre}");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                ]);
                $row++;

                // Fecha generación
                $sheet->mergeCells("A{$row}:J{$row}");
                $sheet->setCellValue("A{$row}", "Generado: " . now()->format('d/m/Y H:i:s'));
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 9],
                ]);
                $row += 2;

                // ========== ENCABEZADOS - FILA 1 ==========
                $firstHeaderRow = $row;

                $sheet->mergeCells("A{$firstHeaderRow}:E{$firstHeaderRow}");
                $sheet->setCellValue("A{$firstHeaderRow}", "CODIFICACIÓN DE LA CUENTA");

                $sheet->mergeCells("F{$firstHeaderRow}:F" . ($firstHeaderRow + 1));
                $sheet->setCellValue("F{$firstHeaderRow}", "NOMBRE DE LA CUENTA");

                $sheet->mergeCells("G{$firstHeaderRow}:H{$firstHeaderRow}");
                $sheet->setCellValue("G{$firstHeaderRow}", "SUMAS");

                $sheet->mergeCells("I{$firstHeaderRow}:J{$firstHeaderRow}");
                $sheet->setCellValue("I{$firstHeaderRow}", "SALDOS");

                // ========== ENCABEZADOS - FILA 2 ==========
                $row++;
                $secondHeaderRow = $row;

                $sheet->setCellValue("A{$secondHeaderRow}", "CLASE");
                $sheet->setCellValue("B{$secondHeaderRow}", "GRUPO");
                $sheet->setCellValue("C{$secondHeaderRow}", "SUBGRUPO");
                $sheet->setCellValue("D{$secondHeaderRow}", "CUENTA PRINCIPAL");
                $sheet->setCellValue("E{$secondHeaderRow}", "CUENTA ANALÍTICA");
                $sheet->setCellValue("G{$secondHeaderRow}", "DEBE");
                $sheet->setCellValue("H{$secondHeaderRow}", "HABER");
                $sheet->setCellValue("I{$secondHeaderRow}", "DEUDOR");
                $sheet->setCellValue("J{$secondHeaderRow}", "ACREEDOR");

                // Estilo encabezados
                $sheet->getStyle("A{$firstHeaderRow}:J{$secondHeaderRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2C3E50']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                $row++;
                $startDataRow = $row;

                // ========== DATOS ==========
                foreach ($this->datos as $dato) {
                    $sheet->fromArray([
                        $dato['clase'],
                        $dato['grupo'],
                        $dato['subgrupo'],
                        $dato['cuenta_principal'],
                        $dato['cuenta_analitica'],
                        $dato['nombre'],
                        $dato['debe_sumas'],
                        $dato['haber_sumas'],
                        $dato['saldo_deudor'],
                        $dato['saldo_acreedor'],
                    ], null, "A{$row}");
                    $row++;
                }
                $endDataRow = $row - 1;

                if ($endDataRow >= $startDataRow) {
                    $sheet->getStyle("A{$startDataRow}:J{$endDataRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
                    ]);
                    for ($i = $startDataRow; $i <= $endDataRow; $i++) {
                        $fillColor = ($i % 2 == 0) ? 'F8F9FA' : 'FFFFFF';
                        $sheet->getStyle("A{$i}:J{$i}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB($fillColor);
                    }
                    $sheet->getStyle("A{$startDataRow}:E{$endDataRow}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("G{$startDataRow}:J{$endDataRow}")->getAlignment()->setHorizontal('right');
                    $sheet->getStyle("G{$startDataRow}:J{$endDataRow}")->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                }

                // ========== TOTALES ==========
                $totalRow = $endDataRow + 2;

                $sheet->mergeCells("A{$totalRow}:F{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", "TOTALES");
                $sheet->setCellValue("G{$totalRow}", $this->datos->sum('debe_sumas'));
                $sheet->setCellValue("H{$totalRow}", $this->datos->sum('haber_sumas'));
                $sheet->setCellValue("I{$totalRow}", $this->datos->sum('saldo_deudor'));
                $sheet->setCellValue("J{$totalRow}", $this->datos->sum('saldo_acreedor'));

                $sheet->getStyle("A{$totalRow}:J{$totalRow}")->applyFromArray([
                    'font'    => ['bold' => true],
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E6E6E6']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->getStyle("G{$totalRow}:J{$totalRow}")->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $sheet->getStyle("G{$totalRow}:J{$totalRow}")->getAlignment()->setHorizontal('right');

                // ========== ANCHOS DE COLUMNA ==========
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(8);
                $sheet->getColumnDimension('C')->setWidth(10);
                $sheet->getColumnDimension('D')->setWidth(16);
                $sheet->getColumnDimension('E')->setWidth(16);
                $sheet->getColumnDimension('F')->setWidth(40);
                $sheet->getColumnDimension('G')->setWidth(15);
                $sheet->getColumnDimension('H')->setWidth(15);
                $sheet->getColumnDimension('I')->setWidth(15);
                $sheet->getColumnDimension('J')->setWidth(15);
            },
        ];
    }
}
