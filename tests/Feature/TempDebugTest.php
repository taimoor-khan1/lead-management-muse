<?php

namespace Tests\Feature;

use App\Models\EmailSend;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TempDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_mark_replied(): void
    {
        if (! Route::has('whatsapp.index')) {
            require base_path('routes/whatsapp.php');
            app('router')->getRoutes()->refreshNameLookups();
        }

        $user = User::factory()->create();
        $lead = Lead::create(['business_name' => 'X']);
        $send = EmailSend::create([
            'lead_id' => $lead->id, 'subject' => 'S', 'body' => 'B',
            'status' => 'sent', 'tracking_token' => 'tok-'.uniqid(),
        ]);

        $url = route('emails.mark-replied', $send);
        fwrite(STDERR, "\nURL: $url\n");

        DB::enableQueryLog();
        $r = $this->actingAs($user)->post($url);
        fwrite(STDERR, 'Response: '.$r->getStatusCode().' -> '.$r->headers->get('Location')."\n");
        foreach (DB::getQueryLog() as $q) {
            fwrite(STDERR, 'Q: '.$q['query'].' ['.implode(',', $q['bindings'])."]\n");
        }
        fwrite(STDERR, 'Fresh: '.$send->fresh()->status."\n");
        fwrite(STDERR, 'Route action: '.json_encode(Route::getRoutes()->getByName('emails.mark-replied')->getAction())."\n");

        $this->assertTrue(true);
    }
}
