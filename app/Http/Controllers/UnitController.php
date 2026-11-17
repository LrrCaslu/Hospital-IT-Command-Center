<?php
namespace App\Http\Controllers;
use App\Models\{OrganizationalUnit,Asset};
use App\Support\{Access,Audit};
use Illuminate\Http\Request;
class UnitController extends Controller {
 public function index(Request $r){$units=OrganizationalUnit::whereIn('id',$r->user()->unitIds())->withCount(['assets','incidents'=>fn($q)=>$q->whereNotIn('status',['resolved','closed'])])->orderBy('name')->get();return view('units',compact('units'));}
 public function show(OrganizationalUnit $unit){Access::assertUnit($unit->id);$demo=config('noc.mode')==='demo';$assets=$unit->assets()->where('origin','!=',$demo?'integrated':'demo')->orderBy('category')->paginate(30);$alerts=$unit->alerts()->where(fn($q)=>$demo?$q->whereIn('source',['demo','manual']):$q->where('source','!=','demo'))->whereIn('status',['active','acknowledged'])->latest()->limit(8)->get();return view('unit-detail',compact('unit','assets','alerts'));}
 public function store(Request $r){$d=$r->validate(['code'=>'required|string|max:15|unique:organizational_units,code','name'=>'required|string|max:160','kind'=>'required|string|max:80','city'=>'required|string|max:100','state'=>'required|string|size:2','technical_contact'=>'nullable|string|max:100','manager'=>'nullable|string|max:100','notes'=>'nullable|string|max:2000']);$unit=OrganizationalUnit::create($d+['status'=>'unknown','availability_pct'=>0]);if(!$r->user()->isSuperAdmin())$r->user()->units()->syncWithoutDetaching($unit->id);Audit::record('units.create',$unit);return redirect()->route('units.show',$unit)->with('success','Unidade cadastrada.');}
 public function update(Request $r,OrganizationalUnit $unit){Access::assertUnit($unit->id);$d=$r->validate(['name'=>'required|string|max:160','kind'=>'required|string|max:80','city'=>'required|string|max:100','state'=>'required|string|size:2','technical_contact'=>'nullable|string|max:100','manager'=>'nullable|string|max:100','notes'=>'nullable|string|max:2000']);$unit->update($d);Audit::record('units.update',$unit);return back()->with('success','Unidade atualizada.');}
}
