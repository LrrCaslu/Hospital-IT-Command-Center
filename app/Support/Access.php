<?php
namespace App\Support;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
class Access {
 public static function scope(Builder $query, string $column='organizational_unit_id'):Builder {return $query->whereIn($column,Auth::user()->unitIds());}
 public static function assertUnit(int $id):void {abort_unless(Auth::user()->canAccessUnit($id),403,'Unidade não autorizada.');}
}
