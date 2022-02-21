<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Accrual;

class ParseEmailTest extends TestCase
{
    /**
     * Проверяет функцию Accrual::parseEmail()
     *
     * @return void
     */
    public function testEmails()
    {
        $result = Accrual::parseEmail('  ,null@vic-insurance.ru; ');
        $this->assertEquals('null@vic-insurance.ru', $result[0]);
        $this->assertEquals(1, count($result));

        $result = Accrual::parseEmail('null1@vic-insurance.ru; null2@vic-insurance.ru');
        $this->assertEquals('null1@vic-insurance.ru', $result[0]);
        $this->assertEquals(2, count($result));

        $result = Accrual::parseEmail('null@vic-insurance.ru; null1@vic-insurance.ru null2@vic-insurance.ru ,null3@vic-insurance.ru');
        $this->assertEquals('null@vic-insurance.ru', $result[0]);
        $this->assertEquals(4, count($result));

        $this->expectExceptionMessage('Некорректный формат email');
        $result = Accrual::parseEmail('test null@vic-insurance.ru');
    }
}
