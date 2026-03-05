@extends('admin.layout')

@section('title', '应用管理')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-lg font-semibold text-white">应用列表</h1>
        <a href="/admin/apps/create"
           class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg transition">
            + 新建应用
        </a>
    </div>

    @if ($apps->isEmpty())
        <div class="text-center py-20 text-gray-600 text-sm">暂无应用，点击右上角新建。</div>
    @else
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-800 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-5 py-3 text-left font-medium">应用 ID</th>
                        <th class="px-5 py-3 text-left font-medium">名称</th>
                        <th class="px-5 py-3 text-left font-medium">状态</th>
                        <th class="px-5 py-3 text-left font-medium">Token 数</th>
                        <th class="px-5 py-3 text-left font-medium">创建时间</th>
                        <th class="px-5 py-3 text-right font-medium">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @foreach ($apps as $app)
                        <tr class="hover:bg-gray-800/50 transition">
                            <td class="px-5 py-3.5 font-mono text-xs text-gray-400">{{ $app->id }}</td>
                            <td class="px-5 py-3.5 text-white font-medium">{{ $app->name }}</td>
                            <td class="px-5 py-3.5">
                                @if ($app->isEnabled())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-900/50 text-green-400 border border-green-800">
                                        启用
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-900/50 text-red-400 border border-red-800">
                                        禁用
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-gray-400">{{ $app->tokens_count }}</td>
                            <td class="px-5 py-3.5 text-gray-400">{{ date('Y-m-d H:i', $app->created_at) }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/admin/apps/{{ $app->id }}/tokens"
                                       class="px-2.5 py-1 rounded-md text-xs text-indigo-400 hover:text-indigo-300 hover:bg-gray-700 transition">
                                        Token
                                    </a>
                                    <a href="/admin/apps/{{ $app->id }}/edit"
                                       class="px-2.5 py-1 rounded-md text-xs text-gray-400 hover:text-white hover:bg-gray-700 transition">
                                        编辑
                                    </a>
                                    <form method="POST" action="/admin/apps/{{ $app->id }}/toggle-status">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="px-2.5 py-1 rounded-md text-xs transition
                                                       {{ $app->isEnabled() ? 'text-yellow-400 hover:text-yellow-300 hover:bg-gray-700' : 'text-green-400 hover:text-green-300 hover:bg-gray-700' }}">
                                            {{ $app->isEnabled() ? '禁用' : '启用' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
