<div class="container">
    <header class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-between py-3 mb-4 border-bottom">
        <a href="/" class="d-flex align-items-center col-md-3 mb-2 mb-md-0 text-dark text-decoration-none">
            <svg class="bi me-2" width="40" height="32" role="img" aria-label="Bootstrap">
                <use xlink:href="#bootstrap" />
            </svg>
        </a>

        <ul class="nav col-12 col-md-auto mb-2 justify-content-center mb-md-0">
            <li><span class="nav-link px-2 link-secondary"> </span></li>
            <li><a href="#" class="nav-link px-2 link-dark"> </a></li>
        </ul>

        <div class="col-md-2 text-end">
        <form method="POST" action="/internal/logout" class="mt-10">
            @csrf
            <button type="submit" class="btn btn-outline-primary me-2">Выйти</button>
        </form>
        </div>
    </header>
</div>
