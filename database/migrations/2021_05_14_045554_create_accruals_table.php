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
            $table->string('period', 6)->comment('Период начислений. "202105" - май 2021 года');
            $table->string('person')->comment('"ФизическоеЛицо"');
            $table->string('full_name')->comment('"ФИО"');
            $table->string('email')->comment('"Email"');
            $table->string('account')->comment('"ЛицевойСчет" - уникальный идентификатор лицевого счета');
            $table->string('account_name')->comment('"ЛС" - наименование лицевого счета');
            $table->decimal('sum', 10, 2)->comment('"СуммаОплаты"');
            $table->decimal('sum_accrual', 15, 2)->comment('"СуммаНачисления"');
            $table->decimal('sum_advance', 15, 2)->comment('"Аванс"');
            $table->decimal('sum_debt', 15, 2)->comment('"СуммаДолга"');
            $table->string('org')->comment('"Организация"');
            $table->string('org_name')->comment('"ОрганизацияНаименование"');
            $table->string('org_account')->comment('"НаименованиеБанковскогоСчетаУК"');
            $table->date('date_a101')->nullable()->comment('"ДатаВыгрузки"');
            $table->text('url_bank')->nullable()->comment('Ссылка для оплаты на страницу банка');
            $table->string('transaction_id')->nullable()->comment('Номер транзакции у Mail.ru');
            $table->timestamps();
            $table->timestamp('sent_at')->nullable()->comment('Время отправки письма');
            $table->timestamp('opened_at')->nullable()->comment('Время открытия письма клиентом');
            $table->timestamp('confirmed_at')->nullable()->comment('Время нажатия клиентом ссылки "Оплатить"');
            $table->timestamp('payed_at')->nullable()->comment('Время проведения платежа');
            $table->timestamp('archived_at')->nullable()->comment('Время когда квитанция потеряла актуальность');
            $table->string('comment')->nullable()->comment('Пояснение текущего статуса');
            $table->text('callback_data')->nullable()->comment('Содержимое колбека от Mail.ru о статусе платежа');
            $table->text('back_data')->nullable()->comment('Содержимое запроса при возвращении пользователя после оплаты');
            $table->unique(['period', 'person', 'org']);
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
