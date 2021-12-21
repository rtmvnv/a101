<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\UniOne\Message;

class UniOneMessageTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testParsesMultipleEmails()
    {
        $message = new Message();
        $message->to(' test1@example.com ; test2@example.com , test3@example.com, test1@example.com ', 'User Name');
        print_r($message);
        $this->assertEquals(3, count($message->recipients));

        $message = new Message();
        $message->to(' , test4@example.com ; some text ', 'User Name');
        print_r($message);
        $this->assertEquals(1, count($message->recipients));
    }
}
