<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccrualsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accruals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->decimal('sum', 10, 2)->comment('"СуммаОплаты"');
            $table->string('period', 6)->comment('Период начислений. "202105" - май 2021 года');
            $table->string('account')->comment('"ЛС" - наименование лицевого счета');
            $table->string('email')->comment('"Email"');
            $table->string('name')->comment('"ФИО"');
            $table->text('url_bank')->nullable()->comment('Ссылка для оплаты на страницу банка');
            $table->string('transaction_id')->nullable()->comment('Номер транзакции у Mail.ru');
            $table->timestampsTz();
            $table->timestampTz('sent_at')->nullable()->comment('Время отправки письма');
            $table->timestampTz('opened_at')->nullable()->comment('Время открытия письма клиентом');
            $table->timestampTz('confirmed_at')->nullable()->comment('Время нажатия клиентом ссылки "Оплатить"');
            $table->timestampTz('paid_at')->nullable()->comment('Время проведения платежа');
            $table->timestampTz('archived_at')->nullable()->comment('Время когда квитанция потеряла актуальность');
            $table->string('comment')->nullable()->comment('Пояснение текущего статуса');
            $table->text('callback_data')->nullable()->comment('Содержимое колбека от Mail.ru о статусе платежа');
            $table->text('back_data')->nullable()->comment('Содержимое запроса при возвращении пользователя после оплаты');
            // $table->unique(['period', 'account']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accruals');
    }
}
