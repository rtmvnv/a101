<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Accrual;
use Carbon\Carbon;

class GetAccrual extends Command
{
    protected $events = []; // Raw list of events
    protected $mongoEvents; // MongoDB collection

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accrual:get
        {id : ID or UUID of the accrual}
        {--a101 : Show dialogs with A101}
        {--unione : Show dialogs with UniOne}
        {--mailru : Show dialogs with Mail.ru}
        {--orangedata : Show dialogs with Orange Data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show all available information about the given accrual';

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
        if (is_numeric($this->argument('id'))) {
            $accrual = Accrual::where('id', $this->argument('id'))->first();
        } else {
            $accrual = Accrual::where('uuid', $this->argument('id'))->first();
        }

        if (empty($accrual)) {
            $this->error('Accrual "' . $this->argument('id') . '" not found');
            return 1;
        }

        $this->newLine();
        $this->info('ACCRUAL');
        $this->line('            id: ' . $accrual->id);
        $this->line('          uuid: ' . $accrual->uuid);
        $this->line('        status: ' . $accrual->status);
        $this->line('       comment: ' . $accrual->comment);
        $this->line('           sum: ' . $accrual->sum);
        $this->line('        period: ' . $accrual->period);
        $this->line('       account: ' . $accrual->account);
        $this->line('        e-mail: ' . $accrual->email);
        $this->line('          name: ' . $accrual->name);
        $this->line('    created_at: ' . (new Carbon($accrual->created_at))->format('c'));
        $this->line('    updated_at: ' . (new Carbon($accrual->updated_at))->format('c'));
        $this->newLine();
        $this->info('unione');
        $this->line('       sent_at: ' . (new Carbon($accrual->sent_at))->format('c'));
        $this->line('     opened_at: ' . (new Carbon($accrual->opened_at))->format('c'));
        $this->line(' unione_status: ' . $accrual->unione_status);
        $this->line('     unione_at: ' . $accrual->unione_at);
        $this->line('     unione_id: ' . $accrual->unione_id);
        $this->newLine();
        $this->info('mailru');
        $this->line('  confirmed_at: ' . (new Carbon($accrual->confirmed_at))->format('c'));
        $this->line('transaction_id: ' . $accrual->transaction_id);
        $this->line('     back_data: ' . json_encode(json_decode($accrual->back_data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->newLine();
        $this->info('orange data');
        $this->line(' fiscalized_at: ' . $accrual->fiscalized_at);

        /*
         * Collect log events
         */
        $this->mongoEvents = app('mongo_events');
        $this->findIncomingAccruals($accrual);
        $this->findOutgoingMailru($accrual);
        $this->findIncomingMailru($accrual->transaction_id);
        $this->findOutgoingOrangedata($accrual);
        $this->findIncomingOrangedata($accrual);

        $this->newLine();
        $this->info('EVENTS');

        $includedTypes = [];
        if (
            !$this->option('a101')
            and !$this->option('unione')
            and !$this->option('mailru')
            and !$this->option('orangedata')
        ) {
            $includedTypes = [
                'incoming-api-accruals',
                'outgoing-unione',
                'incoming-api-unione',
                'outgoing-mailru',
                'incoming-api-mailru',
                'outgoing-orangedata',
                'incoming-api-orangedata',
            ];
        } else {
            if ($this->option('a101')) {
                $includedTypes[] = 'incoming-api-accruals';
            }
            if ($this->option('unione')) {
                $includedTypes[] = 'outgoing-unione';
                $includedTypes[] = 'incoming-api-unione';
            }
            if ($this->option('mailru')) {
                $includedTypes[] = 'outgoing-mailru';
                $includedTypes[] = 'incoming-api-mailru';
            }
            if ($this->option('orangedata')) {
                $includedTypes[] = 'outgoing-orangedata';
                $includedTypes[] = 'incoming-api-orangedata';
            }
        }

        foreach ($this->events as $event) {
            if (in_array($event['message'], $includedTypes)) {
                $this->newLine();
                $this->info($event['message']);
                $this->line(json_encode($event, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }

        return 0;
    }

    /**
     * Find an initial request from 1C
     */
    protected function findIncomingAccruals($accrual)
    {
        $query = [
            'message' => 'incoming-api-accruals',
            'context.response.data.accrual_id' => $accrual->uuid,
        ];

        $this->findOutgoingUnioneJobid($accrual->unione_id);

        if ($this->mongoEvents->countDocuments($query) === 0) {
            $this->error('incoming-api-accruals events not found');
            return 1;
        };

        $events = $this->mongoEvents->find($query);
        foreach ($events as $event) {
            $event = bsondocumentToArray($event);
            $this->events[$event['_id']] = $event;
            $this->findOutgoingUnioneRunid($event['extra']['run_id']);
        }
    }

    /*
     * Найти исходящие запросы к Unione по run_id из запроса от 1С
     */
    protected function findOutgoingUnioneRunid($runId)
    {
        $query = [
            'message' => 'outgoing-unione',
            'extra.run_id' => $runId,
        ];

        $events = $this->mongoEvents->find($query);
        foreach ($events as $event) {
            $event = bsondocumentToArray($event);
            $this->events[$event['_id']] = $event;
            $this->findIncomingUnione($event['context']['response']['job_id']);
        }
    }

    /*
     * Найти исходящие запросы к Unione по unione_id из БД
     */
    protected function findOutgoingUnioneJobid($jobId)
    {
        $query = [
            'message' => 'outgoing-unione',
            'context.response.job_id' => $jobId,
        ];

        $events = $this->mongoEvents->find($query);
        foreach ($events as $event) {
            $event = bsondocumentToArray($event);
            $this->events[$event['_id']] = $event;
            $this->findIncomingUnione($event['context']['response']['job_id']);
        }
    }

    /**
     * Найти входящие запросы от Unione
     */
    protected function findIncomingUnione($jobId)
    {
        $query = [
            'message' => 'incoming-api-unione',
            'context.request.job_id' => $jobId,
        ];

        $events = $this->mongoEvents->find($query);
        foreach ($events as $event) {
            $event = bsondocumentToArray($event);
            $this->events[$event['_id']] = $event;
        }
    }

    /*
     * Найти исходящие запросы к Mailru
     */
    protected function findOutgoingMailru($accrual)
    {
        $query = [
            'message' => 'outgoing-mailru',
            'context.request.body.issuer_id' => $accrual->uuid,
        ];

        $events = $this->mongoEvents->find($query);
        foreach ($events as $event) {
            $event = bsondocumentToArray($event);
            $this->events[$event['_id']] = $event;
            if (isset($event['context']['response']['body'])) {
                $this->findIncomingMailru($event['context']['response']['body']['transaction_id']);
            }
        }
    }

    /*
     * Найти входящие запросы от Mailru
     */
    protected function findIncomingMailru($transactionId)
    {
        $query = [
            'message' => 'incoming-api-mailru',
            'context.request_decoded.body.transaction_id' => $transactionId,
        ];

        $events = $this->mongoEvents->find($query);
        foreach ($events as $event) {
            $event = bsondocumentToArray($event);
            $this->events[$event['_id']] = $event;
        }
    }

    /*
     * Найти исходящие запросы к Orange Data
     */
    protected function findOutgoingOrangedata($accrual)
    {
        $query = [
            'message' => 'outgoing-orangedata',
            'context.request.id' => $accrual->uuid,
        ];

        $events = $this->mongoEvents->find($query);
        foreach ($events as $event) {
            $event = bsondocumentToArray($event);
            $this->events[$event['_id']] = $event;
        }
    }

    /*
     * Найти входящие запросы от Orange Data
     */
    protected function findIncomingOrangedata($accrual)
    {
        $query = [
            'message' => 'incoming-api-orangedata',
            'context.request.id' => $accrual->uuid,
        ];

        $events = $this->mongoEvents->find($query);
        foreach ($events as $event) {
            $event = bsondocumentToArray($event);
            $this->events[$event['_id']] = $event;
        }
    }
}
