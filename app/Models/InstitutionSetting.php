<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity; // <-- 1. Import Trait

class InstitutionSetting extends Model
{
    use LogsActivity; // <-- 2. Gunakan Trait untuk logging aktivitas
    
    protected $fillable = [
        'name',
        'logo',
        'address',
        'phone',
        'email',
        'website',
        'head_name',
        'established_year'
    ];
}
