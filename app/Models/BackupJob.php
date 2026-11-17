<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BackupJob extends Model {protected $guarded=['id'];protected function casts():array{return ['last_run_at'=>'datetime','next_run_at'=>'datetime','restoration_tested'=>'boolean'];}public function unit(){return $this->belongsTo(OrganizationalUnit::class,'organizational_unit_id');}public function executions(){return $this->hasMany(BackupExecution::class);}}
