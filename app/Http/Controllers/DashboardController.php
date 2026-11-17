<?php
namespace App\Http\Controllers;
use App\Services\DashboardService;
use App\Models\OrganizationalUnit;
use Illuminate\Http\Request;
class DashboardController extends Controller {
 public function index(Request $request,DashboardService $service){$filters=$request->validate(['unit'=>['nullable','integer'],'days'=>['nullable','integer','in:1,7,30']]);$data=$service->summary(isset($filters['unit'])?(int)$filters['unit']:null,(int)($filters['days']??7));$choices=OrganizationalUnit::whereIn('id',$request->user()->unitIds())->orderBy('name')->get();return view('dashboard',compact('data','choices'));}
 public function data(Request $request,DashboardService $service){$filters=$request->validate(['unit'=>['nullable','integer'],'days'=>['nullable','integer','in:1,7,30']]);$data=$service->summary(isset($filters['unit'])?(int)$filters['unit']:null,(int)($filters['days']??7));return response()->json(['cards'=>array_map(fn($c)=>['label'=>$c['label'],'value'=>$c['value']],$data['cards']),'units'=>$data['units']->map(fn($u)=>['id'=>$u->id,'status'=>$u->status,'asset_count'=>$u->asset_count,'offline_count'=>$u->offline_count,'active_incidents_count'=>$u->active_incidents_count]),'series'=>$data['series'],'last_updated'=>$data['generatedAt'],'attention'=>$data['cards'][13]['value']])->header('Cache-Control','no-store');}
 public function tv(Request $request,DashboardService $service){return view('tv',['data'=>$service->summary()]);}
}
