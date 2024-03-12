<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\Accrual;
use App\A101;

class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function testPaid()
    {
        Accrual::factory()->create([
            'account' => 'ТЕСТ1',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'created_at' => new Carbon('previous month'),
            'sent_at' => now(),
            'unione_status' => 'delivered',
            'opened_at' => new Carbon('previous month'),
            'confirmed_at' => new Carbon('previous month'),
            'paid_at' => new Carbon('previous month'),
            'archived_at' => new Carbon('previous month'),
        ]);
        Accrual::factory()->create([
            'account' => 'ТЕСТ2',
            'payee' => 'overhaul',
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'created_at' => new Carbon('previous month'),
            'sent_at' => now(),
            'unione_status' => 'delivered',
            'opened_at' => new Carbon('previous month'),
            'confirmed_at' => new Carbon('previous month'),
            'paid_at' => new Carbon('previous month'),
            'archived_at' => new Carbon('previous month'),
        ]);

        $data = [];
        $data['from'] = (new Carbon('previous month'))->startOfMonth()->format('Y-m-d');
        $data['to'] = (new Carbon('previous month'))->endOfMonth()->format('Y-m-d');
        $a101 = app(A101::class);
        $data['signature'] = $a101->getApiPaymentsSignature($data);

        $response = $this->json('GET', '/api/a101/payments', $data);
        $response->assertStatus(200);
        $response->assertJson(['title' => 'OK']);
        $data = $response->decodeResponseJson();
        
        $this->assertCount(2, $data['data']['payments']);
        $this->assertArrayHasKey('date', $data['data']['payments'][0]);
        $this->assertArrayHasKey('accrual_id', $data['data']['payments'][0]);
        $this->assertEquals($data['data']['payments'][0]['account'], 'ТЕСТ1');
        $this->assertEquals($data['data']['payments'][0]['sum'], 10000);
        $this->assertEquals($data['data']['payments'][0]['payee'], 'a101');
    }

    public function testDateFilter()
    {
        Accrual::factory()->create([
            'account' => 'ТЕСТ1',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'created_at' => new Carbon('previous month'),
            'sent_at' => now(),
            'unione_status' => 'delivered',
            'opened_at' => new Carbon('previous month'),
            'confirmed_at' => new Carbon('previous month'),
            'paid_at' => new Carbon('previous month'),
            'archived_at' => new Carbon('previous month'),
        ]);
        Accrual::factory()->create([
            'account' => 'ТЕСТ2',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', time()),
            'created_at' => new Carbon(),
            'sent_at' => now(),
            'unione_status' => 'delivered',
            'opened_at' => new Carbon(),
            'confirmed_at' => new Carbon(),
            'paid_at' => new Carbon(),
            'archived_at' => new Carbon(),
        ]);
        Accrual::factory()->create([
            'account' => 'ТЕСТ3',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', strtotime('-2 months')),
            'created_at' => new Carbon('-2 months'),
            'sent_at' => now(),
            'unione_status' => 'delivered',
            'opened_at' => new Carbon('-2 months'),
            'confirmed_at' => new Carbon('-2 months'),
            'paid_at' => new Carbon('-2 months'),
            'archived_at' => new Carbon('-2 months'),
        ]);

        $data = [];
        $data['from'] = (new Carbon('previous month'))->startOfMonth()->format('Y-m-d');
        $data['to'] = (new Carbon('previous month'))->endOfMonth()->format('Y-m-d');
        $a101 = app(A101::class);
        $data['signature'] = $a101->getApiPaymentsSignature($data);

        $response = $this->json('GET', '/api/a101/payments', $data);
        $response->assertStatus(200);
        $response->assertJson(['title' => 'OK']);
        $data = $response->decodeResponseJson();
        $this->assertCount(1, $data['data']['payments']);
    }

    public function testNotPaid()
    {
        Accrual::factory()->create([
            'account' => 'ТЕСТ1',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'created_at' => new Carbon('previous month'),
            'sent_at' => now(),
            'unione_status' => 'delivered',
            'opened_at' => new Carbon('previous month'),
            'confirmed_at' => new Carbon('previous month'),
            'paid_at' => null,
            'archived_at' => new Carbon('previous month'),
        ]);

        $data = [];
        $data['from'] = (new Carbon('previous month'))->startOfMonth()->format('Y-m-d');
        $data['to'] = (new Carbon('previous month'))->endOfMonth()->format('Y-m-d');
        $a101 = app(A101::class);
        $data['signature'] = $a101->getApiPaymentsSignature($data);

        $response = $this->json('GET', '/api/a101/payments', $data);
        $response->assertStatus(200);
        $response->assertJson(['title' => 'OK']);
        $data = $response->decodeResponseJson();
        $this->assertCount(0, $data['data']['payments']);
    }

    public function testRequiredFields()
    {
        $response = $this->get('/api/a101/payments');
        $response->assertStatus(400);
        $response->assertSeeText('from');

        $from = (new Carbon('previous month'))->startOfMonth()->format('Y-m-d');
        $to = (new Carbon('previous month'))->endOfMonth()->format('Y-m-d');
        $response = $this->call('GET', '/api/a101/payments', [
            'from' => $from,
            'to' => $to,
        ]);
        $response->assertStatus(400);
        $response->assertSeeText('signature');

        $response = $this->call('GET', '/api/a101/payments', [
            'from' => $from,
            'to' => $to,
            'signature' => 'dummysignature',
        ]);
        $response->assertStatus(401);
        $response->assertSeeText('Wrong signature');
    }
}
