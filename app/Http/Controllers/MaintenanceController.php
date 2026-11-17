<?php
namespace App\Http\Controllers;
use App\Models\OrganizationalUnit;
use App\Support\{Access,Audit};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class MaintenanceController extends Controller {
 public function index(Request $r){$ids=$r->user()->unitIds();$windows=DB::table('maintenance_windows')->whereIn('organizational_unit_id',$ids)->orderByDesc('starts_at')->limit(100)->get();$units=OrganizationalUnit::whereIn('id',$ids)->orderBy('name')->get();return view('maintenance',compact('windows','units'));}
 public function store(Request $r){$v=$r->validate(['organizational_unit_id'=>'required|integer|exists:organizational_units,id','name'=>'required|string|max:150','starts_at'=>'required|date','ends_at'=>'required|date|after:starts_at']);Access::assertUnit((int)$v['organizational_unit_id']);$v['starts_at']=\Illuminate\Support\Carbon::parse($v['starts_at'])->toDateTimeString();$v['ends_at']=\Illuminate\Support\Carbon::parse($v['ends_at'])->toDateTimeString();$id=DB::table('maintenance_windows')->insertGetId($v+['created_by'=>$r->user()->id,'created_at'=>now(),'updated_at'=>now()]);Audit::record('maintenance.create',null,['window_id'=>$id,'unit_id'=>(int)$v['organizational_unit_id']]);return back()->with('success','Janela de manutenção registrada. Alertas gerados no período serão silenciados.');}
 public function delete(int $id){$window=DB::table('maintenance_windows')->where('id',$id)->first();abort_unless($window,404);Access::assertUnit($window->organizational_unit_id);DB::table('maintenance_windows')->where('id',$id)->delete();Audit::record('maintenance.delete',null,['window_id'=>$id,'unit_id'=>$window->organizational_unit_id]);return back()->with('success','Janela excluída; eventos históricos permanecem registrados.');}
}
