<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tango Calendar</title>

    <link rel="stylesheet" href="{{ asset('assets/tango-dark/css/tango-dark.css?v1.0.15') }}">
</head>
<body>
@yield('content')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const body = document.body;

        function closeMobilePanels() {
            body.classList.remove('mobile-menu-open', 'mobile-filters-open');
        }

        document.querySelectorAll('[data-mobile-panel]').forEach((button) => {
            button.addEventListener('click', () => {
                closeMobilePanels();

                if (button.dataset.mobilePanel === 'mobileMenu') {
                    body.classList.add('mobile-menu-open');
                }

                if (button.dataset.mobilePanel === 'mobileFilters') {
                    body.classList.add('mobile-filters-open');
                }
            });
        });

        document.querySelectorAll('[data-mobile-close]').forEach((button) => {
            button.addEventListener('click', closeMobilePanels);
        });
    });
</script>
@stack('scripts')
</body>
</html>
