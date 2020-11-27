<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $casts = [
        'label_id' => 'array',
    ];

    public function toESArray()
    {
        $arrtibute = $this->toArray();

        $arrtibute["label"] = Label::whereIn("id", $this->label_id)->pluck("title")->implode("，");

        $arrtibute["content"] = strip_tags($arrtibute["content"]);

        return $arrtibute;
    }
}
