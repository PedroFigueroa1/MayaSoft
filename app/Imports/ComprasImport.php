<?php

namespace App\Imports;

use App\Models\Asiento;
use App\Models\DetalleAsiento;
use App\Models\Cuentas;
use App\Models\Periodo;
use App\Helpers\AsientoHelper;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComprasImport implements ToCollection, WithHeadingRow
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
            if (empty($fila['numero_factura']) || empty($fila['importe_total_compra'])) {
                continue;
            }

            DB::transaction(function () use ($fila) {
                // 1. Obtener o mapear cuentas
                $cuentaDebeId = $this->getCuentaIdByNombre($fila['cuenta_debe']);
                $cuentaIvaId = $this->getCuentaIdByNombre('CREDITO FISCAL IVA'); // Debes tener esta cuenta en tu plan
                $cuentaHaberId = $this->getCuentaIdByNombre($fila['cuenta_haber']); // Normalmente "CAJA"

                if (!$cuentaDebeId || !$cuentaIvaId || !$cuentaHaberId) {
                    throw new \Exception("No se encontró una de las cuentas necesarias para la fila: " . json_encode($fila));
                }

                // 2. Calcular montos
                $importeDebe = floatval($fila['importe_debe'] ?? 0);
                $creditoFiscal = floatval($fila['credito_fiscal'] ?? 0);
                $importeHaber = floatval($fila['importe_haber'] ?? 0);

                // Validación de balance
                if (abs(($importeDebe + $creditoFiscal) - $importeHaber) > 0.01) {
                    Log::warning("Asiento desbalanceado para factura {$fila['numero_factura']}: Debe($importeDebe)+IVA($creditoFiscal) = " . ($importeDebe+$creditoFiscal) . " vs Haber($importeHaber)");
                    // Opcional: puedes ajustar el haber o lanzar excepción
                }

                // 3. Generar número de asiento automático (tipo EGR)
                $tipo = 'EGR'; // Egreso por compra
                $numeroAsiento = AsientoHelper::generarNumeroAsiento($this->periodoId, $tipo);

                // 4. Crear el asiento
                $asiento = Asiento::create([
                    'periodo_id' => $this->periodoId,
                    'fecha' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fila['fecha_de_factura_dui_dim'])->format('Y-m-d'),
                    'numero_asiento' => $numeroAsiento,
                    'tipo' => $tipo,
                    'glosa' => "Compra según factura {$fila['numero_factura']} - {$fila['razon_social_proveedor']}",
                    'comprobante' => $fila['numero_factura'],
                    'creado_por' => auth()->id(),
                ]);

                // 5. Detalle débito a cuenta operativa (mercadería, transporte, etc.)
                $asiento->detalles()->create([
                    'cuenta_id' => $cuentaDebeId,
                    'debe' => $importeDebe,
                    'haber' => 0,
                    'descripcion' => "Valor neto según factura",
                    'deducible' => true, // normalmente deducible para efectos de IT
                ]);

                // 6. Detalle débito a IVA Crédito Fiscal
                $asiento->detalles()->create([
                    'cuenta_id' => $cuentaIvaId,
                    'debe' => $creditoFiscal,
                    'haber' => 0,
                    'descripcion' => "Crédito fiscal IVA 13%",
                    'deducible' => false,
                ]);

                // 7. Detalle crédito a Caja (o cuenta haber)
                $asiento->detalles()->create([
                    'cuenta_id' => $cuentaHaberId,
                    'debe' => 0,
                    'haber' => $importeHaber,
                    'descripcion' => "Pago de factura",
                    'deducible' => false,
                ]);

                $this->cantidad++;
            });
        }
    }

    /**
     * Mapea el nombre de la cuenta (como viene en el Excel) al ID real en tu plan de cuentas.
     * Puedes usar coincidencia exacta o una tabla de mapeo personalizada.
     */
    private function getCuentaIdByNombre($nombreExcel)
    {
        $nombreExcel = trim($nombreExcel);
        // Mapeo manual de nombres típicos en el Libro de Compras a tu nomenclatura
        $map = [
            'TRANSPORTE' => 'TRANSPORTE Y FLETES',
            'ALMACEN DE MERCADERÍAS' => 'MERCADERÍAS',
            'MATERIALES DE ESCRITORIO' => 'MATERIALES DE OFICINA',
            'TRAMITES LEGALES' => 'GASTOS LEGALES',
            'OTROS GASTOS' => 'GASTOS VARIOS',
            'CAJA' => 'CAJA',
            'BANCOS' => 'BANCOS',
        ];

        $nombreBuscar = $map[$nombreExcel] ?? $nombreExcel;

        $cuenta = Cuentas::where('nombre', 'LIKE', "%{$nombreBuscar}%")
            ->orWhere('codigo', $nombreBuscar)
            ->first();

        if (!$cuenta) {
            // Puedes lanzar excepción o registrar error y detener importación
            throw new \Exception("Cuenta no encontrada para: $nombreExcel");
        }

        return $cuenta->id;
    }

    public function getCantidad()
    {
        return $this->cantidad;
    }
}
