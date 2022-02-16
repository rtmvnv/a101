<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LoginController extends Controller
{
    /**
     * Отобразить форму ввода пароля
     */
    public function create()
    {
        return view('internal.login');
    }

    /**
     * Обработать запрос с формы ввода пароля
     */
    public function store()
    {
        $attributes = request()->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        if (auth()->attempt($attributes, true)) {
            return redirect('/internal/dashboard');
        }

        return back()
            ->withInput()
            ->withErrors(['password' => 'Имя пользователя или пароль неверный.']);
    }

    /**
     * Обработать запрос с формы выхода из аккаунта
     */
    public function destroy()
    {
        auth()->logout();

        return redirect('/internal/login');
    }
}
