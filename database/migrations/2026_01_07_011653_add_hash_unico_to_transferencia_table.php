<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si la columna ya existe
        if (!Schema::hasColumn('transferencia', 'hash_unico')) {
            Schema::table('transferencia', function (Blueprint $table) {
                $table->string('hash_unico')->nullable()->after('monto');
            });

            $transferencias = DB::table('transferencia')->get();
            foreach ($transferencias as $transferencia) {
                $hash = md5($transferencia->id_emisor . $transferencia->id_receptor . $transferencia->monto . $transferencia->id);
                DB::table('transferencia')
                    ->where('id', $transferencia->id)
                    ->update(['hash_unico' => $hash]);
            }
        }

        // Agregar la restricción unique si no existe
        $indexes = DB::select("SHOW INDEXES FROM transferencia WHERE Column_name = 'hash_unico' AND Key_name = 'transferencia_hash_unico_unique'");
        if (empty($indexes)) {
            Schema::table('transferencia', function (Blueprint $table) {
                $table->string('hash_unico')->nullable(false)->unique()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transferencia', function (Blueprint $table) {
            $table->dropColumn('hash_unico');
        });
    }
};
