@extends('layouts.calendar')

@section('content')
    <div class="min-h-screen bg-[#f8f2ec] text-[#241814]">

        {{-- Header --}}
        <header class="bg-[#120f0f] text-white px-8 py-5">
            <div class="flex items-center gap-8">
                <div class="text-3xl font-serif tracking-wide">
                    TANGO
                    <div class="text-xs text-red-400 tracking-[0.25em]">CALENDAR</div>
                </div>

                <div class="flex-1 max-w-xl">
                    <input
                        type="text"
                        placeholder="Пошук подій, міст, викладачів..."
                        class="w-full rounded-lg bg-white/10 px-5 py-3 text-sm outline-none placeholder:text-white/50"
                    >
                </div>

                <nav class="ml-auto flex items-center gap-6 text-sm">
                    <span class="border-b-2 border-red-500 pb-1">UA</span>
                    <span class="text-white/50">EN</span>
                    <button>♡ Обране</button>
                    <button class="rounded-lg border border-white/40 px-5 py-3">
                        Додати подію +
                    </button>
                </nav>
            </div>
        </header>

        {{-- Top filters --}}
        <section class="border-b border-[#e4d8cf] bg-[#fbf7f3] px-8 py-4">
            <div class="flex flex-wrap items-center gap-4">
                <button class="rounded-lg bg-[#9d2f2f] px-5 py-3 text-white">⚙ Усі події</button>
                <button class="rounded-lg border px-5 py-3 bg-white">🎟 Фестивалі</button>
                <button class="rounded-lg border px-5 py-3 bg-white">♫ Мілонги</button>
                <button class="rounded-lg border px-5 py-3 bg-white">▱ Заняття</button>

                <select class="ml-8 rounded-lg border bg-white px-5 py-3 min-w-56">
                    <option>🌐 Усі країни</option>
                </select>

                <select class="rounded-lg border bg-white px-5 py-3 min-w-56">
                    <option>📍 Усі міста</option>
                </select>

                <button class="ml-auto rounded-lg border border-[#9d2f2f] px-5 py-3">
                    ☷ Більше фільтрів
                </button>
                <button class="rounded-lg border px-5 py-3 bg-white">↻ Скинути</button>
            </div>
        </section>

        <main class="grid grid-cols-[300px_1fr_340px] gap-6 px-8 py-6">

            {{-- Left sidebar --}}
            <aside class="space-y-5">
                <div class="rounded-xl bg-white/60 p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-serif text-xl">Травень 2024</h3>
                        <div class="flex gap-3 text-gray-400">
                            <button>‹</button>
                            <button>›</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-7 gap-3 text-center text-sm">
                        @foreach(['Пн','Вт','Ср','Чт','Пт','Сб','Нд'] as $day)
                            <div class="text-gray-500">{{ $day }}</div>
                        @endforeach

                        @foreach(range(1, 35) as $i)
                            <div class="{{ $i === 16 ? 'bg-[#9d2f2f] text-white rounded-full' : '' }} py-1">
                                {{ $i <= 2 ? 28 + $i : $i - 2 }}
                            </div>
                        @endforeach
                    </div>

                    <button class="mt-5 w-full rounded-lg border py-3 bg-white">Сьогодні</button>
                </div>

                <div class="rounded-xl border bg-white/60 p-5">
                    <div class="mb-4 flex justify-between">
                        <h3 class="font-serif text-xl">Фільтри</h3>
                        <button class="text-sm text-gray-500">Скинути все</button>
                    </div>

                    <div class="space-y-5 text-sm">
                        <div>
                            <div class="mb-2 font-medium">Тип події</div>
                            @foreach([
                                ['Фестивалі', 128],
                                ['Мілонги', 342],
                                ['Заняття', 512],
                            ] as [$name, $count])
                                <label class="mb-2 flex items-center justify-between">
                                    <span><input type="checkbox" class="mr-2"> {{ $name }}</span>
                                    <span class="rounded bg-gray-100 px-2 text-xs">{{ $count }}</span>
                                </label>
                            @endforeach
                        </div>

                        @foreach(['Країна', 'Місто', 'Дати', 'Доступність квитків'] as $label)
                            <div>
                                <label class="mb-2 block font-medium">{{ $label }}</label>
                                <select class="w-full rounded-lg border bg-white px-3 py-3">
                                    <option>Будь-яка</option>
                                </select>
                            </div>
                        @endforeach

                        <button class="text-[#9d2f2f]">Більше фільтрів⌄</button>
                    </div>
                </div>
            </aside>

            {{-- Center --}}
            <section>
                <div class="mb-5 flex items-center justify-between">
                    <h1 class="font-serif text-2xl">Травень 2024</h1>

                    <div class="flex rounded-lg border bg-white p-1">
                        <button class="rounded-md bg-[#9d2f2f] px-5 py-2 text-white">Місяць</button>
                        <button class="px-5 py-2">Тиждень</button>
                        <button class="px-5 py-2">Список</button>
                    </div>
                </div>

                {{-- Calendar timeline --}}
                <div class="rounded-xl border bg-white/50 p-5">
                    <div class="grid grid-cols-[120px_1fr] gap-4">
                        <div></div>
                        <div class="grid grid-cols-20 text-center text-xs text-gray-500">
                            @foreach(range(6, 25) as $day)
                                <div>{{ $day }}</div>
                            @endforeach
                        </div>

                        @foreach(['🌐 Весь світ', '☷ Європа', '🌎 Південна Америка', '🌎 Північна Америка', '🌐 Азія'] as $row)
                            <div class="py-7 text-sm">{{ $row }}</div>
                            <div class="relative border-t">
                                @foreach(range(1, 20) as $line)
                                    <div class="absolute top-0 bottom-0 border-l border-gray-100"
                                         style="left: {{ $line * 5 }}%"></div>
                                @endforeach

                                @if($loop->index === 0)
                                    <div class="absolute left-[10%] top-5 w-[35%] rounded-md bg-[#9d2f2f] px-4 py-2 text-xs text-white">
                                        Istanbul Tango Festival<br>
                                        <span class="opacity-70">10 — 15 травня</span>
                                    </div>
                                @endif

                                @if($loop->index === 1)
                                    <div class="absolute left-[3%] top-5 w-[18%] rounded-md bg-orange-700 px-4 py-2 text-xs text-white">
                                        Barcelona Tango Meeting
                                    </div>
                                    <div class="absolute left-[38%] top-5 w-[18%] rounded-md bg-slate-500 px-4 py-2 text-xs text-white">
                                        Kyiv Tango Marathon
                                    </div>
                                @endif

                                @if($loop->index === 2)
                                    <div class="absolute left-[0%] top-5 w-[34%] rounded-md bg-purple-700 px-4 py-2 text-xs text-white">
                                        Buenos Aires Tango Festival
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Cards --}}
                <div class="mt-8">
                    <div class="mb-4 flex justify-between">
                        <h2 class="font-serif text-2xl">Вибрані події цього місяця</h2>
                        <a href="#" class="text-[#9d2f2f]">Переглянути всі →</a>
                    </div>

                    <div class="grid grid-cols-4 gap-4">
                        @foreach([
                            ['Istanbul Tango Festival', 'Стамбул, Туреччина', '10 — 15 травня 2024'],
                            ['Kyiv Tango Marathon', 'Київ, Україна', '16 — 19 травня 2024'],
                            ['Prague Tango Marathon', 'Прага, Чехія', '22 — 26 травня 2024'],
                            ['Barcelona Tango Meeting', 'Барселона, Іспанія', '8 — 12 травня 2024'],
                        ] as $event)
                            <article class="relative overflow-hidden rounded-xl bg-black text-white shadow">
                                <div class="h-64 bg-gradient-to-b from-gray-500 to-black"></div>

                                <div class="absolute inset-x-0 bottom-0 p-4">
                                    <span class="rounded bg-[#9d2f2f] px-2 py-1 text-xs">ФЕСТИВАЛЬ</span>
                                    <h3 class="mt-3 font-serif text-xl leading-tight">{{ $event[0] }}</h3>
                                    <p class="mt-2 text-sm">📍 {{ $event[1] }}</p>
                                    <p class="text-sm">▣ {{ $event[2] }}</p>
                                </div>

                                <button class="absolute right-4 bottom-4 text-2xl">♡</button>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Right sidebar --}}
            <aside>
                <h2 class="mb-6 font-serif text-2xl">Майбутні події</h2>

                <div class="space-y-5">
                    @foreach([
                        ['Istanbul Tango Festival', '10 – 15 травня', 'Стамбул, Туреччина', 'Фестиваль'],
                        ['Kyiv Tango Marathon', '16 – 19 травня', 'Київ, Україна', 'Фестиваль'],
                        ['Barcelona Tango Meeting', '8 – 12 травня', 'Барселона, Іспанія', 'Фестиваль'],
                        ['Montreal Tango Weekend', '17 – 19 травня', 'Монреаль, Канада', 'Мілонга'],
                        ['Classes with Pablo & Ludmila', '18 – 20 травня', 'Берлін, Німеччина', 'Заняття'],
                    ] as $event)
                        <article class="flex gap-4">
                            <div class="h-24 w-24 shrink-0 rounded-lg bg-gradient-to-br from-gray-400 to-gray-700"></div>
                            <div class="flex-1">
                                <h3 class="font-serif text-lg leading-tight">{{ $event[0] }}</h3>
                                <p class="text-sm">{{ $event[1] }}</p>
                                <p class="text-sm text-gray-600">{{ $event[2] }}</p>
                                <p class="mt-1 text-sm text-[#9d2f2f]">{{ $event[3] }}</p>
                            </div>
                            <button class="text-xl">♡</button>
                        </article>
                    @endforeach
                </div>

                <button class="mt-8 w-full rounded-lg border bg-white py-4 text-[#9d2f2f]">
                    Показати всі події (18) →
                </button>
            </aside>

        </main>

        {{-- Footer subscribe --}}
        <footer class="bg-[#1b1110] px-8 py-8 text-white">
            <div class="grid grid-cols-[1fr_1fr_1fr] items-center gap-8">
                <div>
                    <h3 class="font-serif text-2xl">Будьте в курсі подій світу танго</h3>
                    <p class="mt-2 text-white/70">
                        Підпишіться на нашу розсилку і отримуйте найсвіжіші новини та анонси
                    </p>
                </div>

                <form class="flex rounded-lg bg-white/10 p-1">
                    <input class="flex-1 bg-transparent px-4 outline-none" placeholder="Ваш e-mail">
                    <button class="rounded-md bg-[#b83a3a] px-8 py-3">Підписатися</button>
                </form>

                <div>
                    <h3 class="font-serif text-xl">Мобільний додаток</h3>
                    <p class="text-white/70">Усі події у твоїй кишені</p>
                </div>
            </div>
        </footer>
    </div>
@endsection
