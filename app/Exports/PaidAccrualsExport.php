<?php

namespace App\Exports;

use App\Models\Accrual;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class PaidAccrualsExport implements FromQuery, WithMapping, WithHeadings
{
    protected $from;
    protected $to;

    public function __construct($day)
    {
        $this->from = (new Carbon($day))->startOfDay();
        $this->to = (new Carbon($day))->endOfDay();
    }

    public function query()
    {
        return Accrual::query()
            ->where('paid_at', '>=', $this->from)
            ->where('paid_at', '<=', $this->to);
    }

    /**
     * @var Accrual $accrual
     */
    public function map($accrual): array
    {
        return [
            $accrual->id,
            $accrual->account,
            $accrual->sum,
            $accrual->period,
            $accrual->paid_at,
            $accrual->name,
            $accrual->email,
            $accrual->transaction_id,
            $accrual->uuid,
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'Лицевой счет',
            'Сумма',
            'Период',
            'Дата оплаты',
            'ФИО',
            'e-mail',
            'transaction_id',
            'uuid',
        ];
    }
}
