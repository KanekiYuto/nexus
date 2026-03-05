<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * 管理员登录控制器。
 *
 * 仅用于为管理面板提供身份认证入口，不涉及业务逻辑。
 * 登录成功后跳转至 /admin，登出后返回登录页。
 */
class LoginController
{
    /**
     * 展示登录页。
     *
     * 若用户已登录则直接跳转管理面板，避免重复认证。
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/admin');
        }

        return view('admin.login');
    }

    /**
     * 处理登录表单提交。
     *
     * 校验邮箱格式与密码非空后，通过 web guard 尝试认证。
     * 认证成功时重新生成 Session ID 防止会话固定攻击，然后跳转管理面板。
     * 认证失败时回显邮箱并附带错误提示。
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect('/admin');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => '邮箱或密码错误。']);
    }

    /**
     * 处理登出请求。
     *
     * 清除认证状态后销毁当前 Session 并重新生成 CSRF Token，
     * 防止登出后 Token 被复用，最终跳转回登录页。
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
