<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transferencia extends Model
{
    use HasFactory;

    protected $table = 'transferencia';

    protected $fillable = [
        'id_emisor',
        'id_receptor',
        'monto',
        'hash_unico'
    ];

    // Relación con usuario emisor
    public function emisor()
    {
        return $this->belongsTo(User::class, 'id_emisor');
    }

    // Relación con usuario receptor
    public function receptor()
    {
        return $this->belongsTo(User::class, 'id_receptor');
    }

}
