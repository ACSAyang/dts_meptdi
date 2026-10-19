<?php

namespace App\Http\Controllers;

use App\Models\IncomingCommunication;
use App\Models\OutgoingCommunication;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $incoming = $outgoing = collect();

        if (mb_strlen($q) >= 2) {
            $incoming = IncomingCommunication::search($q)->latest('received_at')->limit(50)->get();
            $outgoing = OutgoingCommunication::search($q)->latest('id')->limit(50)->get();
        }

        return view('reports.search', compact('q', 'incoming', 'outgoing'));
    }
}
