<?php

namespace App\Http\Controllers\Admin;

use App\Models\App;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 应用管理控制器。
 *
 * 提供应用的增改、禁用/启用操作，所有路由均受 auth 中间件保护。
 * 应用不支持删除，只能通过禁用使其失效，以保留历史记录完整性。
 */
class AppController
{
    /**
     * 应用列表页。
     *
     * 同时附带每个应用的 Token 数量（withCount），按创建时间倒序排列。
     */
    public function index(): View
    {
        $apps = App::query()
            ->withCount('tokens')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.apps.index', compact('apps'));
    }

    /**
     * 新建应用表单页。
     */
    public function create(): View
    {
        return view('admin.apps.create');
    }

    /**
     * 保存新应用。
     *
     * 校验名称后创建应用，初始状态为启用（status=1）。
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        App::create([
            'name'   => $request->input('name'),
            'status' => 1,
        ]);

        return redirect('/admin/apps')->with('success', '应用已创建。');
    }

    /**
     * 编辑应用表单页。
     *
     * 将当前应用数据回填到表单，供修改名称使用。
     */
    public function edit(App $app): View
    {
        return view('admin.apps.edit', compact('app'));
    }

    /**
     * 更新应用名称。
     *
     * 仅允许修改名称，状态变更通过 toggleStatus 单独处理。
     */
    public function update(Request $request, App $app): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $app->update(['name' => $request->input('name')]);

        return redirect('/admin/apps')->with('success', '应用已更新。');
    }

    /**
     * 切换应用启用/禁用状态。
     *
     * 启用 → 禁用，或禁用 → 启用。状态变更后关联 Token 不受影响，
     * 但业务层在验证 app_id 时应自行检查应用状态。
     */
    public function toggleStatus(App $app): RedirectResponse
    {
        $newStatus = $app->isEnabled() ? 0 : 1;
        $app->update(['status' => $newStatus]);

        $msg = $newStatus === 1 ? '应用已启用。' : '应用已禁用。';

        return redirect('/admin/apps')->with('success', $msg);
    }
}
