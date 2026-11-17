<?php
namespace App\Console\Commands;
use App\Models\Integration;
use App\Jobs\SyncIntegrationJob;
use App\Services\DemoSimulator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
class CollectorCommand extends Command {
 protected $signature='noc:collect {--once : Executar uma rodada e terminar} {--sleep=10 : Intervalo contínuo, em segundos}';
 protected $description='Despacha coletas independentes; funciona continuamente em períodos menores que um minuto';
 public function handle(DemoSimulator $demo):int {
  do {
    if(config('noc.mode')==='demo'){$count=$demo->tick();$this->line('Demonstração: '.$count.' métricas simuladas.');}
    else{$due=Integration::where('enabled',true)->where('mode','integrated')->get();foreach($due as $i){$seconds=max(10,(int)$i->poll_seconds);if(!$i->last_attempt_at||$i->last_attempt_at->lte(now()->subSeconds($seconds))){SyncIntegrationJob::dispatch($i->id);$i->update(['last_attempt_at'=>now()]);}}$this->line('Verificadas '.$due->count().' integrações.');}
    if($this->option('once'))break;
    sleep(max(5,min(60,(int)$this->option('sleep'))));
  }while(true);
  return self::SUCCESS;
 }
}
