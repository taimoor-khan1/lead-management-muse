<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'phone' => '+92 3' . $this->faker->numberBetween(10, 49) . ' ' . $this->faker->numberBetween(1000000, 9999999),
            'email' => $this->faker->companyEmail(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->randomElement(['Lahore', 'Karachi', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar']),
            'industry' => $this->faker->randomElement(['School', 'Hospital', 'Restaurant', 'Salon', 'Hotel', 'Pharmacy']),
            'website_status' => $this->faker->randomElement(['none', 'weak', 'ok']),
            'stage' => 'new',
            'score' => $this->faker->numberBetween(0, 100),
            'source' => 'manual',
            'owner_id' => User::factory(),
        ];
    }
}
