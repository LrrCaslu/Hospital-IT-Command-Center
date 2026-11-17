<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use Notifiable;
    protected $fillable=['name','email','password','role_id','active'];
    protected $hidden=['password','remember_token'];
    protected function casts(): array {return ['email_verified_at'=>'datetime','password'=>'hashed','active'=>'boolean'];}
    public function role(): BelongsTo {return $this->belongsTo(Role::class);}
    public function units(): BelongsToMany {return $this->belongsToMany(OrganizationalUnit::class,'user_unit_access');}
    public function isSuperAdmin(): bool {return $this->role?->slug === 'superadmin';}
    public function hasPermission(string $permission): bool
    {
        if (!$this->active) return false;
        if ($this->isSuperAdmin()) return true;
        return $this->role?->permissions()->where('slug',$permission)->exists() ?? false;
    }
    public function unitIds(): array {return $this->isSuperAdmin() ? OrganizationalUnit::pluck('id')->all() : $this->units()->pluck('organizational_units.id')->all();}
    public function canAccessUnit(int $id): bool {return $this->isSuperAdmin() || $this->units()->where('organizational_units.id',$id)->exists();}
}
