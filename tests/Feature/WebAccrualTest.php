<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\Accrual;
use App\A101;

class WebAccrualTest extends TestCase
{
    use RefreshDatabase;

    public function testNormalAccrual()
    {
        $accrual = Accrual::factory()->create([
            'account' => 'ТЕСТ1',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'created_at' => new Carbon('previous month'),
            'sent_at' => now(),
            'unione_status' => 'delivered',
            'opened_at' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => null,
        ]);

        /*
         * Для актуальной квитанции отображается текст "Оплатить"
         */
        $response = $this->get('/accrual/' .  $accrual->uuid);
        $response->assertStatus(200);
        $response->assertSeeText('Оплатить');
    }

    public function testArchivedAccrual()
    {
        $accrualOld = Accrual::factory()->create([
            'account' => 'ТЕСТ1',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'created_at' => new Carbon('previous month'),
            'sent_at' => now(),
            'unione_status' => 'delivered',
            'opened_at' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => null,
        ]);

        /*
         * Для старой квитанции отображается текст "Счет устарел"
         */
        $accrualNew = Accrual::factory()->create([
            'account' => 'ТЕСТ1',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym'),
            'created_at' => now(),
            'sent_at' => now(),
            'unione_status' => 'delivered',
            'opened_at' => now(),
            'confirmed_at' => now(),
            'paid_at' => now(),
            'archived_at' => now(),
        ]);

        $a101 = new A101();
        $a101->cancelOtherAccruals($accrualNew);

        $response = $this->get('/accrual/' .  $accrualOld->uuid);
        $response->assertStatus(200);
        $response->assertSeeText('Счет устарел');
    }

    public function testNegativeSum()
    {
        // Для прохождения этого теста необходимо создать файл
        // /storage/app/mailru_public_key
        /*
         * При ошибке в квитанции (отрицательная сумма) отображается
         * сообщение об ошибке
         */
        $accrual1 = Accrual::factory()->create([
            'account' => 'ТЕСТ1',
            'payee' => 'a101',
            'sum' => -100,
            'period' => date('Ym', strtotime('previous month')),
            'created_at' => new Carbon('previous month'),
            'sent_at' => now(),
            'unione_status' => 'delivered',
            'opened_at' => new Carbon(),
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => null,
        ]);
        $response = $this->get('/accrual/' .  $accrual1->uuid . '/pay');
        $response->assertStatus(200);
        $response->assertSeeText('произошла ошибка');
    }

    public function testUnknownAccrual()
    {
        /*
         * Для несуществующей квитанции выдается 404
         */
        $response = $this->get('/accrual/INVALID-UUID');
        $response->assertStatus(404);
    }
}
