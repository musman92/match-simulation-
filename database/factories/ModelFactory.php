<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use Faker\Generator as Faker;

$factory->define(App\Tournament::class, function (Faker $faker) {
    return [
      'name' => $faker->name,
    ];
});


$factory->define(App\Team::class, function (Faker $faker) {
    return [
      'name' => $faker->name,
    ];
});

$factory->define(App\Player::class, function (Faker $faker) {
    return [
      'name' => $faker->name,
      'type' => $faker->randomElement(['BT', 'BWL', 'AL'])
    ];
});

$factory->define(App\Match::class, function (Faker $faker) {
    return [
      'venue' => $faker->city,
    ];
});
