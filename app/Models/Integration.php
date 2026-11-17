<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Integration extends Model {
 protected $guarded=['id'];
 protected $hidden=['credentials'];
 protected function casts():array{return ['credentials'=>'encrypted:array','options'=>'array','enabled'=>'boolean','last_attempt_at'=>'datetime','last_success_at'=>'datetime'];}
 public function isStale():bool {return $this->mode==='integrated' && (!$this->last_success_at || $this->last_success_at->lt(now()->subSeconds(max((int)config('noc.stale_after_seconds'),$this->poll_seconds*3))));}
 public function unit(){return $this->belongsTo(OrganizationalUnit::class,'organizational_unit_id');}
 public function logs(){return $this->hasMany(IntegrationSyncLog::class);}
}
