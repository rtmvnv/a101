@include('internal/header', ['menu' => 'delivery', 'title' => 'Не доставленные квитанции'])

<div class="container px-3 pb-3 mb-3 bg-light">
    <form method="GET" action="/internal/delivery" class="row">
        <div class="col-auto">
            <label for="start" class="col-form-label">День</label>
        </div>
        <div class="col-auto">
            <input type="date" class="form-control" id="start" name="start" value="{{ $start }}">
        </div>
        <div class="col-auto">
            <label for="interval" class="col-form-label">Интервал</label>
        </div>
        <div class="col-auto">
            <select class="form-select" name="interval" id="interval">
                @if ($interval == 'day')
                <option value="day" selected>день</option>
                @else
                <option value="day">день</option>
                @endif
                @if ($interval == 'week')
                <option value="week" selected>неделя</option>
                @else
                <option value="week">неделя</option>
                @endif
                @if ($interval == 'month')
                <option value="month" selected>месяц</option>
                @else
                <option value="month">месяц</option>
                @endif
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Показать</button>
        </div>
    </form>
</div>

<div class="container">
    <table class="table table-hover">
        <thead>
            <tr>
                <th scope="col">Лицевой счет</th>
                <th scope="col">Период</th>
                <th scope="col">E-mail</th>
                <th scope="col">Статус</th>
                <th scope="col">Коментарий</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($accounts as $account)
            <tr>
                <td><a href="{{ $account->account_link }}">{{ $account->account }}</a></td>
                <td>{{ $account->period }}</td>
                <td>
                    @foreach ($account->emails as $email)
                    <a href="{{ $email->link }}">{{  $email->address }}</a>
                    @endforeach
                </td>
                <td>{{ $account->unione_status }}</td>
                <td>{{ $account->comment }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@include('internal/footer')
