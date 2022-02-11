<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
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
        $result = Accrual::parseEmail('  ,example@example.com; ');
        $this->assertEquals('example@example.com', $result[0]);
        $this->assertEquals(1, count($result));

        $result = Accrual::parseEmail('example@example.com; example1@example.com');
        $this->assertEquals('example@example.com', $result[0]);
        $this->assertEquals(2, count($result));

        $result = Accrual::parseEmail('example@example.com; 1@example.com 2@example.com ,3@example.com');
        $this->assertEquals('example@example.com', $result[0]);
        $this->assertEquals(4, count($result));

        $this->expectExceptionMessage('Incorrect email');
        $result = Accrual::parseEmail('test example@example.com');
    }
}
