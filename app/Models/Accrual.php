<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Accrual extends Model
{
    use HasFactory;

    protected $primaryKey = 'uuid';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $dateFormat = 'c';

    protected $appends = [
        'period_text',
        'valid_till',
        'link_confirm',
        'link_pay',
        'name_case',
        'status',
        'estate',
    ];

    /**
     * Constructor
     */
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        $this->uuid = (string) Str::uuid();
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
    public function getNameCaseAttribute()
    {
        return mb_convert_case($this->name, MB_CASE_TITLE);
    }

    /**
     * Статус счета
     */
    public function getStatusAttribute()
    {
        if (!empty($this->paid_at)) {
            return 'paid';
        }

        if (!empty($this->archived_at)) {
            return 'archived';
        }

        if (!empty($this->confirmed_at)) {
            return 'confirmed';
        }

        if (empty($this->opened_at)) {
            return 'opened';
        }

        if (empty($this->sent_at)) {
            return 'sent';
        }

        return 'created';
    }

    /**
     * Жилой комплекс
     * Определяется по первым двум буквам лицевого счета.
     * spanish = Испанские кварталы: ИКХХХХХХХХ, БВХХХХХХХХ
     * scandinavia = Скандинавия: СКХХХХХХХХ, ЛПХХХХХХХХ, ЭГХХХХХХХХ
     * nights = Белые ночи: ПРХХХХХХХХ
     * spanish2 = Испанские Кварталы 2: ПМХХХХХХХХ
     */
    public function getEstateAttribute()
    {
        $estate = mb_strtoupper(mb_substr($this->account, 0, 2));

        if ($estate === 'ИК' or $estate === 'БВ') {
            return 'spanish';
        }
        if ($estate === 'СК' or $estate === 'ЛП' or $estate === 'ЭГ') {
            return 'scandinavia';
        }
        if ($estate === 'ПР') {
            return 'nights';
        }
        if ($estate === 'ПМ') {
            return 'spanish2';
        }

        throw new \Exception("Estate '$estate' is unknown");
    }
}
