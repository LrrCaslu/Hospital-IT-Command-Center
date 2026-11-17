<?php
namespace App\Services\Connectors;
use App\Models\Integration;
use RuntimeException;
class WazuhConnector extends AbstractConnector {
 private function token(Integration $i):string {$c=$this->credentials($i);if(empty($i->username)||empty($c['password']))throw new RuntimeException('Usuário e senha do Wazuh não configurados.');$json=$this->mustSucceed($this->client($i)->withBasicAuth($i->username,$c['password'])->post($this->url($i,'security/user/authenticate')));if(empty($json['data']['token']))throw new RuntimeException('Wazuh não retornou token.');return $json['data']['token'];}
 public function test(Integration $i):array {$token=$this->token($i);$this->mustSucceed($this->client($i)->withToken($token)->get($this->url($i,'agents'),['limit'=>1]));return ['message'=>'Autenticação e consulta de agentes Wazuh bem-sucedidas.'];}
 public function collect(Integration $i):array {$token=$this->token($i);$json=$this->mustSucceed($this->client($i)->withToken($token)->get($this->url($i,'agents'),['limit'=>500]));$assets=[];foreach($json['data']['affected_items']??[] as $a){$assets[]=['external_id'=>(string)($a['id']??''),'name'=>(string)($a['name']??'Agente Wazuh'),'category'=>'server','status'=>($a['status']??'')==='active'?'online':'unknown'];}return ['assets'=>$assets,'metrics'=>[],'tickets'=>[],'meta'=>['agents'=>count($assets)]];}
}
