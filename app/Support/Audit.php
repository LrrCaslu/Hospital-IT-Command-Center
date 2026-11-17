<?php
namespace App\Support;
use App\Models\AuditLog;
class Audit {
 public static function record(string $action,?object $entity=null,array $detail=[]):void {
  // Nunca registrar dados brutos de formulários, tokens, senhas ou respostas das APIs.
  AuditLog::create(['user_id'=>auth()->id(),'action'=>$action,'entity_type'=>$entity ? class_basename($entity):null,'entity_id'=>$entity?->id,'ip_address'=>request()?->ip(),'detail'=>$detail,'created_at'=>now()]);
 }
}
