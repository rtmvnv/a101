Уважаемый житель!

Во вложении счёт за коммунальные услуги.
Лицевой счет: {{ $account }}
Период: {{ $period_text }}
@if ($sum > 0)
Сумма: {{ $sum }}
Срок оплаты до: {{ $valid_till_etk2 }}
@else
Баланс: {{ $balance_text }}

Оплата не требуется.
@endif

С наилучшими пожеланиями,
ООО «ЭТК № 2»

Примечание: Это письмо создано автоматической службой рассылки
электронных счетов. Пожалуйста, не отвечайте на него.