<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    public function toESArray(): array
    {
        return $this->toArray();
    }
}
