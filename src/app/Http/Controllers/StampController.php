<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrectionRequest;

class StampController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $tab = $request->query('tab', 'waiting');


        $query = AttendanceCorrectionRequest::with('attendance')
            ->whereHas('attendance', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orderBy('submitted_at', 'desc');

        if ($tab === 'approved') {
            $query->where('status', 'approved');
        } else {
            $query->where('status', '!=', 'approved');
        }

        $requests = $query->get();

        return view('stamp.list', compact('requests', 'tab'));
    }
}
