<?php
namespace App\Http\Controllers;
use App\Models\Integration;
use App\Services\{ConnectorRegistry,IntegrationSyncService};
use App\Support\{Access,Audit};
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;
class IntegrationController extends Controller {
 public function index(Request $r){$integrations=Integration::with('unit')->where(fn($q)=>$q->whereNull('organizational_unit_id')->orWhereIn('organizational_unit_id',$r->user()->unitIds()))->orderBy('type')->get();return view('integrations',compact('integrations'));}
 public function show(Integration $integration){if($integration->organizational_unit_id)Access::assertUnit($integration->organizational_unit_id);$logs=$integration->logs()->latest('created_at')->limit(20)->get();$units=\App\Models\OrganizationalUnit::whereIn('id',auth()->user()->unitIds())->orderBy('name')->get();return view('integration-detail',compact('integration','units','logs'));}
 public function update(Request $r,Integration $integration){if($integration->organizational_unit_id)Access::assertUnit($integration->organizational_unit_id);$d=$r->validate([
   'name'=>'required|string|max:140','organizational_unit_id'=>'nullable|integer|exists:organizational_units,id',
   'base_url'=>'nullable|url|max:255','username'=>'nullable|string|max:120','api_token'=>'nullable|string|max:4096','password'=>'nullable|string|max:4096','app_token'=>'nullable|string|max:4096',
   'poll_seconds'=>'required|integer|min:10|max:3600','timeout_seconds'=>'required|integer|min:2|max:30',
   'mode'=>'required|in:demo,integrated','enabled'=>'sometimes|boolean','devices_path'=>'nullable|string|max:240','jobs_path'=>'nullable|string|max:240','ca_bundle'=>'nullable|string|max:240'
  ]);
  if(!empty($d['organizational_unit_id']))Access::assertUnit((int)$d['organizational_unit_id']);
  if($d['mode']==='integrated'){
   if(empty($d['base_url'])||!filter_var($d['base_url'],FILTER_VALIDATE_URL)||parse_url($d['base_url'],PHP_URL_SCHEME)!=='https'||parse_url($d['base_url'],PHP_URL_USER)||parse_url($d['base_url'],PHP_URL_PASS)||parse_url($d['base_url'],PHP_URL_FRAGMENT))return back()->withErrors(['base_url'=>'Use endereço HTTPS válido, sem usuário ou senha na URL.'])->withInput();
  }
  $current=$integration->credentials??[];
  foreach(['api_token','password','app_token'] as $key){if(isset($d[$key])&&$d[$key]!=='')$current[$key]=$d[$key];unset($d[$key]);}
  $options=$integration->options??[];
  foreach(['devices_path','jobs_path','ca_bundle'] as $key){if(array_key_exists($key,$d)){$options[$key]=$d[$key];unset($d[$key]);}}
  if($d['mode']==='integrated'){$d['status']='not_checked';}else{$d['status']='demo';}
  $d['enabled']=($r->boolean('enabled')&&$d['mode']==='integrated');$d['credentials']=$current;$d['options']=$options;
  $integration->update($d);Audit::record('integrations.update',$integration,['mode'=>$d['mode'],'enabled'=>$d['enabled']]);return back()->with('success','Configuração criptografada e salva. Execute Testar conexão antes da sincronização.');
 }
 public function test(Integration $integration,ConnectorRegistry $registry){if($integration->organizational_unit_id)Access::assertUnit($integration->organizational_unit_id);try{$result=$registry->for($integration->type)->test($integration);$integration->update(['status'=>'online','last_attempt_at'=>now(),'last_success_at'=>now(),'last_error'=>null]);$type='success';$message=$result['message']??'Conexão validada.';}catch(Throwable $e){$type='error';$message='Não foi possível validar a API ou as credenciais. Verifique URL, TLS e versão. ('.class_basename($e).')';$integration->update(['status'=>'error','last_attempt_at'=>now(),'last_error'=>$message]);}$integration->logs()->create(['status'=>$type==='success'?'success':'failed','items_count'=>0,'duration_ms'=>0,'message'=>$message,'created_at'=>now()]);Audit::record('integrations.test',$integration,['result'=>$type]);return back()->with($type,$message);}
 public function sync(Integration $integration,IntegrationSyncService $service){if($integration->organizational_unit_id)Access::assertUnit($integration->organizational_unit_id);$result=$service->run($integration);Audit::record('integrations.sync',$integration,['result'=>$result['status']]);return back()->with($result['status']==='failed'?'error':'success',$result['message']);}
}
