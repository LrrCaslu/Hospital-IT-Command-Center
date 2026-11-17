<?php
namespace App\Services\Connectors;
use App\Models\Integration;
use RuntimeException;
class DuplicatiConnector extends AbstractConnector {
 private function get(Integration $i):array {$path=$i->options['jobs_path']??null;if(!$path||!str_starts_with($path,'/'))throw new RuntimeException('Informe o endpoint oficial de jobs compatível com a versão Duplicati instalada.');$token=$this->credentials($i)['api_token']??null;if(!$token)throw new RuntimeException('Token de acesso Duplicati não configurado.');return $this->mustSucceed($this->client($i)->withToken($token)->get($this->url($i,$path)));}
 public function test(Integration $i):array {$this->get($i);return ['message'=>'Endpoint Duplicati configurado respondeu. Mapeamento de jobs depende da versão.'];}
 public function collect(Integration $i):array {$data=$this->get($i);return ['assets'=>[],'metrics'=>[],'tickets'=>[],'meta'=>['received_rows'=>count($data),'mapping_required'=>true]];}
}
