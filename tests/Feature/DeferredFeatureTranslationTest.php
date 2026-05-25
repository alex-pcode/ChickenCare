<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EggEntry;
use App\Models\Expense;
use App\Models\FeedInventory;
use App\Models\FlockBatch;
use App\Models\FlockEvent;
use App\Models\FlockProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeferredFeatureTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_page_renders_in_serbian_for_authenticated_premium_users(): void
    {
        $user = User::factory()->premium()->create(['locale' => 'sr']);

        FeedInventory::factory()->create([
            'user_id' => $user->id,
            'brand' => 'Starter Crumble',
        ]);

        $response = $this->actingAs($user)->get(route('app.feed.index'));

        $response->assertOk();
        $response->assertSee('Zalihe hrane');
        $response->assertSee('Dodaj novu hranu');
        $response->assertSee('Pratite svoju hranu!');
        $response->assertSee('Evidencija hrane');
        $response->assertDontSee('Feed Inventory');
        $response->assertDontSee('Add New Feed');
    }

    public function test_feed_records_htmx_partial_renders_in_serbian(): void
    {
        $user = User::factory()->premium()->create(['locale' => 'sr']);

        FeedInventory::factory()->create([
            'user_id' => $user->id,
            'opened_date' => '2026-04-22',
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get(route('app.feed.index'));

        $response->assertOk();
        $response->assertViewIs('feed.partials.records-table');
        $response->assertSee('Brend');
        $response->assertSee('Trajanje');
        $response->assertSee('Akcije');
        $response->assertDontSee('Brand');
        $response->assertDontSee('Duration');
    }

    public function test_crm_page_and_customers_tab_render_in_serbian(): void
    {
        $user = User::factory()->premium()->create(['locale' => 'sr']);

        Customer::factory()->create([
            'user_id' => $user->id,
            'name' => 'Marko Markovic',
        ]);

        $pageResponse = $this->actingAs($user)->get(route('app.crm.index'));

        $pageResponse->assertOk();
        $pageResponse->assertSee('CRM sistem');
        $pageResponse->assertSee('Upravljajte svojim kupcima!');
        $pageResponse->assertSee('Brza prodaja');
        $pageResponse->assertSee('Kupci');
        $pageResponse->assertSee('Izvestaji');

        $tabResponse = $this->actingAs($user)
            ->withHeaders([
                'HX-Request' => 'true',
                'HX-Target' => 'crm-tab-content',
            ])
            ->get(route('app.crm.index', ['tab' => 'customers']));

        $tabResponse->assertOk();
        $tabResponse->assertViewIs('crm.partials.tab-customers');
        $tabResponse->assertSee('Kupci');
        $tabResponse->assertSee('+ Dodaj kupca');
        $tabResponse->assertSee('Izmeni Marko Markovic');
        $tabResponse->assertSee('Obrisi Marko Markovic');
        $tabResponse->assertDontSee('Customers');

        $quickSaleResponse = $this->actingAs($user)
            ->withHeaders([
                'HX-Request' => 'true',
                'HX-Target' => 'crm-tab-content',
            ])
            ->get(route('app.crm.index', ['tab' => 'quick-sale']));

        $quickSaleResponse->assertOk();
        $quickSaleResponse->assertViewIs('crm.partials.tab-quick-sale');
        $quickSaleResponse->assertSee('Evidentiraj prodaju');
        $quickSaleResponse->assertSee('Unesite detalje prodaje i cenu ispod');
        $quickSaleResponse->assertSee('Cena po jajetu ($)');
        $quickSaleResponse->assertSee('Kupac');
        $quickSaleResponse->assertSee('Izaberite kupca');
        $quickSaleResponse->assertSee('Datum prodaje');
        $quickSaleResponse->assertSee('Broj jaja');
        $quickSaleResponse->assertSee('Ukupan iznos ($)');
        $quickSaleResponse->assertSee('Napomene (opciono)');
        $quickSaleResponse->assertDontSee('Record Sale');
        $quickSaleResponse->assertDontSee('Enter sale details and pricing below');
    }

    public function test_savings_page_renders_in_serbian(): void
    {
        $user = User::factory()
            ->premium()
            ->hobby()
            ->withEggPrice(0.35)
            ->create(['locale' => 'sr']);

        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => now()->startOfMonth()->addDay()->toDateString(),
            'count' => 24,
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'date' => now()->startOfMonth()->addDay()->toDateString(),
            'category' => 'Feed',
            'amount' => 36.00,
        ]);

        $response = $this->actingAs($user)->get(route('app.savings.index'));

        $response->assertOk();
        $response->assertSee('Analiza ustede');
        $response->assertSee('Pratite svoju ustedu!');
        $response->assertSee('Podesavanja ustede');
        $response->assertSee('Cena jajeta');
        $response->assertSee('Dobili ste');
        $response->assertSee('Ustedeli ste');
        $response->assertSee('Ulozili ste');
        $response->assertSee('Neto usteda');
        $response->assertSee('ukupan trosak po jajetu');
        $response->assertSee('jaja za pokrice svih troskova');
        $response->assertSee('jaja');
        $response->assertDontSee('eggs to cover all costs');
        $response->assertSee('Prosli ste');
        $response->assertSee('Poklonili ste');
        $response->assertSee('Pojeli ste');
        $response->assertSee('Gledali ste');
        $response->assertSee('Odgajili ste');
        $response->assertDontSee('Savings Analysis');
    }

    public function test_viability_page_renders_in_serbian(): void
    {
        $user = User::factory()->premium()->create(['locale' => 'sr']);

        $response = $this->actingAs($user)->get(route('app.viability.index'));

        $response->assertOk();
        $response->assertSee('Kalkulator isplativosti');
        $response->assertSee('Izracunajte svoju kokosarsku avanturu!');
        $response->assertSee('Pocetno ulaganje');
        $response->assertSee('Minimalna postavka');
        $response->assertSee('Metod nabavke');
        $response->assertSee('Odgajajte pilice');
        $response->assertSee('Parametri postavke');
        $response->assertSee('Budzetski pristup');
        $response->assertSee('Finansijska analiza');
        $response->assertSee('Godisnji pregled');
        $response->assertSee('Analiza povracaja');
        $response->assertSee('Procena isplativosti');
        $response->assertSee('Analiza tacke pokrica');
        $response->assertSee('Vasa procena');
        $response->assertSee('Preporuke');
        $response->assertDontSee('Viability Calculator');
        $response->assertDontSee('Starting Investment');
    }

    public function test_flock_and_batches_pages_render_in_serbian(): void
    {
        $user = User::factory()->premium()->create(['locale' => 'sr']);

        $profile = FlockProfile::factory()->create([
            'user_id' => $user->id,
            'hens' => 10,
            'roosters' => 1,
            'chicks' => 2,
        ]);

        FlockBatch::factory()->active()->create([
            'user_id' => $user->id,
            'batch_name' => 'Nosilje',
            'type' => 'hens',
            'initial_count' => 8,
            'current_count' => 8,
            'hens_count' => 6,
            'brooding_count' => 1,
            'roosters_count' => 1,
            'chicks_count' => 0,
            'acquisition_date' => '2026-03-02',
            'actual_laying_start_date' => '2026-04-01',
        ]);

        $notLayingBatch = FlockBatch::factory()->active()->create([
            'user_id' => $user->id,
            'batch_name' => 'Mlade',
            'type' => 'hens',
            'initial_count' => 4,
            'current_count' => 4,
            'hens_count' => 4,
            'brooding_count' => 0,
            'roosters_count' => 0,
            'chicks_count' => 0,
            'acquisition_date' => '2026-04-05',
            'actual_laying_start_date' => null,
        ]);

        $event = FlockEvent::factory()->create([
            'flock_profile_id' => $profile->id,
            'date' => '2026-04-20',
            'type' => 'acquisition',
            'description' => 'Dodate su nove kokoske',
            'affected_birds' => 2,
        ]);

        $flockResponse = $this->actingAs($user)->get(route('app.flock.index'));

        $flockResponse->assertOk();
        $flockResponse->assertSee('Profil jata');
        $flockResponse->assertSee('Dodaj novi dogadjaj');
        $flockResponse->assertSee('Vremenska linija dogadjaja');
        $flockResponse->assertSee('Nosi');
        $flockResponse->assertSee('Ne nose');
        $flockResponse->assertSee('Petlovi');
        $flockResponse->assertSee('Nabavljene su nove ptice');
        $flockResponse->assertSee('2 ptice zahvacene');
        $flockResponse->assertSee($event->date->locale('sr')->translatedFormat('d. M Y.'));
        $flockResponse->assertDontSee('New Birds Acquired');

        $batchesResponse = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get(route('app.batches.index'));

        $batchesResponse->assertOk();
        $batchesResponse->assertViewIs('batches.partials.batches-table');
        $batchesResponse->assertSee('Nosi');
        $batchesResponse->assertSee('Ne nosi');
        $batchesResponse->assertSee('Nije postavljeno');
        $batchesResponse->assertSee('Prikazi detalje za Mlade');
        $batchesResponse->assertSee('Izmeni datum nosenja za Mlade');
        $batchesResponse->assertSee($notLayingBatch->acquisition_date->locale('sr')->translatedFormat('d. M Y.'));
        $batchesResponse->assertDontSee('Laying');
    }

    public function test_eggs_page_renders_in_serbian_for_authenticated_premium_users(): void
    {
        $user = User::factory()->premium()->create(['locale' => 'sr']);

        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDay()->toDateString(),
            'count' => 5,
        ]);

        $response = $this->actingAs($user)->get(route('app.eggs.index'));

        $response->assertOk();
        $response->assertSee('Pracenje jaja');
        $response->assertSee('Evidentiraj dnevna jaja');
        $response->assertSee('Broj jaja');
        $response->assertSee('Nedavni unosi');
        $response->assertSee('Datum');
        $response->assertSee('Akcije');
        $response->assertDontSee('Egg Tracking');
        $response->assertDontSee('Log Daily Eggs');
        $response->assertDontSee('Number of Eggs');
        $response->assertDontSee('Recent Entries');
    }

    public function test_eggs_table_htmx_partial_renders_in_serbian(): void
    {
        $user = User::factory()->premium()->create(['locale' => 'sr']);

        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDay()->toDateString(),
            'count' => 3,
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get(route('app.eggs.index', ['page' => 1]));

        $response->assertOk();
        $response->assertViewIs('eggs.partials.table');
        $response->assertSee('Izmeni');
        $response->assertDontSee('Edit');
    }

    public function test_feed_page_renders_english_copy_for_english_locale(): void
    {
        $user = User::factory()->premium()->create(['locale' => 'en']);

        FeedInventory::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('app.feed.index'));

        $response->assertOk();
        $response->assertSee('Feed Inventory');
        $response->assertSee('Add New Feed');
        $response->assertSee('Feed Records');
        $response->assertDontSee('feed.page.title', false);
    }

    public function test_eggs_page_renders_english_copy_for_english_locale(): void
    {
        $user = User::factory()->premium()->create(['locale' => 'en']);

        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDay()->toDateString(),
            'count' => 5,
        ]);

        $response = $this->actingAs($user)->get(route('app.eggs.index'));

        $response->assertOk();
        $response->assertSee('Egg Tracking');
        $response->assertSee('Log Daily Eggs');
        $response->assertSee('Number of Eggs');
        $response->assertSee('Recent Entries');
        $response->assertDontSee('eggs.page.title', false);
        $response->assertDontSee('eggs.form.title', false);
    }
}
