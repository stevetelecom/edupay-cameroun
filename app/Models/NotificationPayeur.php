<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPayeur extends Model
{
    protected $table = 'notifications_payeur';

    protected $fillable = ['user_id', 'titre', 'message', 'type', 'lu_at'];

    protected $casts = [
        'lu_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
