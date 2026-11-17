<?php
namespace App\Http\Controllers;
use App\Models\{User,Role,OrganizationalUnit};
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Artisan,Hash};
class AdminController extends Controller {
 public function users(){return view('admin-users',['users'=>User::with(['role','units'])->orderBy('name')->get(),'roles'=>Role::all(),'units'=>OrganizationalUnit::orderBy('name')->get()]);}
 public function createUser(Request $r){$v=$r->validate(['name'=>'required|string|max:120','email'=>'required|email|max:190|unique:users,email','password'=>'required|string|min:12|max:128','role_id'=>'required|exists:roles,id','units'=>'sometimes|array','units.*'=>'integer|exists:organizational_units,id']);$units=$v['units']??[];unset($v['units']);$user=User::create($v);$user->units()->sync($units);Audit::record('users.create',$user,['role_id'=>$user->role_id]);return back()->with('success','Usuário criado.');}
 public function updateUser(Request $r,User $user){$v=$r->validate(['role_id'=>'required|integer|exists:roles,id','active'=>'required|boolean','units'=>'sometimes|array','units.*'=>'integer|exists:organizational_units,id']);abort_if($user->id===auth()->id() && (!$v['active']||$v['role_id']!=auth()->user()->role_id),422,'Não altere seu próprio perfil ou desative sua conta.');$user->update(['role_id'=>$v['role_id'],'active'=>$v['active']]);$user->units()->sync($v['units']??[]);Audit::record('users.update',$user,['role_id'=>$v['role_id'],'active'=>$v['active']]);return back()->with('success','Acessos atualizados.');}
 public function settings(){return view('settings');}
 public function resetDemo(){abort_unless(config('noc.mode')==='demo',403);$code=Artisan::call('noc:demo-reset',['--force'=>true]);return back()->with($code===0?'success':'error',$code===0?'Base demonstrativa redefinida.':'Redefinição bloqueada; consulte o terminal.');}
}
