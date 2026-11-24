<?php

namespace App\Imports;

use App\Models\Asiento;
use App\Models\DetalleAsiento;
use App\Models\Cuentas;
use App\Helpers\AsientoHelper;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Imports\VentasImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\LibroVenta;
use Carbon\Carbon;


class VentasImport implements ToCollection, WithHeadingRow
{
    protected $periodoId;
    protected $cantidad = 0;

    public function __construct($periodoId)
    {
        $this->periodoId = $periodoId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $fila) {
            // Saltar filas vacías o sin número de factura
            if (empty($fila['n_de_la_factura']) || empty($fila['importe_total_de_la_venta'])) {
                continue;
            }

            // Omitir facturas anuladas o con importe cero
            if (($fila['estado'] ?? '') === 'ANULADA' || floatval($fila['importe_total_de_la_venta']) == 0) {
                continue;
            }

            DB::transaction(function () use ($fila) {
                // 1. Obtener o mapear cuentas
                // Para ventas:
                //   - Cuenta de débito = generalmente "CAJA" o "BANCOS" (lo que viene en el Excel o por defecto)
                //   - Cuenta de haber (ingreso) = normalmente "VENTAS"
                //   - Cuenta de IVA = "DEBITO FISCAL IVA" (o similar)
                $cuentaDebeId = $this->getCuentaIdByNombre($fila['cuenta_debe'] ?? 'CAJA');
                $cuentaIvaId = $this->getCuentaIdByNombre('DEBITO FISCAL IVA');
                $cuentaHaberId = $this->getCuentaIdByNombre($fila['cuenta_haber'] ?? 'VENTAS');

                if (!$cuentaDebeId || !$cuentaIvaId || !$cuentaHaberId) {
                    throw new \Exception("No se encontró una de las cuentas necesarias para la factura {$fila['n_de_la_factura']}");
                }

                // 2. Extraer montos del Excel.
                //    En el archivo SIN, las columnas son:
                //    - IMPORTE TOTAL DE LA VENTA = total factura (debe)
                //    - DEBITO FISCAL = IVA
                //    - El neto (haber) = total - IVA (asumiendo que no hay descuentos adicionales)
                $importeDebe = floatval($fila['importe_total_de_la_venta'] ?? 0);
                $debitoFiscal = floatval($fila['debito_fiscal'] ?? 0);
                $importeHaber = $importeDebe - $debitoFiscal;

                // Validación opcional de balance
                if (abs(($importeDebe) - ($importeHaber + $debitoFiscal)) > 0.01) {
                    Log::warning("Venta desbalanceada factura {$fila['n_de_la_factura']}: Debe($importeDebe) vs Haber+IVA(" . ($importeHaber+$debitoFiscal) . ")");
                }

                // 3. Generar número de asiento automático (tipo ING)
                $tipo = 'ING';
                $numeroAsiento = AsientoHelper::generarNumeroAsiento($this->periodoId, $tipo);

                // 4. Convertir fecha (Excel a Y-m-d)
                $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fila['fecha_de_la_factura'])->format('Y-m-d');

                // 5. Crear el asiento
                $asiento = Asiento::create([
                    'periodo_id' => $this->periodoId,
                    'fecha' => $fecha,
                    'numero_asiento' => $numeroAsiento,
                    'tipo' => $tipo,
                    'glosa' => "Venta factura {$fila['n_de_la_factura']} - {$fila['nombre_o_razon_social']}",
                    'comprobante' => $fila['n_de_la_factura'],
                    'creado_por' => auth()->id(),
                ]);

                // 6. Detalle débito a Caja/Bancos (cuenta_debe)
                $asiento->detalles()->create([
                    'cuenta_id' => $cuentaDebeId,
                    'debe' => $importeDebe,
                    'haber' => 0,
                    'descripcion' => "Ingreso por venta",
                    'deducible' => false,
                ]);

                // 7. Detalle haber a Ventas (neto)
                $asiento->detalles()->create([
                    'cuenta_id' => $cuentaHaberId,
                    'debe' => 0,
                    'haber' => $importeHaber,
                    'descripcion' => "Valor neto de venta",
                    'deducible' => true, // Los ingresos pueden ser deducibles para IT según normativa
                ]);

                // 8. Detalle haber a Débito Fiscal IVA
                $asiento->detalles()->create([
                    'cuenta_id' => $cuentaIvaId,
                    'debe' => 0,
                    'haber' => $debitoFiscal,
                    'descripcion' => "Débito fiscal IVA 13%",
                    'deducible' => false,
                ]);

                $this->cantidad++;
            });
        }
    }

    /**
     * Mapea el nombre de la cuenta (como viene en el Excel o por defecto) al ID real en tu plan de cuentas.
     * Puedes personalizar el mapeo según tu nomenclatura.
     */
    private function getCuentaIdByNombre($nombreExcel)
    {
        $nombreExcel = trim($nombreExcel);
        // Mapeo de nombres típicos (ajústalo a tu plan de cuentas)
        $map = [
            'CAJA' => 'CAJA',
            'BANCOS' => 'BANCOS',
            'VENTAS' => 'VENTAS',
            'INGRESOS POR SERVICIOS' => 'INGRESOS POR SERVICIOS',
            'DEBITO FISCAL IVA' => 'DEBITO FISCAL IVA',
        ];

        $nombreBuscar = $map[$nombreExcel] ?? $nombreExcel;

        $cuenta = Cuentas::where('nombre', 'LIKE', "%{$nombreBuscar}%")
            ->orWhere('codigo', $nombreBuscar)
            ->first();

        if (!$cuenta) {
            throw new \Exception("Cuenta no encontrada para el nombre: $nombreExcel");
        }

        return $cuenta->id;
    }

    public function getCantidad()
    {
        return $this->cantidad;
    }
}
