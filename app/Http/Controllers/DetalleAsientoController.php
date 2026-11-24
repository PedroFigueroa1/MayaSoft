<?php

namespace App\Http\Controllers;

use App\Models\Asiento;
use App\Models\DetalleAsiento;
use Illuminate\Http\Request;

class DetalleAsientoController extends Controller
{
    // Mostrar formulario para editar detalle
    public function edit(Asiento $asiento, DetalleAsiento $detalle)
    {
        if ($detalle->asiento_id !== $asiento->id) {
            abort(404);
        }

        $cuentas = Cuenta::where('estado', 'activo')->get();

        return view('asientos.detalles.edit', compact('asiento', 'detalle', 'cuentas'));
    }

    // Actualizar detalle de asiento
    public function update(Request $request, Asiento $asiento, DetalleAsiento $detalle)
    {
        if ($detalle->asiento_id !== $asiento->id) {
            abort(404);
        }

        $validated = $request->validate([
            'cuenta_id' => 'required|exists:cuentas,id',
            'debe' => 'required_without:haber|numeric|min:0',
            'haber' => 'required_without:debe|numeric|min:0',
            'descripcion' => 'nullable|max:255',
        ]);

        DB::beginTransaction();

        try {
            $detalle->update($validated);

            if (!$asiento->estaBalanceado()) {
                throw new \Exception('El asiento ya no está balanceado después de esta modificación');
            }

            DB::commit();

            return redirect()->route('asientos.show', $asiento)
                ->with('success', 'Movimiento actualizado correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar movimiento: ' . $e->getMessage());
        }
    }

    // Eliminar detalle de asiento
    public function destroy(Asiento $asiento, DetalleAsiento $detalle)
    {
        if ($detalle->asiento_id !== $asiento->id) {
            abort(404);
        }

        DB::beginTransaction();

        try {
            $detalle->delete();

            if (!$asiento->estaBalanceado()) {
                throw new \Exception('El asiento ya no está balanceado después de esta eliminación');
            }

            DB::commit();

            return redirect()->route('asientos.show', $asiento)
                ->with('success', 'Movimiento eliminado correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al eliminar movimiento: ' . $e->getMessage());
        }
    }

        // Versión alternativa si el procedimiento devuelve solo un resultset
    public function generarLibroMayorSimple(Request $request)
    {
        $request->validate([
            'cuenta_id' => 'required|exists:cuentas,id',
            'periodo_id' => 'required|exists:periodos,id',
            'fecha_inicio' => 'required|date',
            'fecha_cierre' => 'required|date|after_or_equal:fecha_inicio'
        ]);

        try {
            // Si el procedimiento solo devuelve el texto del libro mayor
            $resultados = DB::select('CALL probar_libro_mayor(?, ?, ?, ?)', [
                $request->periodo_id,
                $request->cuenta_id,
                $request->fecha_inicio,
                $request->fecha_cierre
            ]);

            $cuenta = DB::table('cuentas')->find($request->cuenta_id);
            $periodo = DB::table('periodos')->find($request->periodo_id);

            return response()->json([
                'success' => true,
                'cuenta' => $cuenta,
                'periodo' => $periodo,
                'libro_mayor' => $resultados[0]->resultado ?? $resultados[0]->libro_mayor ?? '',
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
