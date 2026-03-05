<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>初始化管理员</title>
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="min-h-screen bg-gray-950 flex items-center justify-center">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-semibold text-white tracking-tight">初始化管理员</h1>
            <p class="mt-1 text-sm text-gray-500">创建首个管理员账号，完成后此页面将关闭</p>
        </div>

        <div class="bg-gray-900 rounded-xl border border-gray-800 p-8 shadow-xl">
            <form method="POST" action="/admin/setup" class="space-y-5">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-300 mb-1.5">姓名</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="管理员"
                        class="w-full px-3.5 py-2.5 bg-gray-800 border rounded-lg text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition {{ $errors->has('name') ? 'border-red-500' : 'border-gray-700' }}"
                    >
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">邮箱</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        placeholder="admin@example.com"
                        class="w-full px-3.5 py-2.5 bg-gray-800 border rounded-lg text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition {{ $errors->has('email') ? 'border-red-500' : 'border-gray-700' }}"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">密码</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="至少 8 位"
                        class="w-full px-3.5 py-2.5 bg-gray-800 border rounded-lg text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition {{ $errors->has('password') ? 'border-red-500' : 'border-gray-700' }}"
                    >
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-1.5">确认密码</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="再次输入密码"
                        class="w-full px-3.5 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-900"
                >
                    创建账号
                </button>
            </form>
        </div>
    </div>
</body>
</html>
