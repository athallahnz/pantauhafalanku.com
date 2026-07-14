<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicDocumentSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'sequence_key',
        'last_number',
    ];

    protected $casts = [
        'last_number' => 'integer',
    ];
}
