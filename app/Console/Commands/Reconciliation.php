<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\A101;
use Carbon\Carbon;
use Webklex\IMAP\Facades\Client;


class Reconciliation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'a101:reconciliation {date=yesterday}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform reconciliation from an e-mail';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /**
         * Documentation on the PHP-IMAP library
         * https://www.php-imap.com/api/folder
         */
        $a101 = new A101();
        $date = new Carbon($this->argument('date'));
        $this->info("Reconciliation for " . $date->format('Y-m-d'));

        $imap = Client::account('payments');
        $imap->connect();
        $query = $imap->getFolderByPath('INBOX')->query();
            // ->leaveUnread()
            // ->setFetchBody(false)
            // ->setFetchOrderDesc();

            // $messages = $query->since($date)->from('money@corp.mail.ru')->limit(10)->get();
            // $messages = $query->since($date)->from('stat@inplat.ru')->limit(10)->get();
            // $messages = $query->since($date)->where([['FROM' => 'stat@inplat.ru']])->get();
            $messages = $query->since($date)->limit(10)->get();

            // echo $query->since($date)->count() . PHP_EOL;
            
            echo $messages->count() . PHP_EOL;
            // $messages = $query->since($date)->get();

        foreach ($messages as $message) {
            print_r($this->decode((string)$message->from) . PHP_EOL);
        }

        return 0;
    }

    private function decode($value) {
        if (is_array($value)) {
            return $this->decodeArray($value);
        }
        $original_value = $value;
        $decoder = 'iconv';

        if(strpos(strtolower($value), '=?windows-1251?') !== false || strpos(strtolower($value), '=?koi8-r') !== false) {
            $value = Str::replace(['=?KOI8-R?B?', '?='], '', $value);
            $value = base64_decode($value); // decode base64 data
            $value = iconv('koi8-r', 'UTF-8', $value); // convert source data to UTF-8
        }

        if ($value !== null) {
            $is_utf8_base = $this->is_uft8($value);

            if($decoder === 'utf-8' && extension_loaded('imap')) {
                $value = \imap_utf8($value);
                $is_utf8_base = $this->is_uft8($value);
                if ($is_utf8_base) {
                    $value = mb_decode_mimeheader($value);
                }
                if ($this->notDecoded($original_value, $value)) {
                    $decoded_value = $this->mime_header_decode($value);
                    if (count($decoded_value) > 0) {
                        if(property_exists($decoded_value[0], "text")) {
                            $value = $decoded_value[0]->text;
                        }
                    }
                }
            }elseif($decoder === 'iconv' && $is_utf8_base) {
                $value = iconv_mime_decode($value);
            }elseif($decoder === 'iconv') {
                $value = iconv_mime_decode($value);
            }elseif($is_utf8_base){
                $value = mb_decode_mimeheader($value);
            }

            if ($this->is_uft8($value)) {
                $value = mb_decode_mimeheader($value);
            }

            if ($this->notDecoded($original_value, $value)) {
                $value = $this->convertEncoding($original_value, $this->getEncoding($original_value));
            }
        }

        return $value;
    }
}
