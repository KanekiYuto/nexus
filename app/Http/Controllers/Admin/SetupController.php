<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 系统初始化控制器。
 *
 * 仅在 users 表为空时可用，用于创建第一个管理员账号。
 * 一旦账号已存在，所有访问均重定向至登录页，防止重复初始化。
 */
class SetupController
{
    /**
     * 展示初始化页面。
     *
     * users 表中已有记录时直接跳转登录页，避免覆盖现有账号。
     */
    public function showSetup(): View|RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect('/admin/login');
        }

        return view('admin.setup');
    }

    /**
     * 处理初始化表单提交，创建首个管理员账号。
     *
     * 同样校验 users 表是否为空，防止并发或直接 POST 绕过页面限制。
     * 账号创建成功后跳转登录页。
     */
    public function setup(Request $request): RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect('/admin/login');
        }

        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:100'],
            'email'                 => ['required', 'email', 'max:255'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);

        return redirect('/admin/login')->with('status', '管理员账号已创建，请登录。');
    }
}
