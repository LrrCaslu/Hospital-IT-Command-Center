<?php
namespace App\Services;
use App\Models\{Alert,Incident,User,OrganizationalUnit};
use App\Notifications\OperationalAlertNotification;
use Illuminate\Support\Facades\{Cache,DB};
class AlertService {
 public function raise(int $unitId,string $key,string $title,string $severity='medium',?int $assetId=null,string $source='monitor'):Alert {
  // O lock evita duas requisições criando o mesmo alerta enquanto a chave única é persistida.
  return Cache::lock('alert:'.sha1($key),15)->block(5,function()use($unitId,$key,$title,$severity,$assetId,$source){
    $wasNew=false;
    $alert=DB::transaction(function()use($unitId,$key,$title,$severity,$assetId,$source,&$wasNew){
      $existing=Alert::where('dedupe_key',$key)->lockForUpdate()->first();
      if($existing&&in_array($existing->status,['active','acknowledged']))return $existing;
      $maintenance=DB::table('maintenance_windows')->where('organizational_unit_id',$unitId)->where('starts_at','<=',now())->where('ends_at','>',now())->max('ends_at');
      $incident=null;
      if(!$maintenance){
       $incident=Incident::create(['reference'=>'INC-'.strtoupper(substr(bin2hex(random_bytes(8)),0,10)),'organizational_unit_id'=>$unitId,'title'=>$title,'severity'=>$severity,'status'=>'new','source'=>$source]);
       DB::table('incident_events')->insert(['incident_id'=>$incident->id,'kind'=>'created','description'=>'Incidente gerado por alerta correlacionado','created_at'=>now()]);
      }
      $data=['organizational_unit_id'=>$unitId,'asset_id'=>$assetId,'incident_id'=>$incident?->id,'title'=>$title,'severity'=>$severity,'status'=>'active','source'=>$source,'opened_at'=>now(),'muted_until'=>$maintenance,'resolved_at'=>null,'acknowledged_at'=>null,'acknowledged_by'=>null];
      if($existing)$existing->update($data);else $existing=Alert::create(['dedupe_key'=>$key]+$data);
      DB::table('alert_events')->insert(['alert_id'=>$existing->id,'kind'=>$maintenance?'maintenance_suppressed':'opened','context'=>json_encode(['source'=>$source]),'created_at'=>now()]);
      $wasNew=!$maintenance;
      return $existing;
    });
    if($wasNew){
      $unit=OrganizationalUnit::find($unitId);
      User::with('role')->where('active',true)->get()->each(function($user)use($alert,$unit,$source){
        if(!$user->hasPermission('notifications.view')||!$user->canAccessUnit($alert->organizational_unit_id))return;
        if($source==='wazuh'&&!$user->hasPermission('security.view'))return;
        $user->notify(new OperationalAlertNotification($alert->id,$alert->title,$alert->severity,$unit?->name??'Unidade'));
      });
    }
    return $alert;
  });
 }
 public function normalize(string $key):void {
  DB::transaction(function()use($key){$a=Alert::where('dedupe_key',$key)->lockForUpdate()->first();if(!$a||$a->status==='resolved')return;
   $a->update(['status'=>'resolved','resolved_at'=>now()]);
   DB::table('alert_events')->insert(['alert_id'=>$a->id,'kind'=>'normalized','context'=>null,'created_at'=>now()]);
   if($a->incident && !$a->incident->alerts()->where('id','!=',$a->id)->whereIn('status',['active','acknowledged'])->exists() && !in_array($a->incident->status,['resolved','closed'])){
     $a->incident->update(['status'=>'resolved','resolved_at'=>now()]);
     DB::table('incident_events')->insert(['incident_id'=>$a->incident_id,'kind'=>'auto_resolved','description'=>'Todos os alertas vinculados normalizaram','created_at'=>now()]);
   }
  });
 }
}
