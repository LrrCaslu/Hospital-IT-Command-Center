<?php
namespace App\Http\Controllers;
use App\Models\Alert;
use App\Support\{Access,Audit};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AlertController extends Controller {
 public function index(Request $r){$v=$r->validate(['status'=>'nullable|in:active,acknowledged,resolved','severity'=>'nullable|in:info,low,medium,high,critical']);$q=Alert::with(['unit','incident','asset'])->where(fn($q)=>config('noc.mode')==='demo'?$q->whereIn('source',['demo','manual']):$q->where('source','!=','demo'));Access::scope($q);foreach(['status','severity'] as $key)if(!empty($v[$key]))$q->where($key,$v[$key]);$alerts=$q->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")->latest('opened_at')->paginate(20)->withQueryString();return view('alerts',compact('alerts'));}
 public function acknowledge(Alert $alert){Access::assertUnit($alert->organizational_unit_id);if($alert->status==='resolved')return back()->withErrors(['alert'=>'Alerta já resolvido.']);$alert->update(['status'=>'acknowledged','acknowledged_at'=>now(),'acknowledged_by'=>auth()->id()]);Audit::record('alerts.acknowledge',$alert);return back()->with('success','Alerta reconhecido.');}
 public function mute(Request $r,Alert $alert){Access::assertUnit($alert->organizational_unit_id);$v=$r->validate(['minutes'=>'required|integer|min:5|max:1440']);$alert->update(['muted_until'=>now()->addMinutes((int)$v['minutes'])]);Audit::record('alerts.mute',$alert,['minutes'=>(int)$v['minutes']]);return back()->with('success','Silenciado temporariamente.');}
 public function resolve(Alert $alert){Access::assertUnit($alert->organizational_unit_id);if(!in_array($alert->status,['active','acknowledged']))return back();$alert->update(['status'=>'resolved','resolved_at'=>now()]);Audit::record('alerts.resolve',$alert);return back()->with('success','Alerta encerrado manualmente. O estado do equipamento não foi alterado.');}
}
