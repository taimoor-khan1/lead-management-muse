<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = User::query()->where('email', 'admin@leadflow.pk')->value('id')
            ?? User::query()->first()?->id;

        $leads = [
            ['The City School Model Town', 'Ahmed Raza', '+92 321 4567890', 'info@thecityschoolmt.edu.pk', 'Main Boulevard, Model Town', 31.4705, 74.3232, 'thecityschoolmt.edu.pk', 'weak', 'Lahore', 'School', 'Needs fee management system.', 'School ERP', 'new', 72, 'manual'],
            ['Beaconhouse Gulberg Campus', 'Sara Khan', '+92 333 2145678', 'gulberg@beaconhouse.edu.pk', 'Gulberg III', 31.5204, 74.3587, null, 'none', 'Lahore', 'School', 'No website at all.', 'School ERP', 'contacted', 65, 'manual'],
            ['Dar-e-Arqam School DHA', 'Hassan Ali', '+92 345 9871234', null, 'DHA Phase 4', 31.4697, 74.3944, 'darearqamdha.pk', 'weak', 'Lahore', 'School', 'Old static site.', 'School ERP', 'replied', 78, 'manual'],
            ['Shaukat Khanum Memorial Hospital', 'Dr. Ayesha Malik', '+92 300 8456123', 'contact@skmh.org.pk', 'Johar Town', 31.4696, 74.2728, 'shaukatkhanum.org.pk', 'ok', 'Lahore', 'Hospital', 'Large hospital, needs appointment module.', 'Hospital ERP', 'proposal', 90, 'manual'],
            ['Fatima Memorial Hospital', 'Bilal Ahmed', '+92 322 7789012', 'info@fatimamemorial.org.pk', 'Shadman', 31.5497, 74.3436, 'fatimamemorial.org.pk', 'weak', 'Lahore', 'Hospital', null, 'Hospital ERP', 'new', 68, 'manual'],
            ['City Care Clinic', 'Dr. Usman Tariq', '+92 341 2233445', 'citycareclinic@gmail.com', 'Wapda Town', 31.4303, 74.2667, null, 'none', 'Lahore', 'Clinic', 'Small clinic, no online presence.', 'Business Website', 'new', 55, 'manual'],
            ['Lahore Diagnostic Lab', 'Nadia Hussain', '+92 333 5566778', 'info@lahorediagnosticlab.pk', 'DHA Phase 5', 31.4710, 74.4100, 'lahorediagnosticlab.pk', 'weak', 'Lahore', 'Lab', 'Wants online test booking.', 'Business Website', 'contacted', 70, 'manual'],
            ['Monnoo Restaurant', 'Kashif Mehmood', '+92 321 3344556', null, 'MM Alam Road', 31.5095, 74.3485, null, 'none', 'Lahore', 'Restaurant', 'Popular desi restaurant, no website.', 'Restaurant POS', 'new', 75, 'manual'],
            ['Café Aylanto', 'Maria Shah', '+92 345 6677889', 'hello@cafeaylanto.pk', 'Gulberg', 31.5200, 74.3600, 'cafeaylanto.pk', 'ok', 'Lahore', 'Restaurant', 'Has site, needs online ordering.', 'Restaurant POS', 'replied', 82, 'manual'],
            ['Depilex Beauty Salon DHA', 'Farah Naz', '+92 300 1122334', 'dha@depilex.pk', 'DHA Phase 2', 31.4620, 74.3900, 'depilex.pk', 'ok', 'Lahore', 'Salon', 'Franchise branch.', 'CRM Setup', 'proposal', 85, 'manual'],
            ['Toni&Guy Lahore', 'Ali Hassan', '+92 333 4455667', null, 'Model Town', 31.4710, 74.3230, null, 'none', 'Lahore', 'Salon', 'No website.', 'Business Website', 'contacted', 60, 'manual'],
            ['Pearl Continental Lahore', 'Imran Sheikh', '+92 42 1111789', 'reservations@pc-lahore.com', 'Shahrah-e-Quaid-e-Azam', 31.5610, 74.3260, 'pclahore.com', 'ok', 'Lahore', 'Hotel', 'Interested in CRM for events.', 'CRM Setup', 'won', 95, 'manual'],
            ['Hotel One Johar Town', 'Sana Tariq', '+92 321 9988776', 'johar@hotelone.com.pk', 'Johar Town', 31.4690, 74.2730, null, 'none', 'Lahore', 'Hotel', 'Needs booking website.', 'Business Website', 'new', 66, 'manual'],
            ['Servaid Pharmacy', 'Dr. Faisal Qureshi', '+92 345 1234567', 'info@servaid.pk', 'Main Boulevard Gulberg', 31.5204, 74.3587, 'servaid.pk', 'weak', 'Lahore', 'Pharmacy', 'Wants e-commerce for medicines.', 'E-commerce Store', 'contacted', 74, 'manual'],
            ['Zameen Builders', 'Adnan Malik', '+92 300 5566778', 'info@zameenbuilders.pk', 'DHA Phase 6', 31.4550, 74.4200, 'zameenbuilders.pk', 'weak', 'Lahore', 'Real Estate', 'Outdated site.', 'Website Revamp', 'replied', 80, 'manual'],
            ['Honda City Motors', 'Waqas Ahmed', '+92 321 4433221', 'sales@hondacitymotors.pk', 'Ferozepur Road', 31.4500, 74.3600, null, 'none', 'Lahore', 'Car Dealer', 'Used car dealership.', 'Business Website', 'new', 58, 'manual'],
            ['Chenab Furniture House', 'Tariq Javed', '+92 333 2211009', null, 'Ichhra', 31.5300, 74.3300, 'chenabfurniture.pk', 'weak', 'Lahore', 'Furniture', 'Wants online catalog.', 'E-commerce Store', 'lost', 50, 'manual'],

            ['The Educators Karachi Campus', 'Rubina Aslam', '+92 300 2233445', 'karachi@theeducators.edu.pk', 'Clifton Block 5', 24.8138, 67.0300, null, 'none', 'Karachi', 'School', null, 'School ERP', 'new', 62, 'manual'],
            ['Aga Khan University Hospital', 'Dr. Salman Iqbal', '+92 21 111911911', 'info@aku.edu', 'Stadium Road', 24.8918, 67.0740, 'aku.edu', 'ok', 'Karachi', 'Hospital', 'Large institution.', 'Hospital ERP', 'contacted', 88, 'manual'],
            ['South City Hospital', 'Dr. Hina Rizvi', '+92 333 9988776', 'info@southcityhospital.org', 'Clifton', 24.8140, 67.0320, 'southcityhospital.org', 'weak', 'Karachi', 'Hospital', null, 'Hospital ERP', 'proposal', 84, 'manual'],
            ['Do Darya Food Street', 'Kamran Siddiqui', '+92 345 7788990', null, 'Do Darya, DHA Phase 8', 24.7850, 67.0650, null, 'none', 'Karachi', 'Restaurant', 'Seafood spot, no website.', 'Business Website', 'new', 57, 'manual'],
            ['Kolachi Restaurant', 'Faisal Merchant', '+92 321 6655443', 'info@kolachi.pk', 'Do Darya', 24.7855, 67.0645, 'kolachi.pk', 'ok', 'Karachi', 'Restaurant', 'Wants POS upgrade.', 'Restaurant POS', 'replied', 81, 'manual'],
            ['Sana Safinaz Salon', 'Mahnoor Asif', '+92 300 3344556', 'salon@sanasafinaz.pk', 'Bath Island', 24.8400, 67.0350, null, 'none', 'Karachi', 'Salon', 'No online booking.', 'Business Website', 'new', 63, 'manual'],
            ['Avari Towers Karachi', 'Danish Khan', '+92 21 111282747', 'karachi@avari.com', 'Fatima Jinnah Road', 24.8540, 67.0270, 'avari.com', 'ok', 'Karachi', 'Hotel', 'Needs lead CRM for banquets.', 'CRM Setup', 'won', 92, 'manual'],
            ['Dawlance Pharmacy Chain', 'Asad Raza', '+92 345 1122334', 'info@dawlancepharmacy.pk', 'Gulshan-e-Iqbal', 24.9250, 67.0850, 'dawlancepharmacy.pk', 'weak', 'Karachi', 'Pharmacy', 'Wants online store.', 'E-commerce Store', 'contacted', 71, 'manual'],
            ['Bahria Town Karachi Realty', 'Imran Farooq', '+92 333 4455661', 'info@btkarachirealty.pk', 'Bahria Town', 24.9900, 67.3100, null, 'none', 'Karachi', 'Real Estate', 'Property listings needed.', 'Business Website', 'new', 59, 'manual'],
            ['Toyota Defence Motors', 'Bilal Sheikh', '+92 300 7788990', 'sales@toyotadefence.pk', 'Korangi Road', 24.8600, 67.0450, 'toyotadefence.pk', 'weak', 'Karachi', 'Car Dealer', 'Needs SEO.', 'SEO Monthly', 'lost', 48, 'manual'],
            ['Interwood Karachi Showroom', 'Sadia Anwar', '+92 321 2233445', null, 'Shahrah-e-Faisal', 24.8607, 67.0011, 'interwood.pk', 'ok', 'Karachi', 'Furniture', 'Wants e-commerce.', 'E-commerce Store', 'proposal', 86, 'manual'],
            ['Dr. Essa Laboratory', 'Dr. Farhan Essa', '+92 21 111362536', 'info@essalab.com', 'Gulshan-e-Iqbal', 24.9200, 67.0900, 'essalab.com', 'ok', 'Karachi', 'Lab', 'Online reports already.', 'SEO Monthly', 'replied', 79, 'manual'],

            ['Roots Millennium School', 'Ayesha Farooq', '+92 51 111111292', 'islamabad@rootsmillennium.edu.pk', 'F-8 Markaz', 33.7000, 73.0400, 'rootsmillennium.edu.pk', 'weak', 'Islamabad', 'School', 'Fee portal needed.', 'School ERP', 'new', 69, 'manual'],
            ['Shifa International Hospital', 'Dr. Tariq Mehmood', '+92 51 111444666', 'info@shifa.com.pk', 'H-8/4', 33.7000, 73.0600, 'shifa.com.pk', 'ok', 'Islamabad', 'Hospital', 'Enterprise scale.', 'Hospital ERP', 'contacted', 89, 'manual'],
            ['Savour Foods Rawalpindi', 'Rashid Mehmood', '+92 333 5566771', null, 'Saddar', 33.6000, 73.0500, null, 'none', 'Rawalpindi', 'Restaurant', 'Famous pulao kabab house.', 'Restaurant POS', 'new', 73, 'manual'],
            ['Serena Hotel Islamabad', 'Nadia Ali', '+92 51 111133133', 'islamabad@serena.pk', 'Khayaban-e-Suharwardy', 33.7200, 73.1000, 'serena.pk', 'ok', 'Islamabad', 'Hotel', null, 'CRM Setup', 'replied', 87, 'manual'],
            ['The Body Shop Peshawar', 'Sana Gul', '+92 333 1122334', null, 'University Road', 34.0100, 71.5400, null, 'none', 'Peshawar', 'Salon', 'No website.', 'Business Website', 'new', 54, 'manual'],
            ['Northwest General Hospital', 'Dr. Arif Khan', '+92 91 5838000', 'info@nwgh.pk', 'Hayatabad', 33.9900, 71.4500, 'nwgh.pk', 'weak', 'Peshawar', 'Hospital', 'Needs revamp.', 'Website Revamp', 'contacted', 76, 'manual'],
            ['Chenab School Faisalabad', 'Muhammad Asif', '+92 300 6655443', 'info@chenabschool.edu.pk', 'Madina Town', 31.4200, 73.0900, null, 'none', 'Faisalabad', 'School', null, 'School ERP', 'new', 61, 'manual'],
            ['Sandal Bar Restaurant', 'Usman Ghani', '+92 333 7788991', null, 'Kohinoor City', 31.4300, 73.0800, 'sandalbar.pk', 'weak', 'Faisalabad', 'Restaurant', 'Old site.', 'Website Revamp', 'lost', 52, 'manual'],
            ['Bosan Road Pharmacy', 'Dr. Kamran Bosan', '+92 300 9988771', 'bosanpharmacy@gmail.com', 'Bosan Road', 30.2000, 71.5200, null, 'none', 'Multan', 'Pharmacy', 'New pharmacy chain.', 'E-commerce Store', 'new', 64, 'manual'],
            ['Multan Real Estate Hub', 'Shahid Mehmood', '+92 333 4455662', 'info@multanrealestate.pk', 'Gulgasht Colony', 30.2100, 71.5100, 'multanrealestate.pk', 'weak', 'Multan', 'Real Estate', 'Needs SEO.', 'SEO Monthly', 'contacted', 67, 'manual'],
        ];

        foreach ($leads as $row) {
            Lead::query()->create([
                'business_name' => $row[0],
                'contact_person' => $row[1],
                'phone' => $row[2],
                'email' => $row[3],
                'address' => $row[4],
                'latitude' => $row[5],
                'longitude' => $row[6],
                'website' => $row[7],
                'website_status' => $row[8],
                'city' => $row[9],
                'industry' => $row[10],
                'notes' => $row[11],
                'suggested_service' => $row[12],
                'stage' => $row[13],
                'score' => $row[14],
                'source' => $row[15],
                'owner_id' => $ownerId,
                'last_contacted_at' => $row[13] !== 'new' ? now()->subDays(rand(1, 20)) : null,
            ]);
        }
    }
}
