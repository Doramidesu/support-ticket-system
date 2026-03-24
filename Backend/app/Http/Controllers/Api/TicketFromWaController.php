<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Support\Str;

class TicketFromWaController extends Controller
{
    public function store(Request $request)
    {
        // ====== VALIDATION ======
        $data = $request->validate([
            'phone'      => 'required|string',
            'wa_jid'     => 'required|string',
            'name'       => 'nullable|string',
            'nim'        => 'nullable|string',
            'unit'       => 'nullable|string',
            'message'    => 'required|string',
            'from_me'    => 'nullable|boolean',
            'message_id' => 'nullable|string',
            'timestamp'  => 'nullable',
            'priority'   => 'nullable|in:low,medium,high',
        ]);

        // ====== NORMALIZE UNIT ======
        $unit = ucfirst(strtolower($data['unit'] ?? 'Gen'));

        // ====== PREFIX PER UNIT ======
        $unitPrefix = [
            'Welearn' => 'WL',
            'Siak'    => 'SK',
            'Kpst'    => 'KP',
            'Krs'     => 'KR',
            'Email'   => 'EM',
            'Puskom'  => 'PK',
            'Gen'     => 'GN',
        ];

        $prefix = $unitPrefix[$unit] ?? 'GN';

        // ====== GENERATE UNIQUE TICKET CODE ======
        do {
            $code = $prefix . '-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Ticket::where('code', $code)->exists());

        // ====== AUTO EMAIL UNTUK USER WA ======
        $waEmail = 'wa_' . $data['phone'] . '@example.com';

        // ====== FIND OR CREATE USER ======
        $user = User::firstOrCreate(
            ['email' => $waEmail],
            [
                'name'     => $data['name'] ?? 'User Whatsapp',
                'password' => bcrypt(Str::random(32)),
                'role'     => 'user',
            ]
        );

        // ====== TITLE TICKET ======
        $fullMessage = $data['message'];

        if (!empty($data['name']) || !empty($data['nim'])) {
            $titleParts = [];
            if (!empty($data['name'])) $titleParts[] = $data['name'];
            if (!empty($data['nim']))  $titleParts[] = $data['nim'];
            $title = Str::limit(implode(' - ', $titleParts), 80);
        } else {
            $title = Str::limit($fullMessage, 80);
        }

        // ====== PRIORITY ======
        $priority = $data['priority'] ?? 'low';

        // ====== CREATE TICKET ======
        $ticket = Ticket::create([
            'user_id'     => $user->id,
            'code'        => $code,
            'title'       => $title,
            'description' => $fullMessage,
            'status'      => 'open',
            'priority'    => $priority,
            'unit'        => $unit,              // ⭐ penting untuk filter admin
            'phone'       => $data['phone'],
            'wa_jid'      => $data['wa_jid'],
            'nim'         => $data['nim'],
        ]);

        // ====== RESPONSE ======
        return response()->json([
            'success' => true,
            'message' => 'Ticket berhasil dibuat dari WhatsApp',
            'data'    => [
                'ticket' => $ticket,
                'user'   => $user,
            ],
        ], 201);
    }
}
