<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <title>Оплата квитанции A101 по лицевому счету {{ $account_name }} за {{ $period_text }}</title>
    </head>
    <body>
        <p>Оплата по лицевому счету {{ $account_name }} за {{ $period_text }}.</p>
        <p>Сумма к оплате {{ $sum }} руб.</p>
        <p>Для проведения оплаты нажмите</p>
        <p><a href="{{ $link_pay }}">ОПЛАТИТЬ</a></p>
        <p>Переходя по ссылке Вы соглашаетесь на обработку ваших персональных данных.<p>
</body>
</html>



