<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Incident extends Model {
 protected $guarded=['id'];
 protected function casts():array{return ['acknowledged_at'=>'datetime','resolved_at'=>'datetime'];}
 public function unit(){return $this->belongsTo(OrganizationalUnit::class,'organizational_unit_id');}
 public function assignedTo(){return $this->belongsTo(User::class,'assigned_to');}
 public function comments(){return $this->hasMany(IncidentComment::class)->latest();}
 public function events(){return $this->hasMany(IncidentEvent::class)->latest('created_at');}
 public function alerts(){return $this->hasMany(Alert::class);}
}
