@include('internal/header', ['menu' => 'account', 'title' => 'Квитанции для лицевого счета'])

<div class="container px-3 pb-3 mb-3 bg-light">
    <form method="POST" action="/internal/account" class="row">
        @csrf
        <div class="col-auto">
            <label for="account" class="col-form-label">Лицевой счет</label>
        </div>
        <div class="col-auto">
            <input type="string" class="form-control" id="account" name="account" value="{{ $account }}">
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
                <th scope="col">Период</th>
                <th scope="col">Сумма</th>
                <th scope="col">E-mail</th>
                <th scope="col">Результат</th>
                <th scope="col">Коментарий</th>
                <th scope="col">Дата</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($accruals as $accrual)
            <tr>
                <td>{{ $accrual->period }}</td>
                <td>{{ $accrual->sum }}</td>
                <td>
                    @foreach ($accrual->emails as $email)
                    <a href="{{ $email->link }}">{{  $email->address }}</a>
                    @endforeach
                </td>
                <td>{{ $accrual->unione_status }}</td>
                <td>{{ $accrual->comment }}</td>
                <td>{{ $accrual->created_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@include('internal/footer')
