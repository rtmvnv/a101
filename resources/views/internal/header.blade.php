<!doctype html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <title>A101 Internal</title>
  </head>
  <body>

  <div class="container d-flex justify-content-md-between p-3 bg-light">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                @if ($menu == 'overview')
                <a class="nav-link active" aria-current="page" href="/internal">Статистика</a>
                @else
                <a class="nav-link" href="/internal">Рассылка</a>
                @endif
            </li>
            <li class="nav-item">
                @if ($menu == 'delivery')
                <a class="nav-link active" aria-current="page" href="/internal/delivery">Не доставлено</a>
                @else
                <a class="nav-link" href="/internal/delivery">Не доставлено</a>
                @endif
            </li>
            <li class="nav-item">
                @if ($menu == 'account')
                <a class="nav-link active" aria-current="page" href="/internal/account">Лицевой счет</a>
                @else
                <a class="nav-link" href="/internal/account">Лицевой счет</a>
                @endif
            </li>
            <li class="nav-item">
                @if ($menu == 'email')
                <a class="nav-link active" aria-current="page" href="/internal/email">E-mail</a>
                @else
                <a class="nav-link" href="/internal/email">E-mail</a>
                @endif
            </li>
        </ul>

        <div class="col-md-2 text-end">
            <form method="POST" action="/internal/logout" class="mt-10">
                @csrf
                <button type="submit" class="btn btn-outline-primary me-2">Выйти</button>
            </form>
        </div>
</div>
<div class="container px-3 pb-1 bg-light"><p>{{ $title }}</p></div>
