@extends('admin.layout')

@section('title', 'Token 管理')

@section('content')
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin/apps" class="text-gray-500 hover:text-gray-300 transition text-sm">← 返回列表</a>
        <span class="text-gray-700">/</span>
        <h1 class="text-lg font-semibold text-white">
            Token 管理
            <span class="text-gray-500 font-normal text-base">— {{ $app->name }}</span>
        </h1>
    </div>

    {{-- 生成新 Token 表单 --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 mb-6 max-w-lg">
        <h2 class="text-sm font-medium text-gray-300 mb-4">生成新 Token</h2>
        <form method="POST" action="/admin/apps/{{ $app->id }}/tokens" class="flex items-end gap-3">
            @csrf
            <div class="flex-1">
                <label for="expired_at" class="block text-xs text-gray-500 mb-1.5">过期时间（留空表示永不过期）</label>
                <input
                    id="expired_at"
                    type="datetime-local"
                    name="expired_at"
                    class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition
                           [color-scheme:dark]"
                >
                @error('expired_at')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg transition whitespace-nowrap">
                生成
            </button>
        </form>
    </div>

    {{-- Token 列表 --}}
    @if ($tokens->isEmpty())
        <div class="text-center py-16 text-gray-600 text-sm">暂无 Token，点击上方生成。</div>
    @else
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-800 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-5 py-3 text-left font-medium">Token 值</th>
                        <th class="px-5 py-3 text-left font-medium">状态</th>
                        <th class="px-5 py-3 text-left font-medium">过期时间</th>
                        <th class="px-5 py-3 text-left font-medium">创建时间</th>
                        <th class="px-5 py-3 text-right font-medium">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @foreach ($tokens as $token)
                        <tr class="hover:bg-gray-800/50 transition">
                            <td class="px-5 py-3.5 font-mono text-xs text-gray-300 max-w-xs">
                                <span class="break-all">{{ $token->value }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                @if ($token->isExpired())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-900/50 text-red-400 border border-red-800">
                                        已过期
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-900/50 text-green-400 border border-green-800">
                                        有效
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-gray-400 text-xs">
                                {{ $token->expired_at ? date('Y-m-d H:i', $token->expired_at) : '永不过期' }}
                            </td>
                            <td class="px-5 py-3.5 text-gray-400 text-xs">
                                {{ date('Y-m-d H:i', $token->created_at) }}
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <form method="POST"
                                      action="/admin/apps/{{ $app->id }}/tokens/{{ $token->id }}"
                                      onsubmit="return confirm('确认撤销此 Token？撤销后立即失效。')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-2.5 py-1 rounded-md text-xs text-red-400 hover:text-red-300 hover:bg-gray-700 transition">
                                        撤销
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
