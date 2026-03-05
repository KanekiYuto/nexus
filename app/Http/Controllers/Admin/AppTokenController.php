<?php

namespace App\Http\Controllers\Admin;

use App\Models\App;
use App\Models\AppToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 应用 Token 管理控制器。
 *
 * 提供 Token 的查看、生成、撤销功能，所有路由均受 auth 中间件保护。
 * Token 以明文形式存储于 app_token 表，撤销即物理删除记录。
 */
class AppTokenController
{
    /**
     * 展示指定应用的 Token 列表。
     *
     * 按创建时间倒序排列，包含每条 Token 的有效/过期状态判断。
     */
    public function index(App $app): View
    {
        $tokens = $app->tokens()->orderByDesc('created_at')->get();

        return view('admin.apps.tokens', compact('app', 'tokens'));
    }

    /**
     * 为指定应用生成一条新 Token 并持久化。
     *
     * Token 值使用 bin2hex(random_bytes(32)) 生成 64 字符随机十六进制字符串。
     * expired_at 为可选项：传入时间字符串则转换为 Unix 时间戳存储，留空则永不过期。
     */
    public function store(Request $request, App $app): RedirectResponse
    {
        $request->validate([
            'expired_at' => ['nullable', 'date', 'after:now'],
        ]);

        $expiredAt = $request->filled('expired_at')
            ? strtotime($request->input('expired_at'))
            : null;

        AppToken::create([
            'app_id'     => $app->id,
            'value'      => bin2hex(random_bytes(32)),
            'expired_at' => $expiredAt,
        ]);

        return redirect("/admin/apps/{$app->id}/tokens")->with('success', 'Token 已生成。');
    }

    /**
     * 撤销指定 Token（物理删除）。
     *
     * 删除后立即生效，持有该 Token 的客户端将无法通过验证。
     * 路由模型绑定同时注入 App 和 AppToken，确保 Token 归属校验正确。
     */
    public function destroy(App $app, AppToken $token): RedirectResponse
    {
        $token->delete();

        return redirect("/admin/apps/{$app->id}/tokens")->with('success', 'Token 已撤销。');
    }
}
