<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Business Website', 'description' => 'Modern, mobile-friendly business website with CMS.', 'price' => 85000],
            ['name' => 'Website Revamp', 'description' => 'Redesign and modernization of an existing website.', 'price' => 55000],
            ['name' => 'School ERP', 'description' => 'Complete school management system: admissions, fees, attendance, results.', 'price' => 450000],
            ['name' => 'Hospital ERP', 'description' => 'Hospital management system: OPD, pharmacy, labs, billing, records.', 'price' => 650000],
            ['name' => 'Restaurant POS', 'description' => 'Point-of-sale system for restaurants with kitchen display and reporting.', 'price' => 120000],
            ['name' => 'E-commerce Store', 'description' => 'Online store with product catalog, cart, and payment integration.', 'price' => 150000],
            ['name' => 'CRM Setup', 'description' => 'CRM implementation with pipelines, automations, and team training.', 'price' => 200000],
            ['name' => 'SEO Monthly', 'description' => 'Monthly search engine optimization retainer.', 'price' => 45000],
        ];

        foreach ($services as $service) {
            Service::query()->updateOrCreate(
                ['name' => $service['name']],
                array_merge($service, ['currency' => 'PKR', 'active' => true])
            );
        }
    }
}
