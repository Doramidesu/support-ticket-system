<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WaNotificationController extends Controller
{
    public function pending()
    {
        $notifications = TicketNotification::whereNull('sent_at')
            ->orderBy('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $notifications,
        ]);
    }
    
public function markSent($id)
    {
        $notification = TicketNotification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->sent_at = Carbon::now();
        $notification->save();

        return response()->json([
            'success' => true,
        ]);
    }

}
