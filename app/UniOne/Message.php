<?php

namespace App\UniOne;

use App\UniOne\UniOne;
use App\UniOne\Exception;

class Message
{
    public $fromEmail;
    public $fromName;
    public $subject;
    public $recipients = [];
    public $bodyHtml;
    public $bodyPlain;
    public $attachments = [];
    public $inline_attachments = [];

    /**
     * $email
     */
    public function to($email, $name = '')
    {
        $email = str_replace(',', ';', $email);
        $emails = explode(';', $email);
        $emails = array_unique($emails);

        foreach ($emails as $value) {
            $value = trim($value);
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $recipient = ['email' => $value];
            if (!empty($name)) {
                $recipient['substitutions']['to_name'] = trim($name);
            }
            $this->recipients[] = $recipient;
        }

        return $this;
    }

    public function from($email, $name = '')
    {
        $this->fromEmail = $email;
        if (!empty($name)) {
            $this->fromName = $name;
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
     *
     * $content должен быть закодирован в base64
     */
    public function addAttachment($type, $name, $content)
    {
        $this->attachments[] = ['type' => $type, 'name' => $name, 'content' => $content];

        return $this;
    }

    /**
     *
     * $content должен быть закодирован в base64
     */
    public function addInlineAttachment($type, $name, $content)
    {
        $this->inline_attachments[] = ['type' => $type, 'name' => $name, 'content' => $content];

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
            throw new Exception('Message has no recipients');
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
            throw new Exception('Message has no subject');
        }
        $body['message']['subject'] = $this->subject;

        // bodyPlain
        if (empty($this->bodyPlain)) {
            throw new Exception('Message has no body');
        }
        $body['message']['body']['plaintext'] = $this->bodyPlain;

        // bodyHtml
        if (!empty($this->bodyHtml)) {
            $body['message']['body']['html'] = $this->bodyHtml;
        }

        // attachments
        if (!empty($this->attachments)) {
            $body['message']['attachments'] = $this->attachments;
        }

        // inlineAttachments
        if (!empty($this->inline_attachments)) {
            $body['message']['inline_attachments'] = $this->inline_attachments;
        }

        return $body;
    }
}
