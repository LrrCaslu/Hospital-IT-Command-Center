<?php
namespace App\Http\Controllers;
use App\Models\{Asset,Ticket,BackupJob,Incident,Alert,OrganizationalUnit,AuditLog,Integration};
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Cache};
class ModuleController extends Controller {
 public function show(string $section,Request $r){
  $defs=[
   'operations'=>['Centro de Operações','activity','incidents'],
   'network'=>['Infraestrutura de Rede','network','assets'],
   'wan'=>['Conectividade e WAN','globe','assets'],
   'wifi'=>['Redes Wi-Fi','wifi','assets'],
   'servers'=>['Servidores e Serviços','server','assets'],
   'tickets'=>['Chamados e SLA','ticket','tickets'],
   'security'=>['Segurança da Informação','shield','alerts'],
   'backups'=>['Backups e Recuperação','database','backups'],
   'reports'=>['Relatórios','chart','incidents'],
   'audit'=>['Histórico e Auditoria','clock','audit'],
   'health'=>['Integridade da Plataforma','activity','health'],
   'notifications'=>['Notificações','bell','notifications']
  ];abort_unless(isset($defs[$section]),404);
  $permission=match($section){'security'=>'security.view','backups'=>'backups.view','tickets'=>'tickets.view','reports'=>'reports.view','audit'=>'audit.view','health'=>'settings.manage','notifications'=>'notifications.view',default=>'assets.view'};
  abort_unless($r->user()->hasPermission($permission),403);
  [$heading,$icon,$kind]=$defs[$section];
  if($section==='notifications')return view('notifications',['items'=>$r->user()->notifications()->latest()->paginate(30)]);
  $rows=[];$columns=[];$summaries=[];$ids=$r->user()->unitIds();$demo=config('noc.mode')==='demo';
  if($kind==='assets'){
   $categories=match($section){'network'=>['router','switch','firewall'],'wan'=>['wan','router'],'wifi'=>['access_point'],'servers'=>['server'],default=>[]};
   $q=Asset::with('unit')->whereIn('organizational_unit_id',$ids)->where('origin','!=',$demo?'integrated':'demo')->whereIn('category',$categories)->orderBy('status')->orderBy('name');
   foreach($q->limit(150)->get() as $a)$rows[]=['Ativo'=>$a->name,'Unidade'=>$a->unit?->name,'Tipo'=>strtoupper($a->category),'Estado'=>($a->origin==='integrated' && (!$a->last_seen_at || $a->last_seen_at->lt(now()->subSeconds(config('noc.stale_after_seconds')))))?'stale':$a->status,'CPU'=>$a->cpu_pct===null?'—':$a->cpu_pct.'%','Última coleta'=>$a->last_seen_at?->format('d/m H:i')??'—'];
  }elseif($kind==='tickets'){
   foreach(Ticket::with('unit')->whereIn('organizational_unit_id',$ids)->where('simulated',$demo)->latest()->limit(150)->get() as $t)$rows[]=['Chamado'=>$t->external_id,'Assunto'=>$t->title,'Unidade'=>$t->unit?->name,'Prioridade'=>$t->priority,'Status'=>$t->status,'SLA'=>$t->sla_due_at?->format('d/m H:i')??'—'];
  }elseif($kind==='backups'){
   foreach(BackupJob::with('unit')->whereIn('organizational_unit_id',$ids)->where('origin','!=',$demo?'integrated':'demo')->latest()->limit(150)->get() as $b)$rows[]=['Job'=>$b->name,'Unidade'=>$b->unit?->name,'Status'=>$b->status,'Última execução'=>$b->last_run_at?->format('d/m H:i')??'—','Restauração testada'=>$b->restoration_tested?'Sim':'Não'];
  }elseif($kind==='alerts'){
   foreach(Alert::with('unit')->whereIn('organizational_unit_id',$ids)->where(fn($q)=>$demo?$q->whereIn('source',['demo','manual']):$q->where('source','!=','demo'))->latest()->limit(150)->get() as $a)$rows[]=['Evento'=>$a->title,'Unidade'=>$a->unit?->name,'Severidade'=>$a->severity,'Status'=>$a->status,'Origem'=>$a->source,'Data'=>$a->opened_at?->format('d/m H:i')??'—'];
  }elseif($kind==='incidents'){
   foreach(Incident::with('unit')->whereIn('organizational_unit_id',$ids)->where(fn($q)=>$demo?$q->whereIn('source',['demo','manual']):$q->where('source','!=','demo'))->latest()->limit(150)->get() as $i)$rows[]=['ID'=>$i->reference,'Incidente'=>$i->title,'Unidade'=>$i->unit?->name,'Severidade'=>$i->severity,'Status'=>$i->status,'Criado'=>$i->created_at?->format('d/m H:i')];
  }elseif($kind==='audit'){
   foreach(AuditLog::with('user')->when(!$r->user()->isSuperAdmin(),fn($q)=>$q->where('user_id',$r->user()->id))->latest('created_at')->limit(150)->get() as $a)$rows[]=['Evento'=>$a->action,'Usuário'=>$a->user?->name??'Sistema','Objeto'=>$a->entity_type??'—','Data'=>$a->created_at?->format('d/m/Y H:i')];
  }elseif($kind==='health'){
   foreach(['Banco de dados'=>'DB','Cache'=>'CACHE','Fila de processamento'=>'QUEUE','Coletor demonstrativo'=>'COLLECTOR'] as $label=>$kind){$state='Verificar';try{$state=match($kind){'DB'=>DB::select('SELECT 1')?'Operacional':'Falha','CACHE'=>Cache::put('noc-health',1,10)?'Operacional':'Falha','QUEUE'=>'Requer worker ativo','COLLECTOR'=>Cache::get('noc-demo-last-tick')?'Atividade recente':'Sem atividade recente'};}catch(\Throwable $e){$state='Indisponível';}$rows[]=['Componente'=>$label,'Situação'=>$state];}
  }elseif($kind==='notifications'){
   foreach($r->user()->notifications()->limit(100)->latest()->get() as $n)$rows[]=['Evento'=>$n->data['title']??'Alerta operacional','Mensagem'=>$n->data['message']??'—','Lida'=>$n->read_at?'Sim':'Não','Data'=>$n->created_at?->format('d/m H:i')];
  }
  $columns=$rows?array_keys($rows[0]):[];
  return view('modules.generic',compact('heading','section','kind','rows','columns','icon'));
 }
 public function csv(Request $r){$v=$r->validate(['from'=>'nullable|date','to'=>'nullable|date|after_or_equal:from']);$q=Incident::with('unit')->whereIn('organizational_unit_id',$r->user()->unitIds())->where(fn($q)=>config('noc.mode')==='demo'?$q->whereIn('source',['demo','manual']):$q->where('source','!=','demo'));if(isset($v['from']))$q->whereDate('created_at','>=',$v['from']);if(isset($v['to']))$q->whereDate('created_at','<=',$v['to']);$rows=$q->orderBy('created_at')->limit(5000)->get();return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['ID','Unidade','Título','Severidade','Status','Abertura','Resolução'],';');foreach($rows as $i)fputcsv($out,[$i->reference,$i->unit?->name,$this->csvSafe($i->title),$i->severity,$i->status,$i->created_at?->format('Y-m-d H:i'),$i->resolved_at?->format('Y-m-d H:i')],';');fclose($out);},'hospital-noc-incidentes.csv',['Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'no-store']);}
 private function csvSafe(string $value):string {return preg_match('/^[\s]*[=+@\-]/',$value)?"'".$value:$value;}
 public function markRead(string $id,Request $r){$n=$r->user()->notifications()->where('id',$id)->firstOrFail();$n->markAsRead();return back()->with('success','Notificação lida.');}
}
