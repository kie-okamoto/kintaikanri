<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;

class StampController extends Controller
{
    public function index()
    {
        $attendances = Attendance::where('user_id', Auth::id())
            ->where('approval_status', 'pending')
            ->orderByDesc('updated_at')
            ->get();

        return view('stamp.list', compact('attendances'));
    }
}
