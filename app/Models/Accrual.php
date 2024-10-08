<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

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
        'valid_till_overhaul',
        'base_url',
        'link_confirm',
        'link_pay',
        'link_back',
        'name_case',
        'status',
        'estate',
        'estate_text',
        'balance_text',
        'email_array',
    ];

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
     * Срок оплаты до 20-го числа следующего месяца
     */
    public function getValidTillOverhaulAttribute()
    {
        return Carbon::createFromFormat('Ym', $this->period)
            ->addMonth()
            ->startOfMonth()
            ->addDays(19)
            ->translatedFormat('d.m.Y');
    }

    /**
     * Ссылка на корень сайта (для вставки изображений)
     */
    public function getBaseUrlAttribute()
    {
        return config('app.url');
    }

    /**
     * Ссылка в письме
     */
    public function getLinkConfirmAttribute()
    {
        // Функция url() не работает, потому что при запросе по IP возвращает ответ тоже с IP,
        // а не доменным именем. А IP не работает, потому что сервер находится за VPN.
        // https://stackoverflow.com/questions/30093449/laravel-route-returning-naked-ip-address-instead-of-domain#comment111197221_30093449
        return config('app.url') . '/accrual/' . $this->uuid;
    }

    /**
     * Ссылка для подтверждения оплаты
     */
    public function getLinkPayAttribute()
    {
        return config('app.url') . '/accrual/' . $this->uuid . '/pay';
    }

    /**
     * Ссылка для возврата пользователя после оплаты
     */
    public function getLinkBackAttribute()
    {
        return config('app.url') . '/accrual/' . $this->uuid . '/back';
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

        if (!empty($this->opened_at)) {
            return 'opened';
        }

        if (!empty($this->sent_at)) {
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
        return 'unknown';
    }

    public function getEstateTextAttribute()
    {
        switch ($this->getEstateAttribute()) {
            case 'spanish':
                return 'ЖК "Испанские кварталы"';
                break;

            case 'scandinavia':
                return 'ЖК "Скандинавия"';
                break;

            case 'nights':
                return 'ЖК "Белые ночи"';
                break;

            case 'spanish2':
                return 'ЖК "Испанские кварталы 2"';
                break;

            default:
                return 'А101';
                break;
        }
    }

    public function getBalanceTextAttribute()
    {
        return number_format(-$this->sum, 2, ',', '.') . ' руб.';
    }

    public function getEmailArrayAttribute()
    {
        return self::parseEmail($this->email);
    }

    /**
     * Парсит строку на отдельные email.
     * Проверяет email на валидность.
     * Возвращает массив из email.
     * Если хотя бы один email невалиден - создает исключение.
     */
    public static function parseEmail($string)
    {
        $emails = preg_split('/( |,|;)/', $string, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($emails as $email) {
            $result = Validator::make(['email' => $email], [
                'email' => 'bail|required|email:rfc',
            ])->errors();
            if ($result->isNotEmpty()) {
                throw new \Exception('Некорректный формат email');
            }

            $result = Validator::make(['email' => $email], [
                'email' => 'bail|required|email:rfc,dns',
            ])->errors();
            if ($result->isNotEmpty()) {
                $domainName = substr(strrchr($email, "@"), 1);
                throw new \Exception('Несуществующий домен ' . $domainName);
            }
        }

        return $emails;
    }
}
