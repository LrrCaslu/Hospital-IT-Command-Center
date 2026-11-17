<?php
namespace App\Jobs;
use App\Models\Integration;
use App\Services\IntegrationSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
class SyncIntegrationJob implements ShouldQueue {
 use Queueable;
 public int $tries=2;
 public int $timeout=50;
 public function __construct(public int $integrationId){}
 public function handle(IntegrationSyncService $service):void {$integration=Integration::find($this->integrationId);if($integration)$service->run($integration);}
 public function backoff():array{return [5,20];}
}
