<?php
return [
    'mode'=>env('NOC_MODE','demo'),'demo_tick'=>(bool)env('NOC_DEMO_TICK',true),
    'refresh_seconds'=>(int)env('NOC_REFRESH_SECONDS',10),
    'stale_after_seconds'=>(int)env('NOC_STALE_AFTER_SECONDS',180),
    'roles'=>[
      'superadmin'=>['*'],
      'ti'=>['dashboard.view','units.view','units.manage','assets.view','assets.manage','alerts.view','alerts.manage','incidents.view','incidents.manage','tickets.view','backups.view','security.view','reports.view','integrations.view','notifications.view','audit.view'],
      'noc'=>['dashboard.view','units.view','assets.view','alerts.view','alerts.manage','incidents.view','incidents.manage','tickets.view','backups.view','reports.view','notifications.view'],
      'soc'=>['dashboard.view','units.view','assets.view','alerts.view','alerts.manage','incidents.view','incidents.manage','security.view','notifications.view'],
      'gestor'=>['dashboard.view','units.view','assets.view','alerts.view','incidents.view','tickets.view','backups.view','reports.view','notifications.view'],
      'viewer'=>['dashboard.view','units.view','assets.view','alerts.view','incidents.view','tickets.view','backups.view','notifications.view'],
    ],
    'sources'=>['mikrotik'=>'MikroTik','glpi'=>'GLPI','unifi'=>'UniFi','zabbix'=>'Zabbix','wazuh'=>'Wazuh','duplicati'=>'Duplicati'],
    'severities'=>['info','low','medium','high','critical'],
];
