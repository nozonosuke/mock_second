<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {

        $this->app->bind(
            \Laravel\Fortify\Http\Requests\LoginRequest::class,
            \App\Http\Requests\LoginRequest::class
        );

        //$this->app->bind(
          //  \Laravel\Fortify\Http\Requests\RegisterUserRequest::class,
            //\App\Http\Requests\RegisterUserRequest::class
        //);

        $this->app->singleton(LoginResponseContract::class, function () {
            return new class implements LoginResponseContract {
                public function toResponse($request)
                {
                    $user = auth()->user();

                    if ($user->is_admin) {
                        return redirect()->route('admin.attendance.list');
                    }

                    return redirect()->route('attendance.index');
                }
            };
        });

        $this->app->singleton(\Laravel\Fortify\Contracts\RegisterResponse::class, function () {
            return new class implements \Laravel\Fortify\Contracts\RegisterResponse {
                public function toResponse($request)
                {
                    return redirect()->route('attendance.index');
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::registerView(function () {
         return view('auth.register');
        });

        Fortify::loginView(function () {
            return view('auth.login');
        });

        Fortify::authenticateUsing(function (Request $request) {
                $loginType = $request->input('login_type', 'user');

                if ($loginType === 'admin') {
                    $request->validate(
                        (new \App\Http\Requests\AdminLoginRequest())->rules(),
                        (new \App\Http\Requests\AdminLoginRequest())->messages()
                    );
                } else {
                    $request->validate(
                        (new \App\Http\Requests\LoginRequest())->rules(),
                        (new \App\Http\Requests\LoginRequest())->messages()
                    );
                }

                $user = User::where('email', $request->email)->first();

                if (! $user || ! Hash::check($request->password, $user->password)) {
                    throw ValidationException::withMessages([
                        'email' => ['ログイン情報が登録されていません'],
                    ]);
                }

                if ($loginType === 'admin' && ! $user->is_admin) {
                    throw ValidationException::withMessages([
                        'email' => ['ログイン情報が登録されていません'],
                    ]);
                }

                if ($loginType !== 'admin' && $user->is_admin) {
                    throw ValidationException::withMessages([
                        'email' => ['ログイン情報が登録されていません'],
                    ]);
                }

                return $user;
            });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });
    }
}
