<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class DemoResetCommand extends Command {
 protected $signature='noc:demo-reset {--force : Confirmar a redefinição do banco exclusivamente demonstrativo}';
 protected $description='Redefine o ambiente de demonstração, bloqueado quando o modo não é demo';
 public function handle():int {
  if(config('noc.mode')!=='demo'){$this->error('Operação bloqueada: NOC_MODE deve ser demo.');return self::FAILURE;}
  if(!$this->option('force')){$this->error('Use --force para confirmar.');return self::FAILURE;}
  // Não há dados integrados em um ambiente de demonstração recém-inicializado.
  if(DB::table('assets')->where('origin','!=','demo')->exists() || DB::table('integrations')->where('mode','integrated')->exists()){$this->error('Há dados não demonstrativos: redefinição bloqueada.');return self::FAILURE;}
  DB::table('notifications')->delete();DB::table('incident_comments')->delete();DB::table('incident_events')->delete();DB::table('incident_assignments')->delete();DB::table('alert_events')->delete();DB::table('alerts')->delete();DB::table('incidents')->delete();DB::table('backup_executions')->delete();DB::table('backup_jobs')->delete();DB::table('tickets')->delete();DB::table('metric_samples')->delete();DB::table('asset_status_history')->delete();DB::table('asset_identifiers')->delete();DB::table('asset_relationships')->delete();DB::table('monitored_services')->delete();DB::table('assets')->delete();
  $this->call('db:seed',['--force'=>true]);$this->info('Dados demonstrativos redefinidos.');return self::SUCCESS;
 }
}
