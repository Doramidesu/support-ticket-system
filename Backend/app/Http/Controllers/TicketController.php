<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketReplyStoreRequest;
use App\Http\Requests\TicketStoreRequest;
use App\Http\Resources\TicketReplyResource;
use App\Http\Resources\TicketResource;
use App\Models\TicketNotification;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Ticket::query();

            $query->orderBy('created_at', 'desc');

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('code', 'like', '%' . $request->search . '%')
                        ->orWhere('title', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->priority) {
                $query->where('priority', $request->priority);
            }

            if (auth()->user()->role == 'user') {
                $query->where('user_id', auth()->user()->id);
            }

            $tickets = $query->get();

            return response()->json([
                'message' => 'Data Ticket Berhasil Ditampilkan',
                'data'    => TicketResource::collection($tickets),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'data'    => null,
            ], 500);
        }
    }

    // UNTUK PANEL WEB (dalam auth:sanctum)
    public function show($code)
    {
        try {
            $ticket = Ticket::where('code', $code)->first();

if (!$ticket) {
    return response()->json([
        'message' => 'Ticket Tidak Ditemukan',
    ], 404);
}

            

            $user = auth()->user();

            if ($user && $user->role == 'user' && $ticket->user_id != $user->id) {
                return response()->json([
                    'message' => 'Anda Tidak Diperbolehkan Mengakses Ticket ini',
                ], 403);
            }

            return response()->json([
                'message' => 'Ticket Berhasil Ditampilkan',
                'data'    => [
                    'ticket' => new TicketResource($ticket),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function formatStatus($status)
    {
        return match ($status) {
            'open' => '🔵 Open',
            'diproses' => '🟡 Diproses',
            'resolved' => '🟢 Selesai',
            'ditolak' => '🔴 Ditolak',
            default => $status
        };
    }

    // KHUSUS WHATSAPP BOT 
    public function waStatus($code)
    {
        try {// validasi format kode tiket
        if (!preg_match('/^[A-Z]{2}-\d{8}-[A-Z0-9]{6}$/', $code)) {
            return response()->json([
                'success' => false,
                'message' => 'Format kode tiket tidak valid',
            ], 400);
        }

            $ticket = Ticket::where('code', $code)->first();

if (!$ticket) {
    return response()->json([
        'success' => false,
        'message' => 'Ticket Tidak Ditemukan',
    ], 404);
}

            

            // TIDAK pakai auth() di sini, karena diakses dari bot WA

            return response()->json([
                'success' => true,
                'message' => 'Ticket Berhasil Ditampilkan',
                'ticket'  => [
                    'code'     => $ticket->code,
                    'title'    => $ticket->title,
                    'status'   => $ticket->status,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi Kesalahan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // KHUSUS WHATSAPP BOT (tiket saya)
public function waMyTickets(Request $request)
{
    try {

        $jid = $request->input('wa_jid');

        if (!$jid) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 400);
        }

        $tickets = Ticket::where('wa_jid', $jid)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        if ($tickets->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada tiket'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'tickets' => $tickets->map(function ($t) {
                return [
                    'code' => $t->code,
                    'title' => $t->title,
                    'status' => $t->status
                ];
            })
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function store(TicketStoreRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $ticket = new Ticket();
            $ticket->user_id = auth()->user()->id;
            $ticket->code = 'TIC-' . rand(10000, 99999);
            $ticket->title = $data['title'];
            $ticket->description = $data['description'];
            $ticket->priority = $data['priority'];
            $ticket->save();

            DB::commit();

            return response()->json([
                'message' => 'Ticket berhasil ditambahkan',
                'data'    => new TicketResource($ticket),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'data'    => null,
            ], 500);
        }
    }

    public function storeReply(TicketReplyStoreRequest $request, $code)
{
    $data = $request->validated();

    DB::beginTransaction();

    try {
        $ticket = Ticket::where('code', $code)->first();

        if (!$ticket) {
    return response()->json([
        'success' => false,
        'message' => 'Ticket Tidak Ditemukan',
    ], 404);
}

        if (auth()->user()->role == 'user' && $ticket->user_id != auth()->user()->id) {
            return response()->json([
                'message' => 'Anda Tidak Diperbolehkan Membalas Ticket ini',
            ], 403);
        }

        // simpan balasan
        $ticketReply = new TicketReply();
        $ticketReply->ticket_id = $ticket->id;
        $ticketReply->user_id   = auth()->user()->id;
        $ticketReply->content   = $data['content'];
        $ticketReply->save();

        // JIKA ADMIN → update status + buat notif WA
        if (auth()->user()->role == 'admin') {

            // update status ticket (kalau dikirim)
            if (isset($data['status'])) {
    $ticket->status = $data['status'];

    if ($data['status'] == 'resolved') {
        $ticket->completed_at = now();
    }
}

            $ticket->save();

            $content = trim($ticketReply->content);
            // kalau tiket punya wa_jid → kirim WA ke user
            if ($ticket->wa_jid) {
                // pesan WA yang dikirim ke user
            $message =
"📩 *Update Tiket Anda*

📌 *Kode Tiket*
{$ticket->code}

📊 *Status*
" . $this->formatStatus($ticket->status) . "

💬 *Balasan Admin*
{$content}

Jika masih mengalami kendala, silakan balas tiket kembali melalui sistem helpdesk.

Terima kasih 🙏";

                TicketNotification::create([
                    'ticket_id' => $ticket->id,
                    'wa_jid'    => $ticket->wa_jid,
                    'message'   => $message,
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Balasan Berhasil Ditambahkan',
            'data'    => new TicketReplyResource($ticketReply),
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Terjadi Kesalahan',
            'data'    => $e->getMessage(),
        ], 500);
    }
}
}
