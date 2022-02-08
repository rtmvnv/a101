@include('internal/header')
@include('internal/menu')

<div class="container">
    <h1>Ошибки e-mail</h1>

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
