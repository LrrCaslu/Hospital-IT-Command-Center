<?php
namespace App\Services\Connectors;
use App\Models\Integration;
use RuntimeException;
class GlpiConnector extends AbstractConnector {
 private function session(Integration $i):string {$creds=$this->credentials($i);if(empty($creds['api_token']))throw new RuntimeException('GLPI exige user token.');$headers=['Authorization'=>'user_token '.$creds['api_token']];if(!empty($creds['app_token']))$headers['App-Token']=$creds['app_token'];$json=$this->mustSucceed($this->client($i)->withHeaders($headers)->get($this->url($i,'apirest.php/initSession')));if(empty($json['session_token']))throw new RuntimeException('GLPI não forneceu sessão válida.');return $json['session_token'];}
 private function headers(Integration $i,string $session):array {$h=['Session-Token'=>$session];if(!empty(($this->credentials($i))['app_token']))$h['App-Token']=$this->credentials($i)['app_token'];return $h;}
 public function test(Integration $i):array {$token=$this->session($i);$this->client($i)->withHeaders($this->headers($i,$token))->get($this->url($i,'apirest.php/killSession'));return ['message'=>'Sessão GLPI autenticada.'];}
 public function collect(Integration $i):array {$token=$this->session($i);try{$json=$this->mustSucceed($this->client($i)->withHeaders($this->headers($i,$token))->get($this->url($i,'apirest.php/Ticket'),['range'=>'0-99']));}finally{$this->client($i)->withHeaders($this->headers($i,$token))->get($this->url($i,'apirest.php/killSession'));}
  $tickets=[];foreach($json as $row){if(!is_array($row)||!isset($row['id']))continue;$tickets[]=['external_id'=>(string)$row['id'],'title'=>(string)($row['name']??'Chamado '.$row['id']),'status'=>match((int)($row['status']??1)){1=>'open',2=>'in_progress',3,4=>'pending',5=>'resolved',6=>'closed',default=>'unknown'},'priority'=>match((int)($row['priority']??3)){5,6=>'critical',4=>'high',3=>'medium',default=>'low'}];}
  return ['assets'=>[],'metrics'=>[],'tickets'=>$tickets,'meta'=>['count'=>count($tickets)]];
 }
}
