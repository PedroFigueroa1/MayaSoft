<?php

namespace App\Imports;

use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\UnidadMedida;
use App\Models\Cuentas;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class ProductosImport implements ToCollection, WithHeadingRow
{
    public int $creados = 0;
    public int $actualizados = 0;
    public int $errores = 0;
    public array $detalleErrores = [];

    protected array $categoriasCache = [];
    protected array $unidadesCache = [];
    protected array $cuentasCache = [];

    public function __construct()
    {
        $this->categoriasCache = CategoriaProducto::pluck('id', 'nombre')
            ->mapWithKeys(fn($id, $n) => [mb_strtolower(trim($n)) => $id])->toArray();
        $this->unidadesCache = UnidadMedida::get()
            ->mapWithKeys(fn($u) => [mb_strtolower(trim($u->codigo)) => $u->id])
            ->merge(UnidadMedida::pluck('id', 'nombre')->mapWithKeys(fn($id, $n) => [mb_strtolower(trim($n)) => $id]))
            ->toArray();
        $this->cuentasCache = Cuentas::pluck('id', 'codigo')
            ->mapWithKeys(fn($id, $c) => [mb_strtolower(trim($c)) => $id])->toArray();
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $i => $row) {
                $fila = $i + 2; // fila real en Excel

                try {
                    $descripcion = trim((string) ($row['descripcion'] ?? ''));
                    if ($descripcion === '') {
                        $this->errores++;
                        $this->detalleErrores[] = "Fila {$fila}: 'descripcion' es obligatoria.";
                        continue;
                    }

                    $tipo = strtolower(trim((string) ($row['tipo'] ?? 'mercaderia')));
                    if (!in_array($tipo, ['mercaderia','materia_prima','producto_terminado','servicio','activo_fijo'])) {
                        $this->errores++;
                        $this->detalleErrores[] = "Fila {$fila}: tipo '{$tipo}' inválido.";
                        continue;
                    }

                    $codigo = trim((string) ($row['codigo'] ?? '')) ?: $this->generarCodigo();

                    $data = [
                        'codigo'             => $codigo,
                        'codigo_barra'       => $this->str($row['codigo_barra'] ?? null),
                        'descripcion'        => $descripcion,
                        'descripcion_larga'  => $this->str($row['descripcion_larga'] ?? null),
                        'categoria_id'       => $this->resolverId($row['categoria'] ?? null, $this->categoriasCache),
                        'unidad_medida_id'   => $this->resolverId($row['unidad_medida'] ?? null, $this->unidadesCache),
                        'tipo'               => $tipo,
                        'precio_costo'       => $this->num($row['precio_costo'] ?? 0),
                        'precio_venta'       => $this->num($row['precio_venta'] ?? 0),
                        'precio_venta_2'     => $this->num($row['precio_venta_2'] ?? 0),
                        'precio_venta_3'     => $this->num($row['precio_venta_3'] ?? 0),
                        'aplica_iva'         => $this->bool($row['aplica_iva'] ?? 'SI'),
                        'porcentaje_iva'     => $this->num($row['porcentaje_iva'] ?? 13),
                        'metodo_costeo'      => strtoupper(trim((string) ($row['metodo_costeo'] ?? 'PROM'))) ?: 'PROM',
                        'stock_minimo'       => $this->num($row['stock_minimo'] ?? 0),
                        'stock_maximo'       => $this->num($row['stock_maximo'] ?? 0),
                        'controla_stock'     => $this->bool($row['controla_stock'] ?? 'SI'),
                        'codigo_sin'         => $this->str($row['codigo_sin'] ?? null),
                        'descripcion_sin'    => $this->str($row['descripcion_sin'] ?? null),
                        'activo'             => $this->bool($row['activo'] ?? 'SI'),
                        'cuenta_activo_id'        => $this->resolverId($row['cuenta_activo'] ?? null, $this->cuentasCache),
                        'cuenta_costo_venta_id'   => $this->resolverId($row['cuenta_costo_venta'] ?? null, $this->cuentasCache),
                        'cuenta_ingreso_id'       => $this->resolverId($row['cuenta_ingreso'] ?? null, $this->cuentasCache),
                        'cuenta_variacion_id'     => $this->resolverId($row['cuenta_variacion'] ?? null, $this->cuentasCache),
                    ];

                    $existente = Producto::withTrashed()->where('codigo', $codigo)->first();
                    if ($existente) {
                        $existente->restore();
                        $existente->update($data);
                        $this->actualizados++;
                    } else {
                        Producto::create($data);
                        $this->creados++;
                    }
                } catch (\Throwable $e) {
                    $this->errores++;
                    $this->detalleErrores[] = "Fila {$fila}: " . $e->getMessage();
                }
            }
        });
    }

    private function generarCodigo(): string
    {
        do {
            $codigo = 'P-' . strtoupper(\Illuminate\Support\Str::random(8));
        } while (Producto::withTrashed()->where('codigo', $codigo)->exists());
        return $codigo;
    }

    private function resolverId($valor, array $cache): ?int
    {
        if ($valor === null || trim((string) $valor) === '') return null;
        $key = mb_strtolower(trim((string) $valor));
        return $cache[$key] ?? null;
    }

    private function str($v): ?string
    {
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }

    private function num($v): float
    {
        $v = str_replace(',', '.', (string) $v);
        return is_numeric($v) ? (float) $v : 0.0;
    }

    private function bool($v): bool
    {
        $v = mb_strtolower(trim((string) $v));
        return in_array($v, ['si','sí','1','true','x','yes','y']);
    }
}