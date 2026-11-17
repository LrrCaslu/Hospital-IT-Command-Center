<?php
namespace App\Livewire;
use App\Models\Incident;
use Livewire\Component;
class IncidentPulse extends Component {
 public function render(){return view('livewire.incident-pulse',['incidentCount'=>Incident::whereIn('organizational_unit_id',auth()->user()->unitIds())->whereNotIn('status',['resolved','closed'])->count()]);}
}
