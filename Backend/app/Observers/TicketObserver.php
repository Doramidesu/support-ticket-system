<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\TicketNotification;

class TicketObserver
{
    public function updated(Ticket $ticket)
    {
        // Hanya kirim notif jika status berubah
        if ($ticket->isDirty('status')) {
            TicketNotification::create([
                'ticket_id' => $ticket->id,
                'wa_jid'    => $ticket->wa_jid,
                'message'   => $this->buildMessage($ticket),
            ]);
        }
    }

    private function buildMessage(Ticket $ticket)
{
    $map = [
        'open'     => '📩 Tiket kamu sudah dibuat',
        'pending'  => '📌 Tiket kamu sedang menunggu',
        'diproses' => '⚙️ Tiket kamu sedang diproses',
        'selesai'  => '✅ Tiket kamu telah diselesaikan',
        'ditolak'  => '❌ Tiket kamu ditolak',
    ];

    $statusText = $map[$ticket->status] ?? 'Status tiket diperbarui';

    return "📩 *Balasan Tiket dari Admin*

Kode : *{$ticket->code}*
Pelapor : {$ticket->title}

Deskripsi :
{$ticket->description}

Status : *{$ticket->status}*

Terima kasih telah menghubungi Puskom 🙏";
}

}
