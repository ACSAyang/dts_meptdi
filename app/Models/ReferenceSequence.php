<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceSequence extends Model
{
    protected $fillable = ['type', 'year', 'last_number'];
}
