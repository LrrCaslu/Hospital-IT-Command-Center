<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
class AuthController extends Controller {
 public function login(){return view('auth.login');}
 public function authenticate(Request $r){
  $credentials=$r->validate(['email'=>['required','email'],'password'=>['required','string']]);
  $key=strtolower($credentials['email']).'|'.$r->ip();
  if(RateLimiter::tooManyAttempts($key,5))return back()->withErrors(['email'=>'Muitas tentativas. Tente novamente em '.RateLimiter::availableIn($key).' segundos.'])->onlyInput('email');
  if(Auth::attempt($credentials+['active'=>true])){RateLimiter::clear($key);$r->session()->regenerate();\App\Support\Audit::record('auth.login');return redirect()->intended(route('dashboard'));}
  RateLimiter::hit($key,60);return back()->withErrors(['email'=>'Credenciais inválidas ou conta desativada.'])->onlyInput('email');
 }
 public function logout(Request $r){\App\Support\Audit::record('auth.logout');Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('login');}
}
