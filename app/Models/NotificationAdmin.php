<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationAdmin extends Model
{
    protected $table = 'notifications_admin';

    protected $fillable = [
        'admin_id', 'type', 'titre', 'message',
        'reclamation_id', 'url', 'lu_at',
    ];

    protected $casts = [
        'lu_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function reclamation()
    {
        return $this->belongsTo(Reclamation::class);
    }

    public function estLue(): bool
    {
        return $this->lu_at !== null;
    }
}
