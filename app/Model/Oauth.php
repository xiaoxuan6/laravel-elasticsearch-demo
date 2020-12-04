<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Oauth extends Model
{
    protected $casts = [
        'original' => 'array',
    ];
}
