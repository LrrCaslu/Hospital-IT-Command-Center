<?php
namespace Database\Seeders;

use App\Models\{Role,Permission,User,OrganizationalUnit,Asset,Integration,Incident,Alert,Ticket,BackupJob,MetricSample};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run():void
    {
        mt_srand(20261116);
        $roles=['superadmin'=>'Superadministrador','ti'=>'Administrador de TI','noc'=>'Analista NOC','soc'=>'Analista SOC','gestor'=>'Gestor','viewer'=>'Visualizador'];
        foreach($roles as $slug=>$name){$role=Role::firstOrCreate(['slug'=>$slug],['name'=>$name]);foreach(config('noc.roles.'.$slug,[]) as $key){$permission=Permission::firstOrCreate(['slug'=>$key],['name'=>$key]);$role->permissions()->syncWithoutDetaching($permission->id);}}
        $people=[['Administrador','admin@hospital.local','Admin@123456','superadmin'],['TI Operações','ti@hospital.local','Ti@123456','ti'],['Analista NOC','noc@hospital.local','Noc@123456','noc'],['Analista SOC','soc@hospital.local','Soc@123456','soc'],['Gestor de TI','gestor@hospital.local','Gestor@123456','gestor'],['Visualizador','viewer@hospital.local','Viewer@123456','viewer']];
        foreach($people as [$name,$email,$password,$role]) User::updateOrCreate(['email'=>$email],['name'=>$name,'password'=>Hash::make($password),'role_id'=>Role::where('slug',$role)->value('id'),'active'=>true]);
        $names=[['HCE','Hospital Central','Florianópolis','operational',99],['HRN','Hospital Regional Norte','Joinville','warning',96],['HRS','Hospital Regional Sul','Criciúma','operational',99],['UPL','Unidade de Pronto Atendimento Leste','Itajaí','critical',86],['CDI','Centro de Diagnóstico','São José','operational',99],['UAD','Unidade Administrativa','Tijucas','offline',0]];
        foreach($names as [$code,$name,$city,$state,$uptime]) OrganizationalUnit::updateOrCreate(['code'=>$code],['name'=>$name,'city'=>$city,'state'=>'SC','kind'=>str_starts_with($code,'H')?'Hospital':'Unidade de apoio','status'=>$state,'origin'=>'demo','availability_pct'=>$uptime,'latency_ms'=>$state==='offline'?null:mt_rand(7,135),'criticality'=>'high','last_seen_at'=>$state==='offline'?now()->subMinutes(25):now()->subSeconds(mt_rand(4,70))]);
        $all=OrganizationalUnit::orderBy('id')->get();
        $accounts=User::all();
        foreach($accounts as $person){if(!$person->isSuperAdmin())$person->units()->sync($person->role?->slug==='viewer'?$all->take(3)->pluck('id')->all():$all->pluck('id')->all());}
        $assets=[];
        $cats=['router','wan','server','access_point','access_point','switch','server','access_point','firewall','server','access_point','server'];
        foreach($all as $ui=>$unit){
            foreach($cats as $i=>$cat){
                $status=($unit->status==='offline' && $i<5)?'offline':(($unit->status==='critical'&&in_array($i,[0,1,6]))||($unit->status==='warning'&&$i===1)?'offline':'online');
                $names=['Roteador principal','Link WAN primário','Servidor de aplicações','AP recepção','AP enfermagem','Switch core','Servidor PACS','AP administrativo','Firewall perimetral','Servidor AD','AP internação','Servidor de arquivos'];
                $device=Asset::updateOrCreate(['organizational_unit_id'=>$unit->id,'name'=>$names[$i]],[
                  'category'=>$cat,'manufacturer'=>match($cat){'router','wan'=>'MikroTik','access_point','switch'=>'Ubiquiti',default=>'Dell'},
                  'criticality'=>in_array($i,[0,1,2,6])?'critical':'medium','status'=>$status,
                  'hostname'=>strtolower($unit->code).'-'.str_replace('_','-',$cat).'-'.($i+1),
                  'ip_address'=>'10.'.($ui+21).'.'.(intdiv($i,5)+1).'.'.(10+$i),'origin'=>'demo',
                  'cpu_pct'=>mt_rand(12,78),'memory_pct'=>mt_rand(20,83),
                  'last_seen_at'=>$status==='offline'?now()->subMinutes(18):now()->subSeconds(mt_rand(5,85))]);
                $assets[]=$device;
                DB::table('asset_status_history')->updateOrInsert(['asset_id'=>$device->id,'changed_at'=>now()->subDays(2)->startOfMinute()],['previous_status'=>null,'status'=>$status]);
            }
        }
        foreach(config('noc.sources') as $type=>$label) Integration::firstOrCreate(['type'=>$type,'name'=>$label.' · demonstração'],['mode'=>'demo','status'=>'demo','enabled'=>false,'poll_seconds'=>in_array($type,['glpi','wazuh','duplicati'])?60:30,'timeout_seconds'=>10]);
        $severities=['critical','high','medium','low'];
        $incidentTitles=['Conectividade da unidade comprometida','Serviço crítico indisponível','Latência WAN elevada','Falha recorrente no backup','Incidente de segurança em investigação','Access Point desconectado','Servidor com consumo elevado','Falha de comunicação com o roteador'];
        $users=User::whereHas('role',fn($q)=>$q->where('slug','noc'))->first();
        foreach($incidentTitles as $i=>$title){$unit=$all[$i%$all->count()];$status=($i===7)?'resolved':($i===3?'monitoring':($i%3===0?'investigating':'new'));
            $inc=Incident::updateOrCreate(['reference'=>'INC-'.str_pad($i+1,5,'0',STR_PAD_LEFT)],['organizational_unit_id'=>$unit->id,'title'=>$title,'description'=>'Ocorrência simulada para validação das rotinas operacionais.','severity'=>$severities[$i%4],'status'=>$status,'source'=>'demo','assigned_to'=>$i%2===0?$users?->id:null,'possible_cause'=>$i===0?'Possível indisponibilidade do roteador principal':null,'resolved_at'=>$status==='resolved'?now()->subHours(3):null]);
            $a=$assets[$i*7%count($assets)];
            Alert::updateOrCreate(['dedupe_key'=>'demo-incident-'.$i],['organizational_unit_id'=>$unit->id,'asset_id'=>$a->organizational_unit_id===$unit->id?$a->id:null,'incident_id'=>$inc->id,'title'=>$title,'severity'=>$inc->severity,'status'=>$status==='resolved'?'resolved':'active','source'=>'demo','opened_at'=>now()->subHours(2+$i),'resolved_at'=>$status==='resolved'?now()->subHours(3):null]);
        }
        foreach($all as $ui=>$unit){
            for($t=0;$t<5;$t++) Ticket::updateOrCreate(['organizational_unit_id'=>$unit->id,'external_id'=>'DEMO-'.$unit->code.'-'.$t],[
                'title'=>['Impressora sem acesso à rede','Instabilidade no Wi-Fi','Acesso ao sistema hospitalar','Atualização de estação','Verificação de cabeamento'][$t],
                'status'=>['open','in_progress','pending','resolved','open'][$t],
                'priority'=>['low','high','critical','medium','medium'][$t],
                'technician'=>$t%2?'Equipe Infraestrutura':null,'category'=>['Hardware','Rede','Sistemas','Suporte','Rede'][$t],
                'opened_at'=>now()->subDays($t+1),'sla_due_at'=>now()->addHours(4-$t*2),
                'resolved_at'=>$t===3?now()->subHours(4):null,'simulated'=>true]);
            for($b=0;$b<3;$b++){
                $status=($ui===3&&$b===0)||($ui===1&&$b===2)?'failed':'success';
                $job=BackupJob::updateOrCreate(['organizational_unit_id'=>$unit->id,'name'=>['Backup prontuário (fictício)','Backup arquivos','Backup banco de dados'][$b]],['status'=>$status,'last_run_at'=>now()->subHours(4+$b),'next_run_at'=>now()->addHours(20+$b),'restoration_tested'=>$ui===0&&$b===0,'origin'=>'demo']);
                if(DB::table('backup_executions')->where('backup_job_id',$job->id)->doesntExist()) DB::table('backup_executions')->insert(['backup_job_id'=>$job->id,'status'=>$status,'duration_seconds'=>mt_rand(120,2400),'processed_bytes'=>mt_rand(1000000,200000000),'started_at'=>now()->subHours(4+$b),'finished_at'=>now()->subHours(3+$b)]);
            }
        }
        DB::table('metric_definitions')->insertOrIgnore([['key'=>'cpu','label'=>'CPU','unit'=>'%'],['key'=>'memory','label'=>'RAM','unit'=>'%'],['key'=>'latency','label'=>'Latência','unit'=>'ms']]);
        if(MetricSample::query()->doesntExist()){
            $rows=[];
            foreach($assets as $device){for($d=29;$d>=0;$d--)for($h=0;$h<24;$h+=6){
                $sampled=now()->subDays($d)->startOfDay()->addHours($h);
                foreach(['cpu'=>(float)mt_rand(15,85),'memory'=>(float)mt_rand(20,88)] as $key=>$value)$rows[]=['asset_id'=>$device->id,'organizational_unit_id'=>$device->organizational_unit_id,'metric_key'=>$key,'value'=>$value,'sampled_at'=>$sampled,'simulated'=>1];
                if(count($rows)>=800){DB::table('metric_samples')->insert($rows);$rows=[];}
            }}if($rows)DB::table('metric_samples')->insert($rows);
        }
        if(DB::table('notifications')->doesntExist()){
            $firstAlert=Alert::where('status','active')->where('severity','critical')->first();
            if($firstAlert) foreach($accounts as $account) if($account->hasPermission('notifications.view') && $account->canAccessUnit($firstAlert->organizational_unit_id)){
                $account->notify(new \App\Notifications\OperationalAlertNotification($firstAlert->id,$firstAlert->title,$firstAlert->severity,$firstAlert->unit?->name??'Unidade'));
            }
        }
        DB::table('system_settings')->updateOrInsert(['key'=>'demo_seeded'],['value'=>'true','updated_at'=>now()]);
    }
}
