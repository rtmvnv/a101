<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Latest compiled and minified CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Latest compiled JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <title>dev</title>
</head>

<body>


    <form method="GET" action="/internal/failed-accruals" class="row">
        <div class="col-auto">
            <label for="start" class="col-1 col-form-label">От</label>
        </div>
        <div class="col-auto">
            <input type="start" class="form-control" id="start">
        </div>
        <div class="col-auto">
            <label for="finish" class="col-1 col-form-label">До</label>
        </div>
        <div class="col-auto">
            <input type="finish" class="form-control" id="finish">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Показать</button>
        </div>
    </form>

</body>

</html>
