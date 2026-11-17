<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\{User,Role,OrganizationalUnit,Alert,Incident,Asset,Integration};
use App\Services\{AlertService,IntegrationSyncService};

class PlatformTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void {parent::setUp();$this->seed();}
 private function person(string $email):User {return User::where('email',$email)->firstOrFail();}
 public function test_home_redirects_guests_and_allows_login():void {
   $this->get('/')->assertRedirect('/login');
   $this->post('/login',['email'=>'admin@hospital.local','password'=>'Admin@123456'])->assertRedirect('/');
   $this->assertAuthenticated();
 }
 public function test_six_units_and_real_database_indicators_exist():void {
   $this->actingAs($this->person('admin@hospital.local'));
   $this->assertSame(6,OrganizationalUnit::count());
   $this->get('/')->assertOk()->assertSee('Visão geral')->assertSee('Hospital Central');
   $this->get('/api/dashboard')->assertOk()->assertJsonPath('cards.0.value',6);
 }
 public function test_viewer_cannot_manage_alerts_or_access_unassigned_units():void {
   $this->actingAs($this->person('viewer@hospital.local'));
   $unit=OrganizationalUnit::where('code','UAD')->firstOrFail();
   $this->get('/unidades/'.$unit->id)->assertForbidden();
   $this->get('/api/dashboard?unit='.$unit->id)->assertForbidden();
   $this->post('/unidades',['name'=>'Não pode'])->assertForbidden();
   $this->post('/alertas/'.Alert::firstOrFail()->id.'/reconhecer')->assertForbidden();
   $this->get('/modulos/audit')->assertForbidden();
 }
 public function test_noc_can_acknowledge_alert_without_changing_asset_state():void {
   $this->actingAs($this->person('noc@hospital.local'));$alert=Alert::where('status','active')->firstOrFail();
   $before=$alert->asset?->status;
   $this->post('/alertas/'.$alert->id.'/reconhecer')->assertRedirect();
   $this->assertDatabaseHas('alerts',['id'=>$alert->id,'status'=>'acknowledged']);
   if($alert->asset)$this->assertSame($before,$alert->asset->fresh()->status);
 }
 public function test_incident_comments_are_persisted_and_status_changes_audited():void {
   $this->actingAs($this->person('noc@hospital.local'));$incident=Incident::where('status','new')->firstOrFail();
   $this->post('/incidentes/'.$incident->id.'/comentarios',['body'=>'Diagnóstico em andamento'])->assertRedirect();
   $this->post('/incidentes/'.$incident->id.'/estado',['status'=>'investigating'])->assertRedirect();
   $this->assertDatabaseHas('incident_comments',['incident_id'=>$incident->id,'body'=>'Diagnóstico em andamento']);
   $this->assertDatabaseHas('incidents',['id'=>$incident->id,'status'=>'investigating']);
   $this->assertDatabaseHas('audit_logs',['action'=>'incidents.status']);
 }
 public function test_alert_dedupe_does_not_generate_multiple_active_incidents():void {
   $s=app(AlertService::class);$u=OrganizationalUnit::firstOrFail();$before=Incident::count();
   $a=$s->raise($u->id,'integration-test-unique','Roteador sem comunicação','high');
   $b=$s->raise($u->id,'integration-test-unique','Roteador sem comunicação','high');
   $this->assertSame($a->id,$b->id);$this->assertSame($before+1,Incident::count());
 }
 public function test_secrets_are_encrypted_at_rest():void {
   $i=Integration::firstOrFail();$i->update(['credentials'=>['api_token'=>'private-test-secret']]);
   $raw=DB::table('integrations')->where('id',$i->id)->value('credentials');
   $this->assertStringNotContainsString('private-test-secret',$raw);
   $this->assertSame('private-test-secret',$i->fresh()->credentials['api_token']);
 }
 public function test_http_api_failure_is_sanitized_and_does_not_destroy_data():void {
   $i=Integration::where('type','mikrotik')->firstOrFail();$i->update(['enabled'=>true,'mode'=>'integrated','base_url'=>'https://router.test','username'=>'readonly','credentials'=>['password'=>'private123'],'organizational_unit_id'=>OrganizationalUnit::first()->id]);
   Http::fake(['router.test/*'=>Http::response(['error'=>'secret private123'],500)]);
   $result=app(IntegrationSyncService::class)->run($i->fresh());
   $this->assertSame('failed',$result['status']);
   $this->assertStringNotContainsString('private123',$i->fresh()->last_error);
   $this->assertGreaterThan(0,Asset::count());
 }
 public function test_report_export_is_scoped():void {
   $this->actingAs($this->person('viewer@hospital.local'));
   $this->get('/relatorios/incidentes.csv')->assertForbidden();
   $this->actingAs($this->person('gestor@hospital.local'));
   $this->get('/relatorios/incidentes.csv')->assertOk()->assertHeader('content-type','text/csv; charset=UTF-8');
 }
 public function test_maintenance_suppresses_incident_but_preserves_alert():void {
   $this->actingAs($this->person('noc@hospital.local'));
   $unit=OrganizationalUnit::firstOrFail();
   DB::table('maintenance_windows')->insert(['organizational_unit_id'=>$unit->id,'name'=>'Manutenção de teste','starts_at'=>now()->subMinute(),'ends_at'=>now()->addHour(),'created_at'=>now(),'updated_at'=>now()]);
   $a=app(AlertService::class)->raise($unit->id,'maintenance-test','Link em manutenção','high');
   $this->assertNull($a->incident_id);
   $this->assertNotNull($a->muted_until);
   $this->assertDatabaseHas('alert_events',['alert_id'=>$a->id,'kind'=>'maintenance_suppressed']);
 }
 public function test_integrated_mode_does_not_count_demo_records():void {
   $this->actingAs($this->person('admin@hospital.local'));
   config(['noc.mode'=>'integrated']);
   $this->get('/api/dashboard')->assertOk()->assertJsonPath('cards.0.value',0)->assertJsonPath('cards.4.value',0);
 }
 public function test_manual_incidents_are_visible_in_demo_mode():void {
   $this->actingAs($this->person('noc@hospital.local'));
   $unit=OrganizationalUnit::firstOrFail();
   $this->post('/incidentes',['organizational_unit_id'=>$unit->id,'title'=>'Incidente manual de teste','description'=>'Observação de teste','severity'=>'medium'])->assertRedirect();
   $this->get('/incidentes')->assertOk()->assertSee('Incidente manual de teste');
 }
}
