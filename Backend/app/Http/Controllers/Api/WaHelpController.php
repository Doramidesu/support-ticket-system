<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WaHelpLog;
use Illuminate\Support\Carbon;

class WaHelpController extends Controller
{
    public function checkAndTouch(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string', // 628xxxx
        ]);

        $phone = $data['phone'];

        // Ambil atau buat record
        $log = WaHelpLog::firstOrCreate(
            ['phone' => $phone],
            ['last_help_at' => null]
        );

        $now = Carbon::now();

        // Kalau belum pernah kirim bantuan, atau sudah lebih dari 5 menit
        if ($log->last_help_at === null || $log->last_help_at->diffInMinutes($now) >= 5) {
            $log->last_help_at = $now;
            $log->save();

            return response()->json([
                'should_reply' => true,
            ]);
        }

        // Masih dalam jangka 5 menit, jangan balas lagi
        return response()->json([
            'should_reply' => false,
        ]);
    }
}
