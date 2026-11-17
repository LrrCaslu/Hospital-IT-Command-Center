<?php
namespace App\Services\Connectors;
use App\Models\Integration;
use RuntimeException;
class ZabbixConnector extends AbstractConnector {
 private function rpc(Integration $i,string $method,array $params):array {$token=$this->credentials($i)['api_token']??null;if(!$token)throw new RuntimeException('Token de API do Zabbix não configurado.');$r=$this->mustSucceed($this->client($i)->withToken($token)->post($this->url($i,'api_jsonrpc.php'),['jsonrpc'=>'2.0','method'=>$method,'params'=>$params,'id'=>1]));if(isset($r['error']))throw new RuntimeException('Zabbix recusou a chamada (código '.($r['error']['code']??'?').').');return $r['result']??[];}
 public function test(Integration $i):array {$this->rpc($i,'host.get',['output'=>['hostid'],'limit'=>1]);return ['message'=>'Zabbix respondeu a host.get com autenticação.'];}
 public function collect(Integration $i):array {$rows=$this->rpc($i,'host.get',['output'=>['hostid','host','name','status'],'selectInterfaces'=>['ip','available'],'limit'=>500]);$assets=[];foreach($rows as $r){$assets[]=['external_id'=>(string)$r['hostid'],'name'=>(string)($r['name']?:$r['host']),'category'=>'server','status'=>(int)($r['status']??1)===1?'unknown':match((int)($r['interfaces'][0]['available']??0)){1=>'online',2=>'offline',default=>'unknown'},'ip_address'=>$r['interfaces'][0]['ip']??null];}return ['assets'=>$assets,'metrics'=>[],'tickets'=>[],'meta'=>['hosts'=>count($assets)]];}
}
