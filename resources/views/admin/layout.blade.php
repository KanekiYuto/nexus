<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '管理面板')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-950 text-gray-100">

    {{-- 顶部导航 --}}
    <header class="h-14 bg-gray-900 border-b border-gray-800 flex items-center px-6 gap-6 sticky top-0 z-10">
        <span class="text-sm font-semibold text-white tracking-wide">管理面板</span>

        <nav class="flex items-center gap-1 ml-2">
            <a href="/admin/apps"
               class="px-3 py-1.5 rounded-md text-sm transition
                      {{ request()->is('admin/apps*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                应用管理
            </a>
            <a href="/telescope"
               target="_blank"
               class="px-3 py-1.5 rounded-md text-sm text-gray-400 hover:text-white hover:bg-gray-800 transition flex items-center gap-1">
                Telescope
                <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </nav>

        <div class="ml-auto flex items-center gap-3">
            <span class="text-xs text-gray-500">{{ auth()->user()->name }}</span>
            <form method="POST" action="/admin/logout">
                @csrf
                <button type="submit"
                        class="px-3 py-1.5 rounded-md text-sm text-gray-400 hover:text-white hover:bg-gray-800 transition">
                    登出
                </button>
            </form>
        </div>
    </header>

    {{-- 主内容 --}}
    <main class="max-w-5xl mx-auto px-6 py-8">

        {{-- Flash 消息 --}}
        @if (session('success'))
            <div class="mb-6 px-4 py-3 bg-green-900/50 border border-green-700 rounded-lg text-sm text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 px-4 py-3 bg-red-900/50 border border-red-700 rounded-lg text-sm text-red-300">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

</body>
</html>
