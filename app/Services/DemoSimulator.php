<?php
namespace App\Services;
use App\Models\{Asset,MetricSample,OrganizationalUnit};
use Illuminate\Support\Facades\{Cache,DB};
class DemoSimulator {
 public function tick():int {
  if(config('noc.mode')!=='demo' || !config('noc.demo_tick'))return 0;
  $lock=Cache::lock('noc-demo-tick',25);
  if(!$lock->get())return 0;
  try{
   $assets=Asset::where('origin','demo')->where('status','online')->inRandomOrder()->limit(7)->get();
   $samples=[];
   foreach($assets as $asset){
     $cpu=max(5,min(99,($asset->cpu_pct??35)+random_int(-6,9)));
     $mem=max(10,min(99,($asset->memory_pct??45)+random_int(-3,5)));
     $asset->update(['cpu_pct'=>$cpu,'memory_pct'=>$mem,'last_seen_at'=>now()]);
     foreach(['cpu'=>$cpu,'memory'=>$mem] as $metric=>$value)$samples[]=['asset_id'=>$asset->id,'organizational_unit_id'=>$asset->organizational_unit_id,'metric_key'=>$metric,'value'=>$value,'sampled_at'=>now(),'simulated'=>1];
     $key='demo-cpu-'.$asset->id;
     if($cpu>=85) app(AlertService::class)->raise($asset->organizational_unit_id,$key,'CPU acima do limite · '.$asset->name,'high',$asset->id,'demo');
     else if($cpu<=75) app(AlertService::class)->normalize($key);
   }
   if($samples)DB::table('metric_samples')->insert($samples);
   Cache::put('noc-demo-last-tick',now()->toIso8601String(),60);
   return count($samples);
  }finally{$lock->release();}
 }
}
