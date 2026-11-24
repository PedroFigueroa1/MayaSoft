<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LibroDiarioExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $collection = collect();

        // Encabezado del reporte
        $collection->push([
            'EMPRESA: ' . $this->data['empresa']->nombre,
            '', '', '', '', '', '', '', '', ''
        ]);

        $collection->push([
            'NIT: ' . $this->data['empresa']->nit,
            '', '', '', '', '', '', '', '', ''
        ]);

        $collection->push([
            'LIBRO DIARIO - PERIODO: ' . $this->data['periodo']->nombre,
            '', '', '', '', '', '', '', '', ''
        ]);

        $collection->push([
            'FECHA GENERACIÓN: ' . $this->data['fechaGeneracion'],
            '', '', '', '', '', '', '', '', ''
        ]);

        // Espacio en blanco
        $collection->push(['', '', '', '', '', '', '', '', '', '']);

        // Encabezados de tabla (10 columnas)
        $collection->push([
            'Fecha',
            'N° Asiento',
            'Tipo',
            'Código Cuenta',
            'Nombre Cuenta',
            'Centro Costo',
            'D/ND',
            'Descripción',
            'Debe',
            'Haber'
        ]);

        // Datos
        foreach ($this->data['asientos'] as $asiento) {
            foreach ($asiento->detalles as $detalle) {
                $collection->push([
                    $this->data['formatDate']($asiento->fecha),
                    $asiento->numero_asiento,
                    $this->data['tipoText']($asiento->tipo),
                    $detalle->cuenta->codigo ?? '',
                    $detalle->cuenta->nombre ?? '',
                    $detalle->centro_costo ?
                        "{$detalle->centro_costo->codigo} - {$detalle->centro_costo->nombre}" : '',
                    $detalle->deducible ? 'ND' : 'D',
                    $detalle->descripcion ?: $asiento->glosa,
                    $detalle->debe > 0 ? number_format($detalle->debe, 2, '.', '') : '',
                    $detalle->haber > 0 ? number_format($detalle->haber, 2, '.', '') : ''
                ]);
            }
        }

        // Espacio antes de totales
        $collection->push(['', '', '', '', '', '', '', '', '', '']);

        // Totales
        $collection->push([
            'TOTALES',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            number_format($this->data['totalDebe'], 2, '.', ''),
            number_format($this->data['totalHaber'], 2, '.', '')
        ]);

        return $collection;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilos generales
        $sheet->getStyle('A1:J1000')->getFont()->setName('Arial')->setSize(10);

        // Encabezado de empresa
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getFont()->setSize(10);
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A4')->getFont()->setSize(9);

        // Encabezados de tabla (fila 6)
        $sheet->getStyle('A6:J6')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F81BD']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true]
        ]);

        // Calcular última fila de datos
        $totalDetalles = 0;
        foreach ($this->data['asientos'] as $asiento) {
            $totalDetalles += $asiento->detalles->count();
        }
        $lastDataRow = 6 + $totalDetalles;
        $totalRow = $lastDataRow + 2;

        // Bordes para la tabla
        $sheet->getStyle("A6:J{$lastDataRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Alternar colores en filas de datos
        for ($row = 7; $row <= $lastDataRow; $row++) {
            if ($row % 2 == 0) {
                $sheet->getStyle("A{$row}:J{$row}")
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F2F2F2');
            }
        }

        // Alineación de columnas específicas
        // Fecha, N° Asiento, Tipo, D/ND - Centrado
        $sheet->getStyle("A7:A{$lastDataRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("B7:B{$lastDataRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("C7:C{$lastDataRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("G7:G{$lastDataRow}")->getAlignment()->setHorizontal('center');

        // Descripción - Izquierda
        $sheet->getStyle("H7:H{$lastDataRow}")->getAlignment()->setHorizontal('left');

        // Montos - Derecha
        $sheet->getStyle("I7:I{$lastDataRow}")->getAlignment()->setHorizontal('right');
        $sheet->getStyle("J7:J{$lastDataRow}")->getAlignment()->setHorizontal('right');

        // Formato numérico para montos
        $sheet->getStyle("I7:I{$lastDataRow}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
        $sheet->getStyle("J7:J{$lastDataRow}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        // Estilo para fila de totales
        $sheet->getStyle("A{$totalRow}:J{$totalRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']]
        ]);
        $sheet->getStyle("I{$totalRow}:J{$totalRow}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        // Unir celdas para el texto "TOTALES" (columnas A-H)
        $sheet->mergeCells("A{$totalRow}:H{$totalRow}");
        $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal('right');

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, // Fecha
            'B' => 12, // N° Asiento
            'C' => 12, // Tipo
            'D' => 15, // Código Cuenta
            'E' => 35, // Nombre Cuenta
            'F' => 25, // Centro Costo
            'G' => 8,  // D/ND (Deducible/No Deducible)
            'H' => 40, // Descripción
            'I' => 15, // Debe
            'J' => 15, // Haber
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Autoajustar altura de filas
                $event->sheet->getDelegate()->getDefaultRowDimension()->setRowHeight(20);

                // Autoajustar altura de la fila de encabezados
                $event->sheet->getDelegate()->getRowDimension(6)->setRowHeight(30);

                // Congelar paneles (fijar encabezados)
                $event->sheet->getDelegate()->freezePane('A7');
            },
        ];
    }
}
