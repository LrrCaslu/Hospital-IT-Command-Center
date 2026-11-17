<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Asset extends Model {
 protected $guarded=['id'];
 protected function casts():array{return ['last_seen_at'=>'datetime'];}
 public function unit(){return $this->belongsTo(OrganizationalUnit::class,'organizational_unit_id');}
 public function identifiers(){return $this->hasMany(AssetIdentifier::class);}
 public function metrics(){return $this->hasMany(MetricSample::class);}
}
