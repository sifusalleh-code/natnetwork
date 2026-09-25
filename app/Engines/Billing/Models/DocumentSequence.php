<?php

namespace App\Engines\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    protected $fillable = ['prefix', 'year', 'last_number'];
}
