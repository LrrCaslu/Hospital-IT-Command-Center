<?php
namespace App\Services\Connectors;
use App\Models\Integration;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
abstract class AbstractConnector implements ConnectorInterface {
 protected function client(Integration $i):PendingRequest {
  $url=(string)$i->base_url;
  if(!filter_var($url,FILTER_VALIDATE_URL) || parse_url($url,PHP_URL_SCHEME)!=='https' || parse_url($url,PHP_URL_USER) || parse_url($url,PHP_URL_PASS) || parse_url($url,PHP_URL_FRAGMENT))throw new RuntimeException('A integração requer URL HTTPS válida, sem credenciais embutidas.');
  $options=$i->options??[];
  $verify=true;
  if(!empty($options['ca_bundle'])){
   // Caminho de certificado definido pelo administrador local, não exposto em logs.
   if(!is_file($options['ca_bundle']))throw new RuntimeException('Arquivo de CA não encontrado.');
   $verify=$options['ca_bundle'];
  }
  return Http::acceptJson()->asJson()->timeout(min(max($i->timeout_seconds,2),30))->connectTimeout(5)->withOptions(['verify'=>$verify,'allow_redirects'=>false])->retry(2,350,throw:false);
 }
 protected function url(Integration $i,string $path):string {return rtrim($i->base_url,'/').'/'.ltrim($path,'/');}
 protected function mustSucceed($response):array {
  if(!$response->successful())throw new RuntimeException('Falha HTTP da integração: '.(int)$response->status());
  if(!is_array($response->json()))throw new RuntimeException('Resposta da API não é JSON válido.');
  return $response->json();
 }
 protected function credentials(Integration $i):array {return $i->credentials??[];}
 protected function emptyPayload():array {return ['assets'=>[],'metrics'=>[],'tickets'=>[],'meta'=>[]];}
}
