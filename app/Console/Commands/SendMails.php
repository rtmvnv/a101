<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UniOne;
use App\UniOneMessage;
use App\Models\Accrual;

class SendMails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'a101:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send all pending e-mail messages';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $accruals = Accrual::ToSend()->get();
        $unione = app(UniOne::class);

        $countSuccess = 0;
        $countFailed = 0;
        foreach ($accruals as $accrual) {
            $plain = view('mail_plain', $accrual->toArray())->render();
            $html = view('mail_html_' . $accrual->estate, $accrual->toArray())->render();

            $message = new UniOneMessage();
            $message->to($accrual->email, $accrual->full_name)
                ->subject("Квитанция по лицевому счету {$accrual->account} за {$accrual->period_text}")
                ->plain($plain)
                ->html($html);

            $result = $message->send();

            if ($result['status'] === 'success') {
                $accrual->sent_at = now();
                $accrual->save();
                $countSuccess++;
            } else {
                $countFailed++;
                print_r($result);
            }
        }

        $this->info($countSuccess . ' messages sent, ' . $countFailed . ' messages failed.');

        return 0;
    }
}
