<?php

namespace App;

use App\UniOne;
use App\UniOneException;

class UniOneMessage
{
    public $fromEmail;
    public $fromName;
    public $subject;
    public $recipients = [];
    public $bodyHtml;
    public $bodyPlain;

    public function to($email, $name = '')
    {
        $recipient = ['email' => $email];
        if (!empty($name)) {
            $recipient['substitutions']['to_name'] = $name;
        }
        $this->recipients[] = $recipient;

        return $this;
    }

    public function from($email, $name = '')
    {
        $this->fromEmail[] = $email;
        if (!empty($name)) {
            $this->toName = $name;
        }
        
        return $this;
    }

    public function subject($subject)
    {
        $this->subject = $subject;
        
        return $this;
    }

    public function plain($bodyPlain)
    {
        $this->bodyPlain = $bodyPlain;
        
        return $this;
    }

    public function html($bodyHtml)
    {
        $this->bodyHtml = $bodyHtml;
        
        return $this;
    }

    /**
     * Returns message body to be used by the UniOne->request function.
     * 
     * @return array
     */
    public function build()
    {
        $body = ['message' => []];

        // recipients
        if (empty($this->recipients)) {
            throw new UniOneException('Message has no recipients');
        }
        foreach ($this->recipients as $recipient) {
            $body['message']['recipients'][] = $recipient;
        }

        // from_email
        if (empty($this->fromEmail)) {
            $this->fromEmail = config('services.unione.from_email');
        }
        $body['message']['from_email'] = $this->fromEmail;
        
        // from_name
        if (empty($this->fromName)) {
            $this->fromName = config('services.unione.from_name');
        }
        $body['message']['from_name'] = $this->fromName;
        
        // subject
        if (empty($this->subject)) {
            throw new UniOneException('Message has no subject');
        }
        $body['message']['subject'] = $this->subject;
        
        // bodyPlain
        if (empty($this->bodyPlain)) {
            throw new UniOneException('Message has no body');
        }
        $body['message']['body']['plaintext'] = $this->bodyPlain;

        // bodyHtml
        if (!empty($this->bodyHtml)) {
            $body['message']['body']['html'] = $this->bodyHtml;
        }

        return $body;
    }

    /**
     * Метод для отправки писем вашим подписчикам.
     * https://docs.unione.ru/web-api-ref?php#email-send
     *
     * @return array
     */
    function send() {
        $unione = app(UniOne::class);
        return $unione->request('email/send.json', $this->build());
    }
}
