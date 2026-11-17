<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OperationalAlertNotification extends Notification
{
    use Queueable;
    public function __construct(public int $alertId,public string $title,public string $severity,public string $unitName){}
    public function via(object $notifiable):array{return ['database'];}
    public function toArray(object $notifiable):array{return ['title'=>$this->severity==='critical'?'Alerta crítico':'Novo alerta operacional','message'=>substr($this->title,0,160),'severity'=>$this->severity,'unit'=>$this->unitName,'alert_id'=>$this->alertId];}
}
