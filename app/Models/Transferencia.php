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
    ];
}
