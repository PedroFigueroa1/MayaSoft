<?php

namespace App\Http\Controllers;

use App\Models\Cuentas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PlanCuentasImport;
use Inertia\Inertia;

class ImportarPlanCuentasController extends Controller
{
    public function index()
    {
        return Inertia::render('Cuentas/Importar');
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        try {
            DB::beginTransaction();

            // Pasar el userId al instanciar el import
            Excel::import(new PlanCuentasImport(auth()->id()), $request->file('archivo'));

            // Opción 2: Procesamiento manual (si prefieres)
            // $this->procesarArchivoManual($request->file('archivo'));

            DB::commit();

            return redirect()->back()->with('success', 'Plan de cuentas importado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }

    /**
     * Método alternativo para procesar manualmente
     */
    private function procesarArchivoManual($archivo)
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($archivo->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();

        $cuentasPrevias = [];

        foreach ($worksheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $datos = [];
            foreach ($cellIterator as $cell) {
                $datos[] = $cell->getValue();
            }

            // Asumiendo que tu Excel tiene columnas en este orden:
            // Nivel1, Nivel2, Nivel3, Nivel4, Nivel5, Nombre
            if (count($datos) >= 6) {
                $this->procesarFilaCuenta($datos, $cuentasPrevias);
            }
        }
    }

    private function procesarFilaCuenta($datos, &$cuentasPrevias)
    {
        $nivel1 = trim($datos[0] ?? '');
        $nivel2 = trim($datos[1] ?? '');
        $nivel3 = trim($datos[2] ?? '');
        $nivel4 = trim($datos[3] ?? '');
        $nivel5 = trim($datos[4] ?? '');
        $nombre = trim($datos[5] ?? '');

        // Construir código completo
        $codigoCompleto = $this->construirCodigo($nivel1, $nivel2, $nivel3, $nivel4, $nivel5);

        // Determinar nivel
        $nivel = $this->determinarNivel($nivel1, $nivel2, $nivel3, $nivel4, $nivel5);

        // Determinar cuenta padre
        $cuentaPadreId = $this->determinarPadre($codigoCompleto, $cuentasPrevias);

        // Crear cuenta
        $cuenta = Cuenta::create([
            'codigo' => $codigoCompleto,
            'nombre' => $nombre,
            'tipo' => Cuenta::determinarTipo($nivel1, $nombre),
            'nivel' => $nivel,
            'permite_movimiento' => Cuentas::determinarPermiteMovimiento($codigoCompleto),
            'cuenta_padre_id' => $cuentaPadreId,
            'descripcion' => null
        ]);

        // Guardar referencia para hijos
        $cuentasPrevias[$codigoCompleto] = $cuenta->id;
    }

    private function construirCodigo($n1, $n2, $n3, $n4, $n5)
    {
        $partes = array_filter([$n1, $n2, $n3, $n4, $n5], function($valor) {
            return !empty($valor) && $valor !== '00N' && $valor !== 'XXX';
        });

        return implode('.', $partes);
    }

    private function determinarNivel($n1, $n2, $n3, $n4, $n5)
    {
        $niveles = 0;
        if (!empty($n1)) $niveles++;
        if (!empty($n2)) $niveles++;
        if (!empty($n3)) $niveles++;
        if (!empty($n4)) $niveles++;
        if (!empty($n5)) $niveles++;

        return $niveles;
    }

    private function determinarPadre($codigoCompleto, $cuentasPrevias)
    {
        $partes = explode('.', $codigoCompleto);

        if (count($partes) <= 1) {
            return null;
        }

        // Remover último nivel para obtener padre
        array_pop($partes);
        $codigoPadre = implode('.', $partes);

        return $cuentasPrevias[$codigoPadre] ?? null;
    }
}
