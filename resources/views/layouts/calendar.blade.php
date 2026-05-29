<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tango Calendar</title>

    {{-- Быстрое подключение Tailwind без сборки --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Шрифт, похожий на макет --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    },
                    gridTemplateColumns: {
                        '20': 'repeat(20, minmax(0, 1fr))',
                    }
                }
            }
        }
    </script>

    <style>
        body {
            margin: 0;
            font-family: Inter, sans-serif;
            background: #f8f2ec;
        }

        * {
            box-sizing: border-box;
        }
    </style>
</head>
<body>
@yield('content')
</body>
</html>
