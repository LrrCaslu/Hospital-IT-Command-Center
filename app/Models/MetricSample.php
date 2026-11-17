<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MetricSample extends Model {public $timestamps=false;protected $guarded=['id'];protected function casts():array{return ['sampled_at'=>'datetime','simulated'=>'boolean'];}}
