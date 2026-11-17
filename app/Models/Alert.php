<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Alert extends Model {
 protected $guarded=['id'];
 protected function casts():array{return ['opened_at'=>'datetime','muted_until'=>'datetime','acknowledged_at'=>'datetime','resolved_at'=>'datetime'];}
 public function unit(){return $this->belongsTo(OrganizationalUnit::class,'organizational_unit_id');}
 public function asset(){return $this->belongsTo(Asset::class);}
 public function incident(){return $this->belongsTo(Incident::class);}
}
