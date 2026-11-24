<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vista Previa - Asiento {{ $asiento->numero_asiento }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <!-- Layout simple para previsualización -->
    <div class="min-h-screen p-4">
        <div class="max-w-6xl mx-auto">
            <!-- Botones de navegación -->
            <div class="mb-4 bg-white p-4 rounded-lg shadow">
                <div class="flex justify-between items-center">
                    <a href="{{ url()->previous() }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-700">
                        ← Volver
                    </a>
                    <div class="flex gap-2">
                        <a href="{{ route('asientos.pdf.preview', $asiento->id) }}"
                           target="_blank"
                           class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700">
                            🔄 Actualizar Vista
                        </a>
                        <a href="{{ route('asientos.pdf.download', $asiento->id) }}"
                           class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded hover:bg-green-700">
                            ⬇️ Descargar PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contenedor del asiento (mismo diseño que PDF pero con Tailwind) -->
            <div id="asiento-preview" class="bg-white shadow-lg rounded-lg overflow-hidden">
                <!-- Encabezado -->
                <div class="px-6 py-4 border-b">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                        <!-- Mismo contenido que tu componente Vue -->
                        <div class="flex items-center mb-4 md:mb-0">
                            @if($empresa->logo)
                                <img src="{{ asset('storage/' . $empresa->logo) }}"
                                     class="h-10 w-10 rounded-full object-cover border">
                            @endif
                            <div class="ml-4">
                                <h1 class="text-xl font-bold">{{ $empresa->nombre }}</h1>
                                <div class="text-sm text-gray-600">
                                    @if($empresa->nit) NIT: {{ $empresa->nit }} @endif
                                    @if($empresa->telefono) • Tel: {{ $empresa->telefono }} @endif
                                </div>
                            </div>
                        </div>

                        <div class="text-center mb-4 md:mb-0 md:flex-1">
                            <h2 class="text-lg font-semibold">ASIENTO CONTABLE</h2>
                            <div class="mt-1 text-sm">
                                N° {{ $asiento->numero_asiento }} • {{ $formatDate($asiento->fecha) }}
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-sm text-gray-600">Periodo: {{ $asiento->periodo->nombre }}</div>
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold {{ $tipoClass($asiento->tipo) }}">
                                {{ $tipoText($asiento->tipo) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Resto del contenido (similar a tu componente Vue) -->
                <div class="px-6 py-4">
                    <!-- Información secundaria -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <div class="text-sm text-gray-500">Dirección:</div>
                            <div class="text-sm">{{ $empresa->direccion }}</div>
                            @if($empresa->ciudad || $empresa->pais)
                                <div class="text-sm text-gray-600">
                                    {{ $empresa->ciudad }}{{ $empresa->ciudad && $empresa->pais ? ', ' : '' }}{{ $empresa->pais }}
                                </div>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500">Comprobante:</div>
                            <div class="text-sm">{{ $asiento->comprobante ?: 'N/A' }}</div>
                        </div>
                    </div>

                    <!-- Glosa -->
                    <div class="mb-4">
                        <div class="text-sm text-gray-500">Glosa:</div>
                        <div class="text-sm">{{ $asiento->glosa }}</div>
                    </div>

                    <!-- Tabla de detalles -->
                    <h3 class="text-lg font-medium mb-3">Detalles del Asiento</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <!-- Misma estructura de tabla que tu componente -->
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium">Código</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium">Cuenta</th>
                                    <!-- ... resto de columnas ... -->
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($asiento->detalles as $detalle)
                                <tr>
                                    <td class="px-4 py-2 text-xs">{{ $detalle->cuenta->codigo ?? '—' }}</td>
                                    <td class="px-4 py-2 text-xs">{{ $detalle->cuenta->nombre ?? '—' }}</td>
                                    <!-- ... resto de celdas ... -->
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Totales, auditoría, resúmenes... -->
                </div>
            </div>

            <!-- Botones al final -->
            <div class="mt-4 bg-white p-4 rounded-lg shadow">
                <div class="flex justify-center gap-4">
                    <button onclick="window.print()"
                            class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700">
                        🖨️ Imprimir Vista
                    </button>
                    <a href="{{ route('asientos.pdf.download', $asiento->id) }}"
                       class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded hover:bg-green-700">
                        ⬇️ Descargar PDF
                    </a>
                    <a href="{{ route('asientos.edit', $asiento->id) }}"
                       class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-700">
                        ✏️ Editar Asiento
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Agregar función para imprimir solo el contenido del asiento
        function imprimirAsiento() {
            const contenido = document.getElementById('asiento-preview').innerHTML;
            const ventana = window.open('', '_blank');
            ventana.document.write(`
                <html>
                <head>
                    <title>Asiento {{ $asiento->numero_asiento }}</title>
                    <style>
                        @page { margin: 1cm; }
                        body { font-family: Arial; font-size: 12px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 5px; }
                    </style>
                </head>
                <body>${contenido}</body>
                </html>
            `);
            ventana.document.close();
            ventana.print();
        }
    </script>
</body>
</html>
