<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Accrual extends Model
{
    use HasFactory;

    protected $appends = [
        'period_text',
        'valid_till',
        'link_confirm',
        'link_pay',
        'full_name_case',
        'status',
    ];

    /**
     * Счета, которые не были отправлены и не имеют ошибки.
     */
    public function scopeToSend($query)
    {
        // https://laravel.com/docs/8.x/eloquent#query-scopes
        return $query->where('sent_at', null)->where('failed_at', null);
    }

    /**
     * Период начислений в текстовом виде.
     */
    public function getPeriodTextAttribute()
    {
        $date = Carbon::createFromFormat('Ym', $this->period);
        return $date->translatedFormat('F Y');
    }

    /**
     * Срок оплаты до 10-го числа следующего месяца
     */
    public function getValidTillAttribute()
    {
        return Carbon::createFromFormat('Ym', $this->period)
            ->addMonth()
            ->startOfMonth()
            ->addDays(9)
            ->translatedFormat('d.m.Y');
    }

    /**
     * Ссылка в письме
     */
    public function getLinkConfirmAttribute()
    {
        return url('/') . '/' . $this->uuid;
    }

    /**
     * Ссылка для подтверждения оплаты
     */
    public function getLinkPayAttribute()
    {
        return url('/') . '/' . $this->uuid . '/pay';
    }

    /**
     * ФИО с заглавной буквы
     */
    public function getFullNameCaseAttribute()
    {
        return mb_convert_case($this->full_name, MB_CASE_TITLE);
    }

    /**
     * Статус счета
     */
    public function getStatusAttribute()
    {
        if (!empty($this->failed_at)) {
            return 'failed';
        }

        if (empty($this->sent_at)) {
            return 'created';
        }

        if (empty($this->opened_at)) {
            return 'sent';
        }

        if (empty($this->confirmed_at)) {
            return 'opened';
        }

        if (empty($this->completed_at)) {
            return 'confirmed';
        }

        return 'completed';
    }
}
