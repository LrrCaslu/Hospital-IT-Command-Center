<?php
namespace App\Services\Connectors;
use App\Models\Integration;
use RuntimeException;
class MikrotikConnector extends AbstractConnector {
 public function test(Integration $i):array {$this->resource($i);return ['message'=>'RouterOS REST acessível, credenciais aceitas.'];}
 private function resource(Integration $i):array {$c=$this->credentials($i);if(empty($i->username)||empty($c['password']))throw new RuntimeException('Usuário e senha do RouterOS são necessários.');return $this->mustSucceed($this->client($i)->withBasicAuth($i->username,$c['password'])->get($this->url($i,'rest/system/resource')));}
 public function collect(Integration $i):array {$resource=$this->resource($i);$cpu=isset($resource['cpu-load'])?(float)$resource['cpu-load']:null;$memory=null;if(isset($resource['total-memory'],$resource['free-memory']) && (float)$resource['total-memory']>0)$memory=round(100*(1-((float)$resource['free-memory']/(float)$resource['total-memory'])),1);
  $deviceId='router-resource-'.($resource['board-name']??'main');return ['assets'=>[['external_id'=>$deviceId,'name'=>'MikroTik · '.($resource['board-name']??'Roteador'),'category'=>'router','status'=>'online','manufacturer'=>'MikroTik']], 'metrics'=>array_values(array_filter([$cpu===null?null:['external_id'=>$deviceId,'key'=>'cpu','value'=>$cpu],$memory===null?null:['external_id'=>$deviceId,'key'=>'memory','value'=>$memory]])), 'tickets'=>[],'meta'=>['routeros_version'=>$resource['version']??null,'uptime'=>$resource['uptime']??null]];
 }
}
