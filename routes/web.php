<?php
use Carbon\Carbon;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\CuentasController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\AsientoController;
use App\Http\Controllers\DetalleComprobanteController;
use App\Http\Controllers\TercerosController;
use App\Http\Controllers\EstadoFinancieroController;
use App\Http\Controllers\BalanceGeneralController;
use App\Http\Controllers\EstadoResultadosController;
use App\Http\Controllers\FlujoEfectivoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportarPlanCuentasController;
use App\Http\Controllers\CentroCostoController;
use App\Http\Controllers\SumasSaldosController;
use App\Http\Controllers\EvolucionPatrimonioController;
use App\Http\Controllers\LibroComprasController;
use App\Http\Controllers\LibroVentasController;

use Illuminate\Support\Facades\Http;

use App\Http\Controllers\ProductoController;
use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\AjusteInventarioController;
use App\Http\Controllers\TraspasoAlmacenController;
use App\Http\Controllers\SiatController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\CategoriaProductoController;
use App\Http\Controllers\ProveedorClienteController;
use App\Http\Controllers\MovimientoInventarioController;
use App\Http\Controllers\ConfiguracionSiatController;
use App\Http\Controllers\EmpresaSeleccionController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ReporteInventarioController;
use App\Http\Controllers\ProductoImportController;
use App\Http\Controllers\EstadoCuentaController;


//$fechaExpiracion = Carbon::create(2026, 8, 30);
Route::get('/probar-bcb', function () {

    $response = Http::get('https://bcb.cucu.bo/api/v1/tc');

    return $response->json();

});
//
//if (Carbon::now()->greaterThan($fechaExpiracion)) {
//    // Mostrar página de bloqueo con Inertia en TODAS las rutas
//    Route::get('/{any?}', function () {
//        return Inertia::render('Bloqueado');
//    })->where('any', '.*');
//} else {
    Route::get('/', function () {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    });

    Route::middleware([
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
    ])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    Route::middleware([
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
        'role:SUPERADMIN|ADMIN|CONTADOR',
    ])->group(function () {
        // Rutas de Empresas
        Route::get('/empresa', [EmpresaController::class, 'index'])->name('empresas.index');
        Route::get('/empresa/crear', [EmpresaController::class, 'create'])->name('empresas.create');
        Route::post('/empresa/store', [EmpresaController::class, 'store'])->name('empresas.store');
        Route::get('/empresa/{empresa}', [EmpresaController::class, 'show'])->name('empresas.show');
        Route::get('/empresa/{empresa}/editar', [EmpresaController::class, 'edit'])->name('empresas.edit');
        Route::put('/empresa/{empresa}', [EmpresaController::class, 'update'])->name('empresas.update');
        Route::delete('/empresa/{empresa}/destroy', [EmpresaController::class, 'destroy'])->name('empresas.destroy');
        Route::get('/imagenes/logo/{ruta}', [EmpresaController::class, 'logo'])->where('ruta', '.*');
        Route::get ('empresas/seleccionar', [EmpresaSeleccionController::class, 'seleccionar'])->name('empresas.seleccionar');
        Route::post('empresas/cambiar',     [EmpresaSeleccionController::class, 'cambiar'])->name('empresas.cambiar');

        // Rutas de Cuentas
        Route::get('/cuentas', [CuentasController::class, 'index'])->name('cuentas.index');
        Route::get('/cuentas/crear', [CuentasController::class, 'create'])->name('cuentas.create');
        Route::post('/cuentas/store', [CuentasController::class, 'store'])->name('cuentas.store');
        Route::get('/cuentas/{cuenta}/edit', [CuentasController::class, 'edit'])->name('cuentas.edit');
        Route::put('/cuentas/{cuentas}', [CuentasController::class, 'update'])->name('cuentas.update');
        Route::delete('/cuentas/{cuentas}', [CuentasController::class, 'destroy'])->name('cuentas.destroy');
        Route::get('/cuentas/show', [CuentasController::class, 'show'])->name('cuentas.show');

        // Rutas adicionales para cuentas
        Route::get('/cuentas/arbol', [CuentasController::class, 'arbol'])->name('cuentas.arbol');
        Route::get('/cuentas/autocomplete', [CuentasController::class, 'autocomplete'])->name('cuentas.autocomplete');

        // Rutas de Periodos
        Route::get('/empresa/{empresa}/periodos', [PeriodoController::class, 'index'])->name('periodos.index');
        Route::get('/periodos/create', [PeriodoController::class, 'create'])->name('periodos.create');
        Route::post('/periodos/store', [PeriodoController::class, 'store'])->name('periodos.store');
        Route::get('/periodos/{periodo}/edit', [PeriodoController::class, 'edit'])->name('periodos.edit');
        Route::put('/empresa/{empresa}/periodos/{periodo}/update', [PeriodoController::class, 'update'])->name('periodos.update');
        Route::get('/periodos/{periodo}', [PeriodoController::class, 'show'])->name('periodos.show');
        Route::get('/periodos/{periodo}/asientos', [PeriodoController::class, 'asientos'])->name('periodos.asientos');
        Route::get('/periodos/{periodo}/estadisticas', [PeriodoController::class, 'estadisticas'])->name('periodos.estadisticas');

        // Reportes de Periodos
        Route::get('/periodos/{periodo}/reportes/balance-comprobacion', [PeriodoController::class, 'balanceComprobacion'])->name('periodos.reportes.balance_comprobacion');
        Route::get('/periodos/{periodo}/reportes/estado-resultados', [PeriodoController::class, 'estadoResultados'])->name('periodos.reportes.estado_resultados');
        Route::get('/periodos/{periodo}/reportes/balance-general', [PeriodoController::class, 'balanceGeneral'])->name('periodos.reportes.balance_general');

        // Balance General
        Route::get('/balance-general/index', [BalanceGeneralController::class, 'index'])->name('balance-general.index');
        Route::get('/balance-general/generar/{periodo}', [BalanceGeneralController::class, 'generar'])->name('balance-general.generar');
        Route::post('/balance-general/exportar-pdf', [BalanceGeneralController::class, 'exportarPdf'])->name('balance-general.exportar-pdf');
        Route::post('/balance-general/exportar-excel/facil', [BalanceGeneralController::class, 'exportarExcel'])->name('balance-general.exportar-excel');
        Route::get('/balance-general/download-excel/{filename}', [BalanceGeneralController::class, 'downloadExcel'])->name('balance-general.download-excel');
        Route::get('/balance-general/download-pdf/{filename}', [BalanceGeneralController::class, 'downloadPdf'])->name('balance-general.download-pdf');

        // Comprobantes
        Route::get('comprobantes', [ComprobanteController::class, 'index'])->name('comprobantes.index');
        Route::get('comprobantes/create', [ComprobanteController::class, 'create'])->name('comprobantes.create');
        Route::post('comprobantes', [ComprobanteController::class, 'store'])->name('comprobantes.store');
        Route::get('comprobantes/{comprobante}/edit', [ComprobanteController::class, 'edit'])->name('comprobantes.edit');
        Route::put('comprobantes/{comprobante}', [ComprobanteController::class, 'update'])->name('comprobantes.update');

        // Asientos Contables
        Route::get('/asientos/{periodoId}', [AsientoController::class, 'index'])->name('asientos.index');
        Route::get('/asientos/create/{periodo}', [AsientoController::class, 'create'])->name('asientos.create');
        Route::post('/asientos', [AsientoController::class, 'store'])->name('asientos.store');
        Route::get('/asientos/show/{asiento}', [AsientoController::class, 'show'])->name('asientos.show');
        Route::get('/asientos/{asiento}/edit', [AsientoController::class, 'edit'])->name('asientos.edit');
        Route::put('/asientos/{asiento}', [AsientoController::class, 'update'])->name('asientos.update');
        Route::delete('/asientos/{asiento}', [AsientoController::class, 'destroy'])->name('asientos.destroy');
        //portar ventas y compras asientos
        Route::post('/periodos/{periodoId}/importar-libro', [AsientoController::class, 'importarArchivo'])->name('importar.libro');

        Route::get('/asientos/get-next-number', [AsientoController::class, 'getNextNumber'])->name('asientos.get-next-number');
        //exportables asiento pdf
        Route::get('/asientos/{asiento}/pdf', [AsientoController::class, 'downloadAsiento'])->name('asientos.pdf.download');
        Route::get('/asientos/{asiento}/preview', [AsientoController::class, 'previewAsiento'])->name('asientos.pdf.preview');
        // Libro Diario
        Route::get('/libro-diario/{periodoId}', [AsientoController::class, 'libroDiario'])->name('libro-diario.index');
        //sumas y saldos
        Route::get('/sumas-saldos/periodo/{periodoId}', [SumasSaldosController::class, 'index'])->name('sumas-saldos.index');
        Route::post('/sumas-saldos/periodo/{periodoId}/recalcular', [SumasSaldosController::class, 'recalcular'])->name('sumas-saldos.recalcular');
        Route::get('/sumas-saldos/periodo/{periodoId}/reporte', [SumasSaldosController::class, 'generarReporte'])->name('sumas-saldos.reporte');
        Route::get('/sumas-saldos/periodo/{periodoId}/excel', [SumasSaldosController::class, 'exportarExcel'])->name('sumas-saldos.excel');

        // Exportaciones del libro diario
        Route::get('/libro-diario/{periodo}/pdf', [AsientoController::class, 'exportarLibroDiarioPdf'])->name('libro-diario.pdf');
        Route::get('/libro-diario/{periodoId}/excel', [AsientoController::class, 'excel'])->name('libro-diario.excel');
        Route::get('/libro-diario/{periodo}/preview', [AsientoController::class, 'preview'])->name('libro-diario.preview');

        // Libro Mayor
        Route::get('/libro-mayor/{periodoId}', [AsientoController::class, 'indexMayor'])->name('libro-mayor.index');
        Route::post('/libro-mayor/generar', [AsientoController::class, 'generar'])->name('libro-mayor.generar');
        //Route::get('/libro-mayor/reporte', [LibroMayorController::class, 'showReporte'])->name('libro-mayor.reporte');
        Route::post('/libro-mayor/exportar-pdf', [AsientoController::class, 'exportarPdf'])->name('libro-mayor.exportar-pdf');
        Route::get('/libro-mayor/download-pdf/{filename}', [AsientoController::class, 'downloadPdf'])->name('libro-mayor.download-pdf');
        Route::get('/libro-mayor/prueva', [AsientoController::class, 'sebaasPerro'])->name('libro-mayor.prueva');
        //Route::get('/libro-diariooooo/pdf/{periodoId}', [AsientoController::class, 'exportarLibroDiarioPdf'])->name('libro-diario.pdf');

        // Estados Financieros
        Route::get('/estados-financieros', [EstadoFinancieroController::class, 'index'])->name('estados-financieros.index');
        Route::post('/estados-financieros/generar', [EstadoFinancieroController::class, 'generar'])->name('estados-financieros.generar');
        Route::get('/estados-financieros/{id}/download', [EstadoFinancieroController::class, 'descargar'])->name('estados-financieros.download');
        Route::delete('/estados-financieros/{id}', [EstadoFinancieroController::class, 'destroy'])->name('estados-financieros.destroy');

        // Estado de Resultados
        Route::get('/estado-resultados/{periodo}', [EstadoResultadosController::class, 'index'])->name('estado-resultados.index');
        //Route::post('/estado-resultados/generar/{periodo}', [EstadoResultadosController::class, 'generar'])->name('estado-resultados.generar');
        Route::post('/estado-resultados/exportar-pdf', [EstadoResultadosController::class, 'exportarPdf'])->name('estado-resultados.exportar-pdf');
        Route::get('/estado-resultados/download-pdf/{filename}', [EstadoResultadosController::class, 'downloadPdf'])->name('estado-resultados.download-pdf');
        //Route::post('/estado-resultados/exportar-excel/{periodo}', [EstadoResultadosController::class, 'exportarExcel'])->name('estado-resultados.exportar-excel');
        Route::get('/estado-resultados/download-excel/{filename}', [EstadoResultadosController::class, 'downloadExcel'])->name('estado-resultados.download-excel');

        Route::get('/estado-resultados/{periodo}', [EstadoResultadosController::class, 'index'])->name('estado-resultados.index');

        Route::post('/estado-resultados/{periodo}/generar', [EstadoResultadosController::class, 'generar'])->name('estado-resultados.generar');
        Route::post('/estado-resultados/exportar-pdf', [EstadoResultadosController::class, 'exportarPdf'])->name('estado-resultados.exportar-pdf');
        Route::post('/estado-resultados/{periodo}/exportar-excel', [EstadoResultadosController::class, 'exportarExcel'])->name('estado-resultados.exportar-excel');
        // Flujo de Efectivo
        Route::get('/flujo-efectivo', [FlujoEfectivoController::class, 'index'])->name('flujo-efectivo.index');
        Route::get('/flujo-efectivo/{periodo}', [FlujoEfectivoController::class, 'generar'])->name('flujo-efectivo.generar');
        Route::post('/flujo-efectivo/exportar-excel', [FlujoEfectivoController::class, 'exportarExcel'])->name('flujo-efectivo.exportar-excel');
        Route::get('/flujo-efectivo/download-excel/{filename}', [FlujoEfectivoController::class, 'downloadExcel'])->name('flujo-efectivo.download-excel');
        Route::post('/flujo-efectivo/exportar-pdf', [FlujoEfectivoController::class, 'exportarPdf'])->name('flujo-efectivo.exportar-pdf');
        Route::get('/flujo-efectivo/download-pdf/{filename}', [FlujoEfectivoController::class, 'downloadPdf'])->name('flujo-efectivo.download-pdf');
        // Evolución del Patrimonio
        Route::get('/evolucion-patrimonio/{periodo}', [EvolucionPatrimonioController::class, 'generar'])->name('evolucion-patrimonio.generar');
        Route::post('/evolucion-patrimonio/exportar-excel', [EvolucionPatrimonioController::class, 'exportarExcel'])->name('evolucion-patrimonio.exportar-excel');
        Route::get('/evolucion-patrimonio/download-excel/{filename}', [EvolucionPatrimonioController::class, 'downloadExcel'])->name('evolucion-patrimonio.download-excel');

        // Importar Plan de Cuentas
        Route::get('/importar/index', [ImportarPlanCuentasController::class, 'index'])->name('import');
        Route::post('/plan-cuentas/importar', [ImportarPlanCuentasController::class, 'importar'])->name('plan-cuentas.importar');
        //centro de costos
        Route::get('/centros-costo', [CentroCostoController::class, 'index'])->name('centros-costo.index');
        Route::post('/centros-costo', [CentroCostoController::class, 'store'])->name('centros-costo.store');
        Route::put('/centros-costo/{centroCosto}', [CentroCostoController::class, 'update'])->name('centros-costo.update');
        Route::delete('/centros-costo/{id}', [CentroCostoController::class, 'destroy'])->name('centros-costo.destroy');

        // ── LIBRO DE COMPRAS ────────────────────────────────────────
        Route::get('/compras-libro/importaciones/{periodoId}', [LibroComprasController::class, 'indexImportacionesCompras']);
        Route::get('/ventas-libro/importaciones/{periodoId}', [LibroVentasController::class, 'indexImportacionesVentas']);
        Route::get('/importaciones/{id}/libro-compras', [LibroComprasController::class, 'getLibroCompras'])->name('importaciones.libro-compras');
        Route::post('/periodos/{periodo}/libro-compras/eliminar-seleccionados', [LibroComprasController::class, 'eliminarSeleccionados'])->name('libro-compras.eliminar-seleccionados');
        Route::post('/periodos/{periodo}/libro-ventas/eliminar-seleccionados', [LibroVentasController::class, 'eliminarSeleccionados'])->name('libro-ventas.eliminar-seleccionados');
        // Eliminar una importación individual
        Route::delete('/importaciones/{id}', [LibroVentasController::class, 'destroy']);
        Route::get('/importaciones/{id}/libro-ventas', [LibroVentasController::class, 'getLibroVentas'])->name('importaciones.libro-ventas');
        Route::post('/periodos/libro-compras/{compraId}/contabilizar/mas', [LibroComprasController::class, 'contabilizar'])->name('libro-compras.contabilizar');
        Route::get('libro-compras/{libro}/inventario', [LibroComprasController::class, 'formInventario'])->name('libro-compras.inventario');
        Route::post('libro-compras/{libro}/ingresar-inventario', [LibroComprasController::class, 'ingresarInventario'])->name('libro-compras.ingresar-inventario');
        Route::prefix('periodos/{periodoId}/libro-compras')->name('libro-compras.')->group(function () {
            // Listado principal con filtros
            Route::get('/',[LibroComprasController::class, 'index'])->name('index');
            // Importar Excel del SIN
            Route::post('/importar',[LibroComprasController::class, 'importar'])->name('importar');
            // Actualizar cuentas de una fila (llamada inline por fila)
            Route::patch('/{compra}/cuentas', [LibroComprasController::class, 'actualizarCuentas'])->name('actualizar-cuentas');
            // Contabilizar una sola compra
            //Route::post('/{compra}/contabilizar', [LibroComprasController::class, 'contabilizar'])->name('contabilizar');
            // Contabilizar masivo (todos los pendientes con cuentas asignadas)
            Route::post('/contabilizar-masivo', [LibroComprasController::class, 'contabilizarMasivo'])->name('contabilizar-masivo');
            // Anular contabilización (elimina el asiento)
            Route::post('/{compra}/anular', [LibroComprasController::class, 'anular'])->name('anular');
            // Eliminar un registro pendiente
            //Route::delete('/{compra}', [LibroComprasController::class, 'eliminar'])->name('eliminar');
            // Eliminar toda la importación pendiente
            //Route::delete('/', [LibroComprasController::class, 'eliminarImportacion'])->name('eliminar-importacion');
        });


        Route::patch('/libro-compras/{compraId}/cuentas/jh', [LibroComprasController::class, 'actualizarCuentas'])->name('libro-compras.actualizar-cuentas');

        // ── LIBRO DE VENTAS ─────────────────────────────────────────
        // (estructura idéntica, remplazar LibroComprasController → LibroVentasController)

        Route::prefix('periodos/{periodoId}/libro-ventas')->name('libro-ventas.')->group(function () {
            Route::get('/', [LibroVentasController::class, 'index'])->name('index');
            Route::post('/importar', [LibroVentasController::class, 'importar'])->name('importar');
            Route::post('/contabilizar-masivo', [LibroVentasController::class, 'contabilizarMasivo'])->name('contabilizar-masivo');
            Route::post('/{ventaId}/anular', [LibroVentasController::class, 'anular'])->name('anular');
            Route::delete('/{ventaId}', [LibroVentasController::class, 'eliminar'])->name('eliminar');
            Route::delete('/', [LibroVentasController::class, 'eliminarImportacion'])->name('eliminar-importacion');
        });

        Route::get('//libro-ventas/{libro}/inventario', [LibroVentasController::class, 'formInventario'])->name('libro-ventas.inventario');
        Route::post('libro-ventas/{libro}/inventario', [LibroVentasController::class, 'ingresarInventario'])->name('libro-ventas.ingresar-inventario');
        Route::post('/libro-ventas/{ventaId}/contabilizar', [LibroVentasController::class, 'contabilizar'])->name('libro-ventas.contabilizar');
        Route::patch('/libro-ventas/{ventaId}/cuentas', [LibroVentasController::class, 'actualizarCuentas'])->name('libro-ventas.actualizar-cuentas');


        //Inventario-------------------------------------------------------------
        Route::post('/periodo/cambiar', [PeriodoController::class, 'cambiar'])->name('periodo.cambiar');
        Route::post('/entrada-proudctos/{periodoId}', [CompraController::class, 'store'])->name('entrada.store');
        Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
        Route::prefix('inventario')->name('inventario.')->group(function () {
            Route::get('periodos/{periodo}/reportes/kardex/{producto}', [ReporteInventarioController::class, 'kardexProducto'])->name('reportes.kardex.producto');
            Route::get('periodos/{periodo}/reportes/fisico-valorado', [ReporteInventarioController::class, 'fisicoValorado'])->name('reportes.fisico');

            // ── UNIDADES DE MEDIDA ───────────────────────────────────────────
            Route::resource('unidades-medida', UnidadMedidaController::class);
            Route::patch('unidades-medida/{unidad_medida}/toggle', [UnidadMedidaController::class, 'toggleActivo']);

            // ── CATEGORÍAS DE PRODUCTOS ──────────────────────────────────────
            Route::resource('categorias', CategoriaProductoController::class);
            Route::patch('categorias/{categoria_producto}/toggle', [CategoriaProductoController::class, 'toggleActivo']);

            // ── ALMACENES ────────────────────────────────────────────────────
            Route::resource('almacenes', AlmacenController::class);
            Route::patch('almacenes/{almacen}/toggle', [AlmacenController::class, 'toggleActivo']);
            Route::get('almacenes-select', [AlmacenController::class, 'select']);
            Route::resource('proveedores-clientes', ProveedorClienteController::class)->parameters(['proveedores-clientes' => 'proveedorCliente']);
            Route::post('/clientes/express', [ProveedorClienteController::class, 'storeExpress'])->name('clientes.store-express');
            Route::get('estado-cuenta/', [EstadoCuentaController::class, 'index'])->name('estado-cuenta.index');
            Route::get('estado-cuenta/{proveedorCliente}', [EstadoCuentaController::class, 'show'])->name('estado-cuenta.show');
            Route::post('estado-cuenta/{proveedorCliente}/cargo', [EstadoCuentaController::class, 'storeCargo'])->name('estado-cuenta.cargo');
            Route::post('estado-cuenta/{proveedorCliente}/abono', [EstadoCuentaController::class, 'storeAbono'])->name('estado-cuenta.abono');
            Route::delete('estado-cuenta/movimiento/{movimiento}', [EstadoCuentaController::class, 'destroyMovimiento'])->name('estado-cuenta.movimiento.destroy');

            // Próximamente:
            Route::resource('productos', ProductoController::class);
            Route::get('productos/importar/dos', [ProductoImportController::class, 'create'])->name('productos.import');
            Route::post('productos/importar', [ProductoImportController::class, 'store'])->name('productos.import.store');
            Route::get('productos/importar/plantilla', [ProductoImportController::class, 'plantilla'])->name('productos.import.plantilla');

            Route::get('productos/{producto}/kardex', [ProductoController::class, 'kardex'])->name('productos.kardex');
            Route::get('productos-reporte/stock-valorizado', [ProductoController::class, 'stockValorizado'])->name('productos.stock-valorizado');
            Route::get('productos-api/buscar-pos', [ProductoController::class, 'buscarPos'])->name('productos.buscar-pos');
            // Route::apiResource('stock-actual', StockActualController::class)->only(['index', 'show']);
            // Route::apiResource('movimientos', MovimientoInventarioController::class)->only(['index', 'show']);
            Route::get('compras/{periodoId}', [CompraController::class, 'index'])   ->name('compras.index');
            Route::get('compras/create/{periodoId}', [CompraController::class, 'create'])  ->name('compras.create');
            Route::get('compras/edit/{periodoId}', [CompraController::class, 'edit'])  ->name('compras.edit');
            //Route::post('compras/queesto', [CompraController::class, 'store'])   ->name('compras.store');
            Route::get('compras/{compra}/show', [CompraController::class, 'show'])->name('compras.show');
            Route::post('compras/{compra}/confirmar', [CompraController::class, 'confirmar'])->name('compras.confirmar');
            Route::post('compras/{compra}/contabilizar', [CompraController::class, 'contabilizar'])->name('compras.contabilizar');
            Route::post('compras/{compra}/anular', [CompraController::class, 'anular']);

            // ── MOVIMIENTOS DE INVENTARIO (Kardex + Entrada/Salida manual) ──

            Route::prefix('periodos/{periodoId}/movimientos')->name('movimientos.')->group(function () {
                // Kardex general del período
                Route::get('/kardex', [MovimientoInventarioController::class, 'kardex'])->name('kardex');
                // Entrada manual
                Route::get('/entrada/crear', [MovimientoInventarioController::class, 'createEntrada'])->name('entrada.create');
                Route::post('/entrada', [MovimientoInventarioController::class, 'storeEntrada'])->name('entrada.store');
                // Salida manual
                Route::get('/salida/crear', [MovimientoInventarioController::class, 'createSalida'])->name('salida.create');
                Route::post('/salida', [MovimientoInventarioController::class, 'storeSalida'])->name('salida.store');
                // Anular un movimiento
                Route::post('/{movimiento}/anular', [MovimientoInventarioController::class, 'anular'])->name('anular');
            });


            // API: stock de un producto en un almacén (llamada AJAX desde formularios)
            Route::get('/api/movimientos/stock-producto', [MovimientoInventarioController::class, 'stockProducto'])->name('movimientos.stock-producto');
            // VENTAS
            Route::get('/ventas/{periodoId}', [VentaController::class, 'index'])->name('ventas.index');
            Route::get('/ventas/{periodoId}/create', [VentaController::class, 'create'])->name('ventas.create');
            Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
            Route::get('/ventas/{periodoId}/edit', [VentaController::class, 'edit'])->name('ventas.edit');
            Route::get('/ventas/{periodoId}/{venta}', [VentaController::class, 'show'])->name('ventas.show');
            Route::post('/ventas/{periodoId}/{venta}/contabilizar', [VentaController::class, 'contabilizar'])->name('ventas.contabilizar');
            Route::post('/ventas/{periodoId}/contabilizar-masivo', [VentaController::class, 'contabilizarMasivo'])->name('ventas.contabilizar-masivo');
            Route::post('/ventas/{periodoId}/{venta}/anular', [VentaController::class, 'anular'])->name('ventas.anular');
            Route::post('/ventas/{periodoId}/{venta}/reenviar-siat', [VentaController::class, 'reenviarSiat'])->name('ventas.reenviar-siat');
            Route::get('/ventas/{periodoId}/{venta}/factura', [VentaController::class, 'imprimirFactura'])->name('ventas.factura');
            // Route::apiResource('ajustes', AjusteInventarioController::class);
            // Route::apiResource('traspasos', TraspasoAlmacenController::class);
            // Route::apiResource('cierres-caja', CierreCajaController::class);
            // Route::apiResource('config-siat', ConfiguracionSiatController::class);
            // Route::apiResource('cola-siat', ColaSiatController::class)->only(['index', 'show']);
            Route::prefix('configuracion/siat')->name('configuracion.siat.')->group(function () {
               Route::get('/', [ConfiguracionSiatController::class, 'index'])->name('index');
               Route::post('/', [ConfiguracionSiatController::class, 'guardar'])->name('guardar');
               Route::post('puntos', [ConfiguracionSiatController::class, 'guardarPunto'])->name('puntos.store');
               Route::put('puntos/{punto}', [ConfiguracionSiatController::class, 'guardarPunto'])->name('puntos.update');
               Route::delete('puntos/{punto}', [ConfiguracionSiatController::class, 'eliminarPunto'])->name('puntos.destroy');
               Route::post('puntos/{punto}/codigos', [ConfiguracionSiatController::class, 'renovarCodigos'])->name('puntos.codigos');
            });

        });
        Route::prefix('configuracion/siat')->name('configuracion.siat.')->group(function () {
            Route::get   ('/',                              [ConfiguracionSiatController::class, 'index'])          ->name('index');
            Route::post  ('/',                              [ConfiguracionSiatController::class, 'guardar'])        ->name('guardar');
            Route::post  ('probar',                         [ConfiguracionSiatController::class, 'probarConexion'])->name('probar');
            Route::post  ('puntos',                         [ConfiguracionSiatController::class, 'guardarPunto'])   ->name('puntos.store');
            Route::put   ('puntos/{punto}',                 [ConfiguracionSiatController::class, 'guardarPunto'])   ->name('puntos.update');
            Route::delete('puntos/{punto}',                 [ConfiguracionSiatController::class, 'eliminarPunto'])  ->name('puntos.destroy');
            Route::post  ('puntos/{punto}/codigos',         [ConfiguracionSiatController::class, 'renovarCodigos']) ->name('puntos.codigos');
        });



    });

    Route::middleware([
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
        'role:SUPERADMIN',
    ])->group(function () {
        // Users
        Route::get('/admin/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/admin/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/admin/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/admin/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/admin/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('admin.users.toggle-status');
        Route::post('/admin/users/{user}/assign-roles', [UserController::class, 'assignRoles'])->name('users.assign-roles');
        Route::post('/admin/users/{user}/assign-permissions', [UserController::class, 'assignPermissions'])->name('users.assign-permissions');

        // Roles
        Route::get('/admin/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/admin/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/admin/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/admin/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/admin/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/admin/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Permissions
        Route::get('/admin/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/admin/permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
        Route::post('/admin/permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::get('/admin/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::put('/admin/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('/admin/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
    });
//}
