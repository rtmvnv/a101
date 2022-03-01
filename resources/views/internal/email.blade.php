@include('internal/header', ['menu' => 'email', 'title' => 'Информация об адресе e-mail'])

<div class="container px-3 pb-3 mb-3 bg-light">
    <form method="GET" action="/internal/emails" class="row">
        <div class="col-auto">
            <label for="account" class="col-form-label">E-mail</label>
        </div>
        <div class="col-auto">
            <input type="string" class="form-control" id="email" name="email" value="{{ $email }}">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Показать</button>
        </div>
    </form>
</div>

@if ( !empty($alert['message']) )
<!-- https://www.w3schools.com/bootstrap5/bootstrap_alerts.php -->
<div class="container">
    <div class="alert alert-{{ $alert['type'] }} alert-dismissible fade show" role="alert">
        {{ $alert['message'] }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
@endif


@if ( !empty($suppression['message']) )
<div class="container px-3 pb-3 mb-3">
    <div class="card">
        <div class="card-body">
            <strong class="card-title">Блокировка</strong>
            <p class="card-text">{{ $suppression['message'] }}</p>
            @if ( $suppression['show_button'])
            <div class="card-footer">
                <form method="GET" action="/internal/emails" class="row">
                    <input type="hidden" id="email" name="email" value="{{ $email }}">
                    <input type="hidden" id="unblock" name="unblock" value="true">
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Снять блокировку</button>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

@if ( !empty($email) )
<div class="container px-3 pb-3 mb-3">
    <div class="card">
        <div class="card-body">
            <strong class="card-title">Лицевые счета</strong>
            <p class="card-text">
                @foreach ($accounts as $account)
                <a href="{{ $account['link'] }}">{{ $account['value'] }}</a>
                @endforeach
            </p>
        </div>
    </div>
</div>
@endif

<div class="container">
    <table class="table table-hover">
        <thead>
            <tr>
                <th scope="col">Лицевой счет</th>
                <th scope="col">e-mail</th>
                <th scope="col">Ошибка</th>
                <th scope="col">Подробности</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($failed_emails as $failed_email)
            <tr>
                <th scope="row">{{ $failed_email['account'] }}</th>
                <td>{{ $failed_email['email'] }}</td>
                <td>{{ $failed_email['explanation'] }}</td>
                <td>{{ $failed_email['status'] }} {{ $failed_email['delivery_status'] }} {{ $failed_email['destination_response'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@include('internal/footer')
