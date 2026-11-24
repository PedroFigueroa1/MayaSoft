<?php

namespace App\Imports;

use App\Models\Cuentas;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\DB;

class PlanCuentasImport implements ToCollection, WithStartRow
{
    private $cuentasPorCodigo = [];

    public function startRow(): int
    {
        return 2; // Saltar encabezados
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            // Limpiar cache al inicio
            $this->cuentasPorCodigo = [];

            // Ordenar filas por nivel (de menor a mayor) para crear padres primero
            $filasOrdenadas = $this->ordenarFilasPorNivel($rows);

            foreach ($filasOrdenadas as $fila) {
                $this->procesarFila($fila);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function ordenarFilasPorNivel($rows)
    {
        return $rows->sortBy(function($fila) {
            return $this->determinarNivel(
                $fila[0] ?? '',
                $fila[1] ?? '',
                $fila[2] ?? '',
                $fila[3] ?? '',
                $fila[4] ?? ''
            );
        });
    }

    private function procesarFila($fila)
    {
        $nivel1 = trim($fila[0] ?? '');
        $nivel2 = trim($fila[1] ?? '');
        $nivel3 = trim($fila[2] ?? '');
        $nivel4 = trim($fila[3] ?? '');
        $nivel5 = trim($fila[4] ?? '');
        $nombre = trim($fila[5] ?? '');

        if (empty($nombre)) {
            return;
        }

        $codigo = $this->construirCodigo($nivel1, $nivel2, $nivel3, $nivel4, $nivel5);
        $nivel = $this->determinarNivel($nivel1, $nivel2, $nivel3, $nivel4, $nivel5);

        // Crear padres si no existen
        $this->crearPadresSiNoExisten($codigo, $nombre);

        // Determinar padre
        $padreId = $this->buscarPadrePorCodigo($codigo);

        // Si la cuenta ya existe, actualizarla
        if (isset($this->cuentasPorCodigo[$codigo])) {
            $cuenta = Cuentas::find($this->cuentasPorCodigo[$codigo]);
            if ($cuenta) {
                $cuenta->update([
                    'nombre' => $nombre,
                    'tipo' => $this->determinarTipo($nivel1, $nombre),
                    'nivel' => $nivel,
                    'permite_movimiento' => $this->permiteMovimiento($codigo),
                    'cuenta_padre_id' => $padreId,
                ]);
            }
            return;
        }

        // Crear nueva cuenta
        $cuenta = Cuentas::create([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'tipo' => $this->determinarTipo($nivel1, $nombre),
            'nivel' => $nivel,
            'permite_movimiento' => $this->permiteMovimiento($codigo),
            'cuenta_padre_id' => $padreId,
            'descripcion' => null
        ]);

        // Guardar en cache
        $this->cuentasPorCodigo[$codigo] = $cuenta->id;
    }

    private function construirCodigo($n1, $n2, $n3, $n4, $n5)
    {
        $partes = [];

        if (!empty($n1) && $n1 !== '00N' && $n1 !== 'XXX' && $n1 !== '000') {
            $partes[] = $n1;
        }
        if (!empty($n2) && $n2 !== '00N' && $n2 !== 'XXX' && $n2 !== '000') {
            $partes[] = $n2;
        }
        if (!empty($n3) && $n3 !== '00N' && $n3 !== 'XXX' && $n3 !== '000') {
            $partes[] = $n3;
        }
        if (!empty($n4) && $n4 !== '00N' && $n4 !== 'XXX' && $n4 !== '000') {
            $partes[] = $n4;
        }
        if (!empty($n5) && $n5 !== '00N' && $n5 !== 'XXX' && $n5 !== '000') {
            $partes[] = $n5;
        }

        return implode('.', $partes);
    }

    private function determinarNivel($n1, $n2, $n3, $n4, $n5)
    {
        $contador = 0;

        if (!empty($n1) && $n1 !== '00N' && $n1 !== 'XXX' && $n1 !== '000') $contador++;
        if (!empty($n2) && $n2 !== '00N' && $n2 !== 'XXX' && $n2 !== '000') $contador++;
        if (!empty($n3) && $n3 !== '00N' && $n3 !== 'XXX' && $n3 !== '000') $contador++;
        if (!empty($n4) && $n4 !== '00N' && $n4 !== 'XXX' && $n4 !== '000') $contador++;
        if (!empty($n5) && $n5 !== '00N' && $n5 !== 'XXX' && $n5 !== '000') $contador++;

        return $contador;
    }

    private function crearPadresSiNoExisten($codigoCompleto, $nombreHijo)
    {
        $partes = explode('.', $codigoCompleto);

        if (count($partes) <= 1) {
            return; // Es nivel 1, no tiene padre
        }

        // Construir códigos padres desde el nivel 1
        $codigoPadre = '';

        for ($i = 0; $i < count($partes) - 1; $i++) {
            if ($i === 0) {
                $codigoPadre = $partes[$i];
            } else {
                $codigoPadre .= '.' . $partes[$i];
            }

            // Si no existe, crear cuenta padre
            if (!isset($this->cuentasPorCodigo[$codigoPadre])) {
                $this->crearCuentaPadre($codigoPadre, $partes, $i);
            }
        }
    }

    private function crearCuentaPadre($codigoPadre, $partes, $nivelIndex)
    {
        // Buscar si ya existe en la base de datos
        $cuentaExistente = Cuentas::where('codigo', $codigoPadre)->first();

        if ($cuentaExistente) {
            $this->cuentasPorCodigo[$codigoPadre] = $cuentaExistente->id;
            return;
        }

        // Nombre genérico para el padre
        $nombresGenericos = [
            0 => 'ACTIVO',
            1 => 'ACTIVO CORRIENTE',
            2 => 'EFECTIVO Y EQUIVALENTES',
            3 => 'CAJA',
            4 => 'CUENTAS POR COBRAR',
            5 => 'PASIVO',
            6 => 'PASIVO CORRIENTE',
            7 => 'PATRIMONIO',
            8 => 'INGRESOS',
            9 => 'GASTOS',
        ];

        $nombrePadre = $nombresGenericos[$nivelIndex] ?? 'CUENTA ' . $codigoPadre;

        // Determinar padre del padre
        $padreDelPadreId = $this->buscarPadrePorCodigo($codigoPadre);

        $cuentaPadre = Cuentas::create([
            'codigo' => $codigoPadre,
            'nombre' => $nombrePadre,
            'tipo' => $this->determinarTipo($partes[0], $nombrePadre),
            'nivel' => $nivelIndex + 1,
            'permite_movimiento' => false, // Los padres intermedios no permiten movimiento
            'cuenta_padre_id' => $padreDelPadreId,
            'descripcion' => null
        ]);

        $this->cuentasPorCodigo[$codigoPadre] = $cuentaPadre->id;
    }

    private function buscarPadrePorCodigo($codigoHijo)
    {
        $partes = explode('.', $codigoHijo);

        if (count($partes) <= 1) {
            return null;
        }

        // Remover último nivel
        array_pop($partes);
        $codigoPadre = implode('.', $partes);

        // Buscar en cache primero
        if (isset($this->cuentasPorCodigo[$codigoPadre])) {
            return $this->cuentasPorCodigo[$codigoPadre];
        }

        // Buscar en base de datos
        $padre = Cuentas::where('codigo', $codigoPadre)->first();

        if ($padre) {
            $this->cuentasPorCodigo[$codigoPadre] = $padre->id;
            return $padre->id;
        }

        return null;
    }

    private function determinarTipo($nivel1, $nombre)
    {
        $nivel1 = (string)$nivel1;
        $nombreUpper = strtoupper(trim($nombre));

        // Por código del primer nivel
        if (str_starts_with($nivel1, '1')) return 'A';      // Activo
        if (str_starts_with($nivel1, '2')) return 'P';      // Pasivo
        if (str_starts_with($nivel1, '3')) return 'PN';     // Patrimonio Neto
        if (str_starts_with($nivel1, '4')) return 'I';      // Ingresos
        if (str_starts_with($nivel1, '5')) return 'G';      // Gastos
        if (str_starts_with($nivel1, '6')) return 'I';      // Ingresos (alternativo)
        if (str_starts_with($nivel1, '7')) return 'G';      // Gastos (alternativo)

        // Por nombre
        if (str_contains($nombreUpper, 'ACTIVO')) return 'A';
        if (str_contains($nombreUpper, 'PASIVO')) return 'P';
        if (str_contains($nombreUpper, 'PATRIMONIO')) return 'PN';
        if (str_contains($nombreUpper, 'INGRESO')) return 'I';
        if (str_contains($nombreUpper, 'GASTO')) return 'G';
        if (str_contains($nombreUpper, 'COSTO')) return 'G';
        if (str_contains($nombreUpper, 'EGRESO')) return 'G';

        // Por defecto según el primer dígito del código
        if (!empty($nivel1) && is_numeric($nivel1[0])) {
            $primerDigito = intval($nivel1[0]);
            switch ($primerDigito) {
                case 1: return 'A';
                case 2: return 'P';
                case 3: return 'PN';
                case 4:
                case 6: return 'I';
                case 5:
                case 7: return 'G';
            }
        }

        return 'A'; // Por defecto
    }

    private function permiteMovimiento($codigo)
    {
        $segmentos = explode('.', $codigo);

        // Solo las cuentas de último nivel permiten movimiento
        // Asumiendo que máximo es 5 niveles
        return count($segmentos) >= 5;

        // Alternativa: cuentas que no tengan hijos
        // Esto requeriría verificar después de crear todas
    }
}
