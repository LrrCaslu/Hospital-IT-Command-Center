<?php
namespace App\Services;

use App\Models\{OrganizationalUnit,Asset,Alert,Incident,Ticket,BackupJob,MetricSample,Integration};
use App\Support\Access;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function summary(?int $unitId=null,int $days=7):array
    {
        $user=auth()->user();
        $ids=$unitId ? [$unitId] : $user->unitIds();
        if($unitId) Access::assertUnit($unitId);
        $demo=config('noc.mode')==='demo';
        // O modo integrado jamais usa equipamentos, chamados ou incidentes fictícios como indicadores reais.
        $unitsQuery=OrganizationalUnit::whereIn('id',$ids);
        if(!$demo) $unitsQuery->where(fn($q)=>$q->where('origin','!=','demo')->orWhereHas('assets',fn($a)=>$a->where('origin','!=','demo')));
        $units=$unitsQuery->withCount([
            'assets as asset_count'=>fn($q)=>$q->where('origin','!=',$demo?'integrated':'demo'),
            'assets as offline_count'=>fn($q)=>$q->where('origin','!=',$demo?'integrated':'demo')->where('status','offline'),
            'incidents as active_incidents_count'=>fn($q)=>$q->where(fn($q)=>$demo?$q->whereIn('source',['demo','manual']):$q->where('source','!=','demo'))->whereNotIn('status',['resolved','closed']),
            'alerts as critical_alerts_count'=>fn($q)=>$q->where(fn($q)=>$demo?$q->whereIn('source',['demo','manual']):$q->where('source','!=','demo'))->where('status','active')->whereIn('severity',['critical','high'])
        ])->orderBy('name')->get();
        if(!$demo) {
            $units->each(function($u){
                if($u->origin==='demo'){$u->status='unknown';$u->availability_pct=0;$u->latency_ms=null;}
            });
        }
        $ids=$units->pluck('id')->all();
        $assets=Asset::whereIn('organizational_unit_id',$ids)->where('origin','!=',$demo?'integrated':'demo');
        $activeAlerts=Alert::whereIn('organizational_unit_id',$ids)->where(fn($q)=>$demo?$q->whereIn('source',['demo','manual']):$q->where('source','!=','demo'))->whereIn('status',['active','acknowledged'])->where(fn($q)=>$q->whereNull('muted_until')->orWhere('muted_until','<',now()));
        $activeIncidents=Incident::whereIn('organizational_unit_id',$ids)->where(fn($q)=>$demo?$q->whereIn('source',['demo','manual']):$q->where('source','!=','demo'))->whereNotIn('status',['resolved','closed']);
        $tickets=Ticket::whereIn('organizational_unit_id',$ids)->where('simulated',$demo)->whereNotIn('status',['resolved','closed']);
        $fresh=now()->subSeconds(config('noc.stale_after_seconds'));
        $cards=[
            ['label'=>'Unidades monitoradas','value'=>$units->count(),'icon'=>'building','tone'=>'blue','href'=>route('units.index')],
            ['label'=>'Operacionais','value'=>$units->where('status','operational')->count(),'icon'=>'check','tone'=>'green','href'=>route('units.index')],
            ['label'=>'Com atenção','value'=>$units->where('status','warning')->count()+$units->where('status','critical')->count(),'icon'=>'alert','tone'=>'yellow','href'=>route('units.index')],
            ['label'=>'Sem comunicação','value'=>$units->where('status','offline')->count(),'icon'=>'wifi','tone'=>'red','href'=>route('units.index')],
            ['label'=>'Ativos monitorados','value'=>(clone $assets)->count(),'icon'=>'server','tone'=>'blue','href'=>route('assets.index')],
            ['label'=>'Equipamentos offline','value'=>(clone $assets)->where('status','offline')->count(),'icon'=>'power','tone'=>'red','href'=>route('assets.index',['status'=>'offline'])],
            ['label'=>'Servidores online','value'=>(clone $assets)->where('category','server')->where('status','online')->where('last_seen_at','>=',$fresh)->count(),'icon'=>'server','tone'=>'green','href'=>route('modules.servers')],
            ['label'=>'Access Points online','value'=>(clone $assets)->where('category','access_point')->where('status','online')->where('last_seen_at','>=',$fresh)->count(),'icon'=>'wifi','tone'=>'green','href'=>route('modules.wifi')],
            ['label'=>'Chamados abertos','value'=>(clone $tickets)->count(),'icon'=>'ticket','tone'=>'blue','href'=>route('modules.tickets')],
            ['label'=>'Chamados críticos','value'=>(clone $tickets)->where('priority','critical')->count(),'icon'=>'ticket','tone'=>'red','href'=>route('modules.tickets')],
            ['label'=>'Incidentes ativos','value'=>(clone $activeIncidents)->count(),'icon'=>'activity','tone'=>'yellow','href'=>route('incidents.index')],
            ['label'=>'Alertas críticos','value'=>(clone $activeAlerts)->whereIn('severity',['high','critical'])->count(),'icon'=>'shield','tone'=>'red','href'=>route('alerts.index')],
            ['label'=>'Backups com falha','value'=>BackupJob::whereIn('organizational_unit_id',$ids)->where('origin','!=',$demo?'integrated':'demo')->where('status','failed')->count(),'icon'=>'database','tone'=>'red','href'=>route('modules.backups')],
            ['label'=>'Pontos de atenção','value'=>$this->attentionCount($ids,$demo),'icon'=>'alert','tone'=>'yellow','href'=>route('alerts.index')],
        ];
        $recent=Alert::with(['unit','incident'])->whereIn('organizational_unit_id',$ids)->where(fn($q)=>$demo?$q->whereIn('source',['demo','manual']):$q->where('source','!=','demo'))->whereIn('status',['active','acknowledged'])->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")->latest('opened_at')->limit(7)->get();
        $cutoff=now()->subDays($days);
        $metric=MetricSample::whereIn('organizational_unit_id',$ids)->where('simulated',$demo)->where('metric_key','cpu')->where('sampled_at','>=',$cutoff)->orderBy('sampled_at')->get(['sampled_at','value']);
        $series=$metric->groupBy(fn($m)=>$m->sampled_at->format('d/m'))->map(fn($group)=>round($group->avg('value'),1));
        $availability=$units->count()?round($units->avg('availability_pct'),1):0;
        return compact('units','cards','recent','series','availability')+[
            'integrationCount'=>Integration::where('mode','integrated')->where('enabled',true)->count(),
            'unitFilter'=>$unitId,'days'=>$days,'generatedAt'=>now()->format('d/m/Y H:i:s'),
            'mode'=>config('noc.mode'),
        ];
    }
    public function attentionCount(array $ids,bool $demo):int
    {
        $open=Incident::whereIn('organizational_unit_id',$ids)->where(fn($q)=>$demo?$q->whereIn('source',['demo','manual']):$q->where('source','!=','demo'))->whereNotIn('status',['resolved','closed'])->count();
        $unlinked=Alert::whereIn('organizational_unit_id',$ids)->where(fn($q)=>$demo?$q->whereIn('source',['demo','manual']):$q->where('source','!=','demo'))->whereNull('incident_id')->whereIn('status',['active','acknowledged'])->where(fn($q)=>$q->whereNull('muted_until')->orWhere('muted_until','<',now()))->count();
        return $open+$unlinked;
    }
}
