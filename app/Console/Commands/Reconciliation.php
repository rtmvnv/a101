<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\A101;
use App\Models\Accrual;
use Carbon\Carbon;
use PhpImap\Mailbox;

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

        $mailbox = new Mailbox(
            config('services.payments_imap.url'),
            config('services.payments_imap.username'),
            config('services.payments_imap.password'),
            false,
            // 'UTF-8' // Server encoding (optional)
        );

        // Mail.ru doesn't support the FROM search criterion
        // https://ru.stackoverflow.com/questions/289544/mail-ru-imap-unsupported-search-criterion
        $mailsIds = $mailbox->searchMailbox('SINCE "' . $date->format('d M Y') . '"');

        if (!$mailsIds) {
            throw new \Exception('No mails since ' . $date->format('Y-m-d'), 90842523);
        }

        $subject = $date->format(config('services.reconciliation.subject'));

        /**
         * Find the message
         */
        foreach ($mailsIds as $mailId) {
            $header = $mailbox->getMailHeader($mailId);
            if ($header->senderAddress != config('services.reconciliation.from')) {
                continue;
            }

            if (strcmp($header->subject, $subject) !== 0) {
                continue;
            }

            /**
             * Remove all files in the temp directory left from the previous run
             */
            $files = glob(storage_path('reconciliation') . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            /**
             * Download the message
             */
            $message = $mailbox->getMail($mailId);
            $attachements = $message->getAttachments();
            if (!$attachements) {
                throw new \Exception(
                    "Mail message '{$message->subject}' has no attachements",
                    15908295
                );
            }

            echo $message->subject . PHP_EOL;

            /**
             * Save attachement to disk
             */
            $attachement = reset($attachements); // Get the first attachement
            $filePath = storage_path('reconciliation' . DIRECTORY_SEPARATOR . $attachement->name);
            $attachement->setFilePath($filePath);
            $attachement->saveToDisk();

            /**
             * Process attachement
             */
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                throw new \qubz\Exception('Cannot open csv file:', 87662526);
            }

            // Первая строка содержит заголовки столбцов
            // [
            //     [0] => №                         1
            //     [1] => "Дата платежа"            18:39:52 20.10.2021 (1634744392)
            //     [2] => "Номер платежа"           66876-48660-30924-21270
            //     [3] => "Транзакция магазина"     60700-63751-21295-59286
            //     [4] => "Транзакция Принципала"   f61073b3-6844-4b75-b75f-28e46405037e
            //     [5] => "Код валюты"              TEST
            //     [6] => "Сумма"                   677.51
            //     [7] => "Вознаграждение"          33.88
            //     [8] => "Сумма к перечислению"    643.63
            //     [9] => "Платежный тип"
            //     [10] => "Детали"                 {"pan_country_code":"BRA"}
            //     [11] => "Номер карты"            555555..5599
            //     [12] => "Дата АБС"
            // ]
            $line = mb_convert_encoding(fgets($handle), 'utf-8', $attachement->charset);
            $header = array_map('trim', explode(';', $line));

            $result = []; // Итоговый список расхождений

            //
            // Перебрать все строки реестра
            //
            $transactions = []; // Копия реестра
            while (($line = fgets($handle)) !== false) {
                $line = mb_convert_encoding($line, 'utf-8', $attachement->charset);
                $transaction = array_map('trim', explode(';', $line));
                $transactions[$transaction[4]] = $transaction;

                $accrual = Accrual::where('uuid', $transaction[4])->first();
                if (empty($accrual)) {
                    $result[] = [
                        'uuid' => $transaction[4],
                        'message' => 'Транзакция есть в реестре Mail.ru, но нет в базе данных'
                    ];
                    continue;
                }
            }

            //
            // Перебрать все записи в базе данных
            //
            $accruals = Accrual::whereDate('paid_at', $date->format('Y-m-d'))->get();
            foreach ($accruals as $accrual) {
                if (!isset($transactions[$accrual->uuid])) {
                    $result[] = [
                        'uuid' => $accrual->uuid,
                        'message' => 'Транзакция есть в базе данных, но нет в реестре Mail.ru'
                    ];
                    continue;
                }
            }
        }

        print_r($result);

        return 0;
    }
}
