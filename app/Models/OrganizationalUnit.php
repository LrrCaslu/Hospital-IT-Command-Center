<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrganizationalUnit extends Model {
 protected $guarded=['id'];
 protected function casts():array{return ['last_seen_at'=>'datetime'];}
 public function assets(){return $this->hasMany(Asset::class);}
 public function incidents(){return $this->hasMany(Incident::class);}
 public function alerts(){return $this->hasMany(Alert::class);}
}
