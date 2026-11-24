<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\ConfiguracionSiat;
use App\Models\Empresa;
use App\Models\SiatPuntoVenta;
use App\Models\User;
use App\Services\SiatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ConfiguracionSiatController extends Controller
{
    private function autorizar(): void
    {
        Gate::authorize('configurar-siat');
    }

    // ══════════════════════════════════════════════════════════════
    //  INDEX — toda la pantalla en una sola carga
    // ══════════════════════════════════════════════════════════════
    public function index()
    {
        //$this->autorizar();

        $empresaId = session('empresa_id');

        // Config activa priorizando producción (1) sobre piloto (2)
        $config = ConfiguracionSiat::query()
            ->where('empresa_id', $empresaId)
            ->orderByRaw("FIELD(cod_ambiente, '1', '2')")
            ->orderByDesc('activo')
            ->first();

        $empresa = Empresa::findOrFail($empresaId);

        return Inertia::render('Inventarios/Configuracion/Siat', [
            'empresa' => $empresa->only(['id', 'nit', 'razon_social']),

            // token_delegado está en $hidden → sólo enviamos el flag
            'config' => $config ? array_merge($config->toArray(), [
                'tiene_token' => !empty($config->getRawOriginal('token_delegado')),
            ]) : null,

            'puntos' => $config
                ? SiatPuntoVenta::with('usuarios:id,name')
                    ->where('configuracion_siat_id', $config->id)
                    ->orderBy('cod_sucursal')
                    ->orderBy('cod_punto_venta')
                    ->get()
                    ->map(fn($p) => array_merge($p->toArray(), [
                        'cufd_vigente' => $p->cufdVigente(),
                        'cuis_vigente' => $p->cuisVigente(),
                        'usuarios_ids' => $p->usuarios->pluck('id'),
                    ]))
                : [],

            'usuarios' => User::whereHas('empresas',
                    fn($q) => $q->where('empresas.id', $empresaId))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  GUARDAR CREDENCIALES
    // ══════════════════════════════════════════════════════════════
public function guardar(Request $request)
{
    //$this->autorizar();

    // ── Resolver empresa activa (a prueba de balas) ──
    $empresaId = session('empresa_id')
        ?? auth()->user()?->empresas()->value('empresas.id')
        ?? Empresa::query()->value('id');

    if (!$empresaId) {
        return back()->withErrors([
            'error' => 'No hay empresa activa. Crea una empresa o inicia sesión con un usuario asignado.'
        ]);
    }

    // Fija la sesión para futuros requests
    session(['empresa_id' => (int) $empresaId]);
    session()->save();   // ← fuerza el guardado inmediato

    $existente = ConfiguracionSiat::query()
        ->where('empresa_id', $empresaId)
        ->where('cod_ambiente', $request->cod_ambiente)
        ->first();

    $data = $request->validate([
        'cod_ambiente'     => 'required|in:1,2',
        'cod_modalidad'    => 'required|in:1,2,3',
        'cod_tipo_factura' => 'required|string|max:5',
        'cod_sistema'      => 'nullable|string|max:100',
        'url_api'          => 'nullable|url|max:200',
        'token_expira_at'  => 'nullable|date',
        'token_delegado'   => [$existente ? 'nullable' : 'required', 'string', 'max:2000'],
    ]);

    DB::transaction(function () use ($data, $existente, $empresaId) {
        // ⚠️ CLAVE: usar updateOrCreate / asignar empresa_id SIN fill()
        if ($existente) {
            $cfg = $existente;
            $cfg->cod_modalidad    = $data['cod_modalidad'];
            $cfg->cod_tipo_factura = $data['cod_tipo_factura'];
            $cfg->cod_sistema      = $data['cod_sistema'] ?? null;
            $cfg->url_api          = $data['url_api'] ?: ($data['cod_ambiente'] === '1'
                ? 'https://siat.impuestos.gob.bo/'
                : 'https://pilotosiat.impuestos.gob.bo/');
            $cfg->token_expira_at  = $data['token_expira_at'] ?? null;
            if (!empty($data['token_delegado'])) {
                $cfg->token_delegado = trim($data['token_delegado']);
            }
            $cfg->activo = true;
            $cfg->save();
        } else {
            // ── CREAR: asignación directa, sin mass assignment ──
            $cfg = new ConfiguracionSiat();
            $cfg->empresa_id       = (int) $empresaId;   // ⬅️ ASIGNACIÓN DIRECTA
            $cfg->cod_ambiente     = $data['cod_ambiente'];
            $cfg->cod_modalidad    = $data['cod_modalidad'];
            $cfg->cod_tipo_factura = $data['cod_tipo_factura'];
            $cfg->cod_sistema      = $data['cod_sistema'] ?? null;
            $cfg->url_api          = $data['url_api'] ?: ($data['cod_ambiente'] === '1'
                ? 'https://siat.impuestos.gob.bo/'
                : 'https://pilotosiat.impuestos.gob.bo/');
            $cfg->token_expira_at  = $data['token_expira_at'] ?? null;
            $cfg->token_delegado   = trim($data['token_delegado']);
            $cfg->activo           = true;
            $cfg->save();
        }

        ConfiguracionSiat::where('empresa_id', $empresaId)
            ->where('id', '!=', $cfg->id)
            ->update(['activo' => false]);
    });

    return back()->with('success', 'Credenciales SIAT guardadas.');
}

    // ══════════════════════════════════════════════════════════════
    //  PUNTOS DE VENTA
    // ══════════════════════════════════════════════════════════════
    public function guardarPunto(Request $request, ?SiatPuntoVenta $punto = null)
    {
        //$this->autorizar();
        $empresaId = session('empresa_id');

        $config = ConfiguracionSiat::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->first();

        if (!$config) {
            return back()->withErrors(['error' => 'Primero guarda las credenciales de la empresa.']);
        }

        // Si es update, verificar que el punto pertenece a la empresa activa
        if ($punto?->exists && $punto->empresa_id !== $empresaId) {
            abort(403);
        }

        $data = $request->validate([
            'nombre'          => 'required|string|max:100',
            'cod_sucursal'    => 'required|integer|min:0|max:9999',
            'cod_punto_venta' => [
                'required', 'integer', 'min:0', 'max:9999',
                Rule::unique('siat_puntos_venta')
                    ->where(fn($q) => $q
                        ->where('configuracion_siat_id', $config->id)
                        ->where('cod_sucursal', $request->cod_sucursal))
                    ->ignore($punto?->id),
            ],
            'direccion'       => 'nullable|string|max:250',
            'municipio'       => 'nullable|string|max:100',
            'telefono'        => 'nullable|string|max:30',
            'activo'          => 'boolean',
            'usuarios'        => 'array',
            'usuarios.*.id'   => 'exists:users,id',
            'usuarios.*.por_defecto' => 'boolean',
        ]);

        DB::transaction(function () use ($data, $config, $empresaId, $punto) {
            $punto ??= new SiatPuntoVenta([
                'empresa_id'            => $empresaId,
                'configuracion_siat_id' => $config->id,
            ]);

            $punto->fill(collect($data)->except('usuarios')->all())->save();

            $pivot = collect($data['usuarios'] ?? [])
                ->mapWithKeys(fn($u) => [$u['id'] => ['por_defecto' => (bool)($u['por_defecto'] ?? false)]])
                ->all();

            $punto->usuarios()->sync($pivot);
        });

        return back()->with('success', 'Punto de venta guardado.');
    }

    public function eliminarPunto(SiatPuntoVenta $punto)
    {
        //$this->autorizar();

        if ($punto->empresa_id !== session('empresa_id')) {
            abort(403);
        }

        if ($punto->correlativo_actual > 0) {
            return back()->withErrors([
                'error' => 'Este punto ya emitió facturas; desactívalo en lugar de eliminarlo.'
            ]);
        }

        $punto->delete();
        return back()->with('success', 'Punto de venta eliminado.');
    }

    // ══════════════════════════════════════════════════════════════
    //  RENOVAR CUIS + CUFD  (SOAP contra el SIN)
    // ══════════════════════════════════════════════════════════════
    public function renovarCodigos(SiatPuntoVenta $punto)
    {
        //$this->autorizar();

        if ($punto->empresa_id !== session('empresa_id')) {
            abort(403);
        }

        try {
            app(SiatService::class)->asegurarCodigos($punto);
            return back()->with('success', "CUIS y CUFD al día para «{$punto->nombre}».");
        } catch (\Throwable $e) {
            return back()->withErrors([
                'error' => 'No se pudo comunicar con el SIN: ' . $e->getMessage()
            ]);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  PROBAR CONEXIÓN (útil para el botón "Probar")
    // ══════════════════════════════════════════════════════════════
    public function probarConexion()
    {
        //$this->autorizar();

        $config = ConfiguracionSiat::where('empresa_id', session('empresa_id'))
            ->where('activo', true)
            ->firstOrFail();

        try {
            $ok = app(\App\Services\Siat\SiatTransport::class)->ping($config);
            return back()->with('success', $ok ? 'Conexión exitosa con el SIN.' : 'El SIN no respondió.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
