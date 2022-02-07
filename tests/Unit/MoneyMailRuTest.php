<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\MoneyMailRu\MoneyMailRu;
use App\MoneyMailRu\Exception;

class MoneyMailRuTest extends TestCase
{
    /**
     * Test that callback interface exists.
     *
     * @return void
     */
    public function testCallbackInterfaceExists()
    {
        $response = $this->post(
            '/api/mailru',
            []
        );

        $response->assertStatus(200);
    }

    /**
     * Test that connection to Mailru works.
     *
     * @return void
     */
    public function testConnects()
    {
        $module = app(MoneyMailRu::class);
        $result = $module->request('merchant/info');
        $this->assertTrue($result['result_code'] === 0);
    }
}
