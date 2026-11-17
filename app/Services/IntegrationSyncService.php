<?php
namespace App\Services;
use App\Models\{Integration,Asset,AssetIdentifier,MetricSample,Ticket};
use Illuminate\Support\Facades\{Cache,DB};
use Throwable;
class IntegrationSyncService {
 public function run(Integration $i):array {
  if(!$i->enabled||$i->mode!=='integrated')return ['status'=>'skipped','message'=>'Integração desabilitada ou demonstrativa.'];
  $lock=Cache::lock('sync-'.$i->id,60);if(!$lock->get())return ['status'=>'busy','message'=>'Coleta já está em andamento.'];
  $start=microtime(true);$status='success';$count=0;$message='Coleta concluída.';
  try {
   $i->update(['status'=>'syncing','last_attempt_at'=>now(),'last_error'=>null]);
   $result=app(ConnectorRegistry::class)->for($i->type)->collect($i);
   if($i->organizational_unit_id){$this->persist($i,$result);$count=count($result['assets']??[])+count($result['metrics']??[])+count($result['tickets']??[]);}
   else{$message='Conexão válida, mas atribua uma unidade para persistir registros de ativos.';}
   $i->update(['status'=>'online','last_success_at'=>now(),'last_error'=>null]);
  }catch(Throwable $e){$status='failed';$message='Falha na comunicação ou incompatibilidade da API ('.class_basename($e).').';$i->update(['status'=>'error','last_error'=>$message]);}
  finally {
   DB::table('integration_sync_logs')->insert(['integration_id'=>$i->id,'status'=>$status,'items_count'=>$count,'duration_ms'=>(int)((microtime(true)-$start)*1000),'message'=>$message,'created_at'=>now()]);$lock->release();
  }
  return ['status'=>$status,'count'=>$count,'message'=>$message];
 }
 private function persist(Integration $i,array $result):void {
  DB::transaction(function()use($i,$result){$map=[];
   foreach($result['assets']??[] as $raw){$external=(string)($raw['external_id']??'');if($external==='')continue;
    $identity=AssetIdentifier::where('integration_id',$i->id)->where('external_id',$external)->first();
    $data=['name'=>substr((string)($raw['name']??'Ativo'),0,140),'category'=>$raw['category']??'other','status'=>$raw['status']??'unknown','last_seen_at'=>now()];
    if($identity){$asset=$identity->asset;$asset->update($data);}
    else{$asset=Asset::create($data+['organizational_unit_id'=>$i->organizational_unit_id,'origin'=>'integrated']);$asset->identifiers()->create(['integration_id'=>$i->id,'external_id'=>$external]);}
    $map[$external]=$asset;
   }
   foreach($result['metrics']??[] as $m){$a=$map[(string)($m['external_id']??'')]??null;if(!$a||!is_numeric($m['value']??null))continue;MetricSample::create(['asset_id'=>$a->id,'organizational_unit_id'=>$a->organizational_unit_id,'metric_key'=>substr($m['key'],0,32),'value'=>$m['value'],'sampled_at'=>now(),'simulated'=>false]);}
   foreach($result['tickets']??[] as $ticket){$key=(string)($ticket['external_id']??'');if(!$key)continue;Ticket::updateOrCreate(['organizational_unit_id'=>$i->organizational_unit_id,'external_id'=>$i->id.':'.$key],['title'=>substr($ticket['title']??'Chamado',0,255),'status'=>$ticket['status']??'unknown','priority'=>$ticket['priority']??'medium','simulated'=>false]);}
  });
 }
}
