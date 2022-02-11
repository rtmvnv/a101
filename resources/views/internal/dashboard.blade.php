<!doctype html>
<html lang="ru">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

    <title>A101 dashboard</title>
</head>

<body>
    <div class="container">
        <h1>Лицевые счета</h1>

        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">Период</th>
                    <th scope="col">Всего</th>
                    <th scope="col">Отправлено</th>
                    <th scope="col">Не отправлено</th>
                    <th scope="col">Доставлено</th>
                    <th scope="col">Не доставлено</th>
                    <th scope="col">Оплачено</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th scope="row">{{ $day0['title']}}</th>
                    <td>{{ $day0['total']}}</td>
                    <td>{{ $day0['sent']}}</td>
                    <td>{{ $day0['not_sent']}}</td>
                    <td>{{ $day0['delivered']}}</td>
                    <td>{{ $day0['not_delivered']}}</td>
                    <td>{{ $day0['paid']}}</td>
                </tr>
                <tr>
                    <th scope="row">{{ $day1['title']}}</th>
                    <td>{{ $day1['total']}}</td>
                    <td>{{ $day1['sent']}}</td>
                    <td>{{ $day1['not_sent']}}</td>
                    <td>{{ $day1['delivered']}}</td>
                    <td>{{ $day1['not_delivered']}}</td>
                    <td>{{ $day1['paid']}}</td>
                </tr>
                <tr>
                    <th scope="row">{{ $day2['title']}}</th>
                    <td>{{ $day2['total']}}</td>
                    <td>{{ $day2['sent']}}</td>
                    <td>{{ $day2['not_sent']}}</td>
                    <td>{{ $day2['delivered']}}</td>
                    <td>{{ $day2['not_delivered']}}</td>
                    <td>{{ $day2['paid']}}</td>
                </tr>
                <tr>
                    <th scope="row">{{ $current_month['title']}}</th>
                    <td>{{ $current_month['total']}}</td>
                    <td>{{ $current_month['sent']}}</td>
                    <td>{{ $current_month['not_sent']}}</td>
                    <td>{{ $current_month['delivered']}}</td>
                    <td>{{ $current_month['not_delivered']}}</td>
                    <td>{{ $current_month['paid']}}</td>
                </tr>
                <tr>
                    <th scope="row">{{ $previous_month['title']}}</th>
                    <td>{{ $previous_month['total']}}</td>
                    <td>{{ $previous_month['sent']}}</td>
                    <td>{{ $previous_month['not_sent']}}</td>
                    <td>{{ $previous_month['delivered']}}</td>
                    <td>{{ $previous_month['not_delivered']}}</td>
                    <td>{{ $previous_month['paid']}}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</body>

</html>
