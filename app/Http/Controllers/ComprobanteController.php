<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\DetalleComprobante;
use App\Models\Asiento;
use App\Models\DetalleAsiento;
use App\Models\Periodo;
use App\Models\Cuentas;
use App\Models\Empresa;
use App\Models\Terceros;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ComprobanteController extends Controller
{
    public function index(Request $request)
    {
        $comprobantes = Comprobante::with(['periodo', 'emisor', 'receptor', 'asiento'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where('numero', 'like', "%{$search}%")
                    ->orWhere('tipo', 'like', "%{$search}%");
            })
            ->orderBy('fecha_emision', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Comprobantes/Index', [
            'comprobantes' => $comprobantes,
            'filters' => $request->only(['search']),
            'tipos' => Comprobante::TIPOS,
            'estados' => Comprobante::ESTADOS_SIN,
            'can' => [
                'create' => Auth::user()->can('create', Comprobante::class),
                'edit' => Auth::user()->can('update', Comprobante::class),
            ]
        ]);
    }

    public function create()
    {
        $periodos = Periodo::where('estado', 'A')->get();
        $empresas = Empresa::all();
        $terceros = Terceros::all();

        return Inertia::render('Comprobantes/Form', [
            'tipos' => Comprobante::TIPOS,
            'periodos' => $periodos,
            'empresas' => $empresas,
            'terceros' => $terceros,
            'unidades_medida' => DetalleComprobante::UNIDADES_MEDIDA,
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validar comprobante principal
            $validated = $request->validate([
                'periodo_id' => 'required|exists:periodos,id',
                'tipo' => 'required|in:FAC,FAC_E,REC,ND,NC,LQD,CON,ORD,PAG,AJT',
                'numero' => 'required|string|max:50',
                'fecha_emision' => 'required|date',
                'fecha_limite_emision' => 'nullable|date',
                'lugar_emision' => 'required|string|max:100',
                'numero_autorizacion' => 'nullable|string|max:100',
                'codigo_control' => 'nullable|string|max:100',
                'qr' => 'nullable|string',
                'emisor_id' => 'required|exists:empresas,id',
                'receptor_id' => 'required|exists:terceros,id',
                'subtotal' => 'required|numeric|min:0',
                'descuento' => 'required|numeric|min:0',
                'importe_base' => 'required|numeric|min:0',
                'importe_ice' => 'required|numeric|min:0',
                'importe_exento' => 'required|numeric|min:0',
                'importe_iva' => 'required|numeric|min:0',
                'importe_it' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'leyenda' => 'required|string',
                'archivo_pdf' => 'required|string',
                'archivo_xml' => 'nullable|string',
                'estado_sin' => 'nullable|in:NO_ENVIADO,PENDIENTE,VALIDADO,ANULADO,ERROR',
                'detalles' => 'required|array|min:1',
                'detalles.*.producto_servicio' => 'required|string|max:255',
                'detalles.*.cantidad' => 'required|numeric|min:0',
                'detalles.*.unidad_medida' => 'required|in:UN,KG,M,L,GL,OT',
                'detalles.*.precio_unitario' => 'required|numeric|min:0',
                'detalles.*.tasa_iva' => 'required|numeric|min:0|max:100',
                'detalles.*.tasa_it' => 'required|numeric|min:0|max:100',
                'detalles.*.tasa_ice' => 'required|numeric|min:0|max:100',
                'detalles.*.descuento' => 'required|numeric|min:0',
                'detalles.*.subtotal' => 'required|numeric|min:0',
                'detalles.*.importe_iva' => 'required|numeric|min:0',
                'detalles.*.importe_it' => 'required|numeric|min:0',
                'detalles.*.importe_ice' => 'required|numeric|min:0',
                'detalles.*.total' => 'required|numeric|min:0',
            ]);

            // Crear comprobante
            $validated['creado_por'] = Auth::id();
            $comprobante = Comprobante::create($validated);

            // Crear detalles
            foreach ($request->detalles as $detalle) {
                $comprobante->detalles()->create($detalle);
            }

            // Generar asiento contable automáticamente
            $asiento = $this->generarAsientoContable($comprobante);

            // Vincular asiento al comprobante
            $comprobante->asiento_id = $asiento->id;
            $comprobante->save();

            DB::commit();

            return redirect()->route('comprobantes.index')
                ->with('success', 'Comprobante registrado con éxito. Asiento contable generado.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar comprobante: ' . $e->getMessage());
        }
    }

    private function generarAsientoContable(Comprobante $comprobante)
    {
        // Obtener el último número de asiento del periodo
        $ultimoAsiento = Asiento::where('periodo_id', $comprobante->periodo_id)
            ->orderBy('numero_asiento', 'desc')
            ->first();

        $numeroAsiento = $ultimoAsiento ?
            (int)$ultimoAsiento->numero_asiento + 1 :
            1;

        // Crear el asiento
        $asiento = Asiento::create([
            'periodo_id' => $comprobante->periodo_id,
            'fecha' => $comprobante->fecha_emision,
            'numero_asiento' => str_pad($numeroAsiento, 7, '0', STR_PAD_LEFT),
            'tipo' => $this->mapTipoAsiento($comprobante->tipo),
            'glosa' => 'Comprobante: ' . $comprobante->tipo . ' ' . $comprobante->numero,
            'comprobante' => $comprobante->tipo . '-' . $comprobante->numero,
            'creado_por' => Auth::id(),
        ]);

        // Generar detalles del asiento
        $this->generarDetallesAsiento($asiento, $comprobante);

        return $asiento;
    }

    private function mapTipoAsiento($tipoComprobante)
    {
        $map = [
            'FAC' => 'ING',   // Factura -> Ingreso
            'FAC_E' => 'EGR', // Factura Electrónica -> Egreso
            'REC' => 'ING',   // Recibo -> Ingreso
            'NC' => 'AJT',    // Nota Crédito -> Ajuste
            'ND' => 'AJT',     // Nota Débito -> Ajuste
            'PAG' => 'EGR',    // Pago -> Egreso
            'AJT' => 'AJT',    // Ajuste
        ];

        return $map[$tipoComprobante] ?? 'TRN';
    }

    private function generarDetallesAsiento(Asiento $asiento, Comprobante $comprobante)
    {
        $detalles = [];

        // 1. Registro principal (cuentas por cobrar/pagar)
        $detalles[] = [
            'cuenta_id' => $this->getCuentaContable($comprobante, 'principal'),
            'debe' => in_array($comprobante->tipo, ['FAC', 'REC']) ? $comprobante->total : 0,
            'haber' => in_array($comprobante->tipo, ['FAC_E', 'PAG']) ? $comprobante->total : 0,
            'descripcion' => 'Registro principal comprobante'
        ];

        // 2. Registro de ingresos/egresos
        $detalles[] = [
            'cuenta_id' => $this->getCuentaContable($comprobante, 'ingresos'),
            'debe' => in_array($comprobante->tipo, ['FAC_E', 'PAG']) ? $comprobante->importe_base : 0,
            'haber' => in_array($comprobante->tipo, ['FAC', 'REC']) ? $comprobante->importe_base : 0,
            'descripcion' => 'Base imponible'
        ];

        // 3. Registro de impuestos (IVA)
        if ($comprobante->importe_iva > 0) {
            $detalles[] = [
                'cuenta_id' => $this->getCuentaContable($comprobante, 'iva'),
                'debe' => in_array($comprobante->tipo, ['FAC_E', 'PAG']) ? $comprobante->importe_iva : 0,
                'haber' => in_array($comprobante->tipo, ['FAC', 'REC']) ? $comprobante->importe_iva : 0,
                'descripcion' => 'IVA ' . $comprobante->detalles->first()->tasa_iva . '%'
            ];
        }

        // 4. Registro de otros impuestos (IT)
        if ($comprobante->importe_it > 0) {
            $detalles[] = [
                'cuenta_id' => $this->getCuentaContable($comprobante, 'it'),
                'debe' => in_array($comprobante->tipo, ['FAC_E', 'PAG']) ? $comprobante->importe_it : 0,
                'haber' => in_array($comprobante->tipo, ['FAC', 'REC']) ? $comprobante->importe_it : 0,
                'descripcion' => 'IT ' . $comprobante->detalles->first()->tasa_it . '%'
            ];
        }

        // 5. Registro de ICE
        if ($comprobante->importe_ice > 0) {
            $detalles[] = [
                'cuenta_id' => $this->getCuentaContable($comprobante, 'ice'),
                'debe' => in_array($comprobante->tipo, ['FAC_E', 'PAG']) ? $comprobante->importe_ice : 0,
                'haber' => in_array($comprobante->tipo, ['FAC', 'REC']) ? $comprobante->importe_ice : 0,
                'descripcion' => 'ICE'
            ];
        }

        // Validar que el asiento esté balanceado
        $totalDebe = array_sum(array_column($detalles, 'debe'));
        $totalHaber = array_sum(array_column($detalles, 'haber'));

        if (abs($totalDebe - $totalHaber) > 0.01) {
            throw new \Exception('Asiento no balanceado. Diferencia: ' . ($totalDebe - $totalHaber));
        }

        // Guardar detalles del asiento
        foreach ($detalles as $detalle) {
            $asiento->detalles()->create($detalle);
        }
    }

    private function getCuentaContable(Comprobante $comprobante, $tipo)
    {
        // En un sistema real, estas cuentas deberían venir de configuración
        $cuentas = [
            'principal' => [
                'FAC' => 110101, // Cuentas por cobrar
                'FAC_E' => 210101, // Cuentas por pagar
                'PAG' => 210101, // Cuentas por pagar
                'REC' => 110101 // Cuentas por cobrar
            ],
            'ingresos' => 410101, // Ventas
            'iva' => 220101, // IVA
            'it' => 220102, // IT
            'ice' => 220103, // ICE
        ];

        if ($tipo === 'principal') {
            return $cuentas[$tipo][$comprobante->tipo] ?? 110101;
        }

        return $cuentas[$tipo] ?? 999999; // Cuenta genérica en caso de error
    }
}
