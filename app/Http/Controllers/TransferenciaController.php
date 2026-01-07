<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Transferencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * @group Gestión de Transferencias
 * 
 * APIs para gestionar transferencias entre usuarios con validaciones de seguridad
 */
class TransferenciaController extends Controller
{
    /**
     * Crear transferencia
     * 
     * Realiza una transferencia entre dos usuarios. Incluye validaciones de:
     * - Saldo suficiente del emisor
     * - Límite diario de 5,000 USD
     * - Prevención de duplicados (5 minutos)
     * - No permitir transferencias a sí mismo
     * 
     * @authenticated
     * 
     * @bodyParam id_emisor integer required ID del usuario que envía dinero. Example: 1
     * @bodyParam id_receptor integer required ID del usuario que recibe dinero (debe ser diferente al emisor). Example: 2
     * @bodyParam monto numeric required Monto a transferir (máximo 5000 por transferencia). Example: 100.50
     * 
     * @response 201 {
     *   "message": "Transferencia realizada exitosamente",
     *   "transferencia": {
     *     "id": 10,
     *     "id_emisor": 1,
     *     "id_receptor": 2,
     *     "monto": "100.50",
     *     "hash_unico": "abc123...",
     *     "created_at": "2026-01-07T12:00:00.000000Z"
     *   },
     *   "nuevo_saldo_emisor": "900.50",
     *   "nuevo_saldo_receptor": "100.50"
     * }
     * 
     * @response 400 {
     *   "message": "Saldo insuficiente para realizar la transferencia",
     *   "saldo_actual": "50.00",
     *   "monto_solicitado": "100.00"
     * }
     * 
     * @response 400 {
     *   "message": "Has excedido el límite diario de transferencias de 5,000 USD",
     *   "total_transferido_hoy": "4500.00",
     *   "monto_solicitado": "600.00",
     *   "limite_disponible": "500.00"
     * }
     * 
     * @response 409 {
     *   "message": "Esta transferencia ya fue procesada recientemente. Transferencia duplicada detectada."
     * }
     * 
     * @response 422 {
     *   "errors": {
     *     "monto": ["El monto máximo por transferencia es de 5,000 USD"]
     *   }
     * }
     */
    public function crearTransferencia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_emisor' => 'required|exists:users,id',
            'id_receptor' => 'required|exists:users,id|different:id_emisor',
            'monto' => 'required|numeric|min:0.01|max:5000'
        ], [
            'id_receptor.different' => 'No puedes transferir dinero a ti mismo',
            'monto.max' => 'El monto máximo por transferencia es de 5,000 USD'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $emisor = User::find($request->id_emisor);
        $receptor = User::find($request->id_receptor);

        if ($emisor->saldo < $request->monto) {
            return response()->json([
                'message' => 'Saldo insuficiente para realizar la transferencia',
                'saldo_actual' => $emisor->saldo,
                'monto_solicitado' => $request->monto
            ], 400);
        }

        $hoy = Carbon::today();
        $totalHoy = Transferencia::where('id_emisor', $request->id_emisor)
            ->whereDate('created_at', $hoy)
            ->sum('monto');

        if (($totalHoy + $request->monto) > 5000) {
            return response()->json([
                'message' => 'Has excedido el límite diario de transferencias de 5,000 USD',
                'total_transferido_hoy' => $totalHoy,
                'monto_solicitado' => $request->monto,
                'limite_disponible' => 5000 - $totalHoy
            ], 400);
        }

        $hashUnico = md5($request->id_emisor . $request->id_receptor . $request->monto);
        
        $transferenciaReciente = Transferencia::where('hash_unico', $hashUnico)
            ->where('created_at', '>=', Carbon::now()->subMinutes(5))
            ->first();
        
        if ($transferenciaReciente) {
            return response()->json([
                'message' => 'Esta transferencia ya fue procesada recientemente. Transferencia duplicada detectada.',
                'transferencia_existente' => $transferenciaReciente
            ], 409);
        }

        try {
            DB::beginTransaction();

            $transferencia = Transferencia::create([
                'id_emisor' => $request->id_emisor,
                'id_receptor' => $request->id_receptor,
                'monto' => $request->monto,
                'hash_unico' => $hashUnico
            ]);

            $emisor->saldo -= $request->monto;
            $emisor->save();

            $receptor->saldo += $request->monto;
            $receptor->save();

            DB::commit();

            return response()->json([
                'message' => 'Transferencia realizada exitosamente',
                'transferencia' => $transferencia,
                'nuevo_saldo_emisor' => $emisor->saldo,
                'nuevo_saldo_receptor' => $receptor->saldo
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al procesar la transferencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar transferencias a CSV
     * 
     * Descarga todas las transferencias en formato CSV con punto y coma (;) como delimitador.
     * El archivo incluye: ID, Emisor, Receptor, Monto, Fecha y Hash único.
     * 
     * @authenticated
     * 
     * @response 200 [Binary CSV file download]
     */
    public function exportarCSV()
    {
        $transferencias = Transferencia::with(['emisor', 'receptor'])->get();

        $filename = 'transferencias_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($transferencias) {
            $file = fopen('php://output', 'w');
            
            // BOM para UTF-8 (para que Excel lea bien los acentos)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Encabezados del CSV con punto y coma
            fputcsv($file, [
                'ID',
                'Emisor ID',
                'Emisor Nombre',
                'Receptor ID',
                'Receptor Nombre',
                'Monto',
                'Fecha de Creación',
                'Hash Único'
            ], ';');

            // Datos de las transferencias
            foreach ($transferencias as $transferencia) {
                fputcsv($file, [
                    $transferencia->id,
                    $transferencia->id_emisor,
                    $transferencia->emisor->name,
                    $transferencia->id_receptor,
                    $transferencia->receptor->name,
                    number_format($transferencia->monto, 2, '.', ''),
                    $transferencia->created_at->format('Y-m-d H:i:s'),
                    $transferencia->hash_unico
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Total transferido por usuario
     * 
     * Consulta optimizada que retorna el total de dinero transferido por cada usuario emisor.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "total_transferido_por_usuario": [
     *     {
     *       "usuario_id": 1,
     *       "usuario_nombre": "Juan Pérez",
     *       "usuario_email": "juan@example.com",
     *       "total_transferido": "5000.00"
     *     }
     *   ]
     * }
     */
    public function totalTransferidoPorUsuario()
    {
        $totales = Transferencia::select('id_emisor', DB::raw('SUM(monto) as total_transferido'))
            ->with('emisor:id,name,email')
            ->groupBy('id_emisor')
            ->get()
            ->map(function($item) {
                return [
                    'usuario_id' => $item->id_emisor,
                    'usuario_nombre' => $item->emisor->name,
                    'usuario_email' => $item->emisor->email,
                    'total_transferido' => number_format($item->total_transferido, 2, '.', '')
                ];
            });

        return response()->json([
            'total_transferido_por_usuario' => $totales
        ], 200);
    }

    /**
     * Promedio de monto por usuario
     * 
     * Consulta optimizada que calcula el promedio de monto transferido por cada usuario.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "promedio_monto_por_usuario": [
     *     {
     *       "usuario_id": 1,
     *       "usuario_nombre": "Juan Pérez",
     *       "usuario_email": "juan@example.com",
     *       "promedio_monto": "250.00",
     *       "total_transferencias": 20
     *     }
     *   ]
     * }
     */
    public function promedioMontoPorUsuario()
    {
        $promedios = Transferencia::select('id_emisor', DB::raw('AVG(monto) as promedio_monto'), DB::raw('COUNT(*) as total_transferencias'))
            ->with('emisor:id,name,email')
            ->groupBy('id_emisor')
            ->get()
            ->map(function($item) {
                return [
                    'usuario_id' => $item->id_emisor,
                    'usuario_nombre' => $item->emisor->name,
                    'usuario_email' => $item->emisor->email,
                    'promedio_monto' => number_format($item->promedio_monto, 2, '.', ''),
                    'total_transferencias' => $item->total_transferencias
                ];
            });

        return response()->json([
            'promedio_monto_por_usuario' => $promedios
        ], 200);
    }

}
