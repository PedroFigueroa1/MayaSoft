<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\UnidadMedida;
use App\Models\Cuentas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductosImport;

class ProductoImportController extends Controller
{
    public function create()
    {
        return Inertia::render('Inventarios/Productos/Import', [
            'plantilla_url' => route('inventario.productos.import.plantilla'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new ProductosImport();
        Excel::import($import, $request->file('archivo'));

        return redirect()
            ->route('inventario.productos.index')
            ->with('success', "Importación finalizada: {$import->creados} creados, {$import->actualizados} actualizados, {$import->errores} errores.")
            ->with('import_errores', $import->detalleErrores); // 👈 array de errores
    }

    /** Genera la plantilla con encabezados + 1 fila ejemplo + validación de datos */
    public function plantilla()
    {
        $headers = [
            'codigo', 'codigo_barra', 'descripcion', 'descripcion_larga',
            'categoria', 'unidad_medida', 'tipo',
            'precio_costo', 'precio_venta', 'precio_venta_2', 'precio_venta_3',
            'aplica_iva', 'porcentaje_iva', 'metodo_costeo',
            'stock_minimo', 'stock_maximo', 'controla_stock',
            'codigo_sin', 'descripcion_sin', 'activo',
            'cuenta_activo', 'cuenta_costo_venta', 'cuenta_ingreso', 'cuenta_variacion',
        ];

        $ejemplo = [
            'PROD-001', '7891234567890', 'Coca Cola 2L', 'Gaseosa Coca Cola 2 litros',
            'Bebidas', 'UNID', 'mercaderia',
            5.50, 8.00, 7.50, 7.00,
            'SI', 13, 'PROM',
            5, 100, 'SI',
            '123456', 'Coca Cola 2L', 'SI',
            '1.1.4.01', '5.1.1.01', '4.1.1.01', '5.1.1.02',
        ];

        return Excel::download(
            new \App\Exports\ProductosPlantillaExport($headers, $ejemplo),
            'plantilla_productos.xlsx'
        );
    }
}