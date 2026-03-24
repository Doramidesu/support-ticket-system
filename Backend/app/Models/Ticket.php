<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
    'user_id',
    'code',
    'title',
    'description',
    'status',
    'priority',
    'phone',    
    'wa_jid',
    'nim',    
    'completed_at'
];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticketReplies()
    {
        return $this->hasMany(TicketReply::class);
    }

    // ====== RELATION: Ticket -> Notifications ======
public function notifications()
{
    return $this->hasMany(\App\Models\TicketNotification::class);
}

}
