<?php
namespace App\Services;
use App\Services\Connectors\{ConnectorInterface,MikrotikConnector,GlpiConnector,ZabbixConnector,WazuhConnector,UnifiConnector,DuplicatiConnector};
use InvalidArgumentException;
class ConnectorRegistry {
 public function for(string $type):ConnectorInterface {return match($type){
 'mikrotik'=>app(MikrotikConnector::class),'glpi'=>app(GlpiConnector::class),
 'unifi'=>app(UnifiConnector::class),'zabbix'=>app(ZabbixConnector::class),
 'wazuh'=>app(WazuhConnector::class),'duplicati'=>app(DuplicatiConnector::class),
 default=>throw new InvalidArgumentException('Conector desconhecido')};}
}
