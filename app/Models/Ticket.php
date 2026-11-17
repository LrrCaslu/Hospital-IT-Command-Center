<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Ticket extends Model {protected $guarded=['id'];protected function casts():array{return ['opened_at'=>'datetime','resolved_at'=>'datetime','sla_due_at'=>'datetime','simulated'=>'boolean'];}public function unit(){return $this->belongsTo(OrganizationalUnit::class,'organizational_unit_id');}}
