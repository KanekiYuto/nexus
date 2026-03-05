@extends('admin.layout')

@section('title', '编辑应用')

@section('content')
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin/apps" class="text-gray-500 hover:text-gray-300 transition text-sm">← 返回列表</a>
        <span class="text-gray-700">/</span>
        <h1 class="text-lg font-semibold text-white">编辑应用</h1>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 max-w-lg">
        <p class="text-xs text-gray-600 font-mono mb-5">ID: {{ $app->id }}</p>

        <form method="POST" action="/admin/apps/{{ $app->id }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-300 mb-1.5">应用名称</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $app->name) }}"
                    required
                    autofocus
                    class="w-full px-3.5 py-2.5 bg-gray-800 border rounded-lg text-sm text-white placeholder-gray-600
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition
                           {{ $errors->has('name') ? 'border-red-500' : 'border-gray-700' }}"
                >
                @error('name')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 pt-1">
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg transition">
                    保存
                </button>
                <a href="/admin/apps" class="px-5 py-2.5 text-sm text-gray-400 hover:text-white transition">
                    取消
                </a>
            </div>
        </form>
    </div>
@endsection
