<?php
namespace App\Services\Connectors;
use App\Models\Integration;
use RuntimeException;
class UnifiConnector extends AbstractConnector {
 private function get(Integration $i):array {$path=$i->options['devices_path']??null;if(!$path||!str_starts_with($path,'/'))throw new RuntimeException('Informe o caminho oficial da API de dispositivos para a versão UniFi em Integrações.');$token=$this->credentials($i)['api_token']??null;if(!$token)throw new RuntimeException('Chave de API UniFi não configurada.');return $this->mustSucceed($this->client($i)->withHeaders(['X-API-Key'=>$token])->get($this->url($i,$path)));}
 public function test(Integration $i):array {$this->get($i);return ['message'=>'Endpoint UniFi configurado respondeu com autenticação. Confirme o esquema da versão.'];}
 public function collect(Integration $i):array {$data=$this->get($i);$records=$data['data']??[];$assets=[];foreach($records as $d){if(!is_array($d)||!isset($d['id']))continue;$assets[]=['external_id'=>(string)$d['id'],'name'=>(string)($d['name']??'UniFi '.$d['id']),'category'=>'access_point','status'=>in_array($d['state']??'',['ONLINE','online'],true)?'online':'unknown'];}return ['assets'=>$assets,'metrics'=>[],'tickets'=>[],'meta'=>['devices'=>count($assets)]];}
}
