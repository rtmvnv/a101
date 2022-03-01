@include('internal/header', ['menu' => 'overview', 'title' => 'Статистика по лицевым счетам'])

<div class="container">
    <table class="table table-hover">
        <thead>
            <tr>
                <th scope="col">Период</th>
                <th scope="col">Всего</th>
                <th scope="col">Доставлено</th>
                <th scope="col">Не доставлено</th>
                <th scope="col">Оплачено</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th scope="row">{{ $day0['title'] }}</th>
                <td>{{ $day0['total'] }}</td>
                <td>{{ $day0['delivered'] }}</td>
                <td title="{{ $day0['statistics'] }}">
                    <a href="{{ $day0['not_delivered_link'] }}">{{ $day0['not_delivered'] }}</a>
                </td>
                <td>{{ $day0['paid'] }}</td>
            </tr>
            <tr>
                <th scope="row">{{ $day1['title'] }}</th>
                <td>{{ $day1['total'] }}</td>
                <td>{{ $day1['delivered'] }}</td>
                <td title="{{ $day1['statistics'] }}">
                    <a href="{{ $day1['not_delivered_link'] }}">{{ $day1['not_delivered'] }}</a>
                </td>
                <td>{{ $day1['paid'] }}</td>
            </tr>
            <tr>
                <th scope="row">{{ $day2['title'] }}</th>
                <td>{{ $day2['total'] }}</td>
                <td>{{ $day2['delivered'] }}</td>
                <td title="{{ $day2['statistics'] }}">
                    <a href="{{ $day2['not_delivered_link'] }}">{{ $day2['not_delivered'] }}</a>
                </td>
                <td>{{ $day2['paid'] }}</td>
            </tr>
            <tr>
                <th scope="row">{{ $day3['title'] }}</th>
                <td>{{ $day3['total'] }}</td>
                <td>{{ $day3['delivered'] }}</td>
                <td title="{{ $day3['statistics'] }}">
                    <a href="{{ $day3['not_delivered_link'] }}">{{ $day3['not_delivered'] }}</a>
                </td>
                <td>{{ $day3['paid'] }}</td>
            </tr>
            <tr>
                <th scope="row">{{ $day4['title'] }}</th>
                <td>{{ $day4['total'] }}</td>
                <td>{{ $day4['delivered'] }}</td>
                <td title="{{ $day4['statistics'] }}">
                    <a href="{{ $day4['not_delivered_link'] }}">{{ $day4['not_delivered'] }}</a>
                </td>
                <td>{{ $day4['paid'] }}</td>
            </tr>
            <tr>
                <th scope="row">{{ $day5['title'] }}</th>
                <td>{{ $day5['total'] }}</td>
                <td>{{ $day5['delivered'] }}</td>
                <td title="{{ $day5['statistics'] }}">
                    <a href="{{ $day5['not_delivered_link'] }}">{{ $day5['not_delivered'] }}</a>
                </td>
                <td>{{ $day5['paid'] }}</td>
            </tr>
            <tr>
                <th scope="row">{{ $day6['title'] }}</th>
                <td>{{ $day6['total'] }}</td>
                <td>{{ $day6['delivered'] }}</td>
                <td title="{{ $day6['statistics'] }}">
                    <a href="{{ $day6['not_delivered_link'] }}">{{ $day6['not_delivered'] }}</a>
                </td>
                <td>{{ $day6['paid'] }}</td>
            </tr>
            <tr>
                <th scope="row">{{ $current_month['title'] }}</th>
                <td>{{ $current_month['total'] }}</td>
                <td>{{ $current_month['delivered'] }}</td>
                <td><a href="{{ $current_month['not_delivered_link'] }}">{{ $current_month['not_delivered'] }}</a></td>
                <td>{{ $current_month['paid'] }}</td>
            </tr>
            <tr>
                <th scope="row">{{ $previous_month['title'] }}</th>
                <td>{{ $previous_month['total'] }}</td>
                <td>{{ $previous_month['delivered'] }}</td>
                <td><a href="{{ $previous_month['not_delivered_link'] }}">{{ $previous_month['not_delivered'] }}</a></td>
                <td>{{ $previous_month['paid'] }}</td>
            </tr>
            <tr>
                <th scope="row">{{ $preprevious_month['title'] }}</th>
                <td>{{ $preprevious_month['total'] }}</td>
                <td>{{ $preprevious_month['delivered'] }}</td>
                <td><a href="{{ $preprevious_month['not_delivered_link'] }}">{{ $preprevious_month['not_delivered'] }}</a></td>
                <td>{{ $preprevious_month['paid'] }}</td>
            </tr>
        </tbody>
    </table>
</div>

@include('internal/footer')
