<?php
namespace App\Services\Connectors;
use App\Models\Integration;
interface ConnectorInterface {
 public function test(Integration $integration):array;
 /** @return array{assets:array,metrics:array,tickets:array,meta:array} */
 public function collect(Integration $integration):array;
}
