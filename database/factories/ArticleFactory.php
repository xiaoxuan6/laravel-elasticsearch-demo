<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(\App\Model\Article::class, function (Faker $faker) {
    return [
        'name' => $faker->title,
        'content' => $faker->text,
        'view' => $faker->randomNumber(2),
        'label' => $faker->lastName
    ];
});
