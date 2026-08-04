<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $topUsers = User::where('boipoka_points', '>', 0)
            ->orderBy('boipoka_points', 'desc')
            ->limit(50)
            ->get();

        return view('pages.leaderboard', [
            'topUsers' => $topUsers,
            'seoTitle' => 'Monthly Boipoka Leaderboard - OpenShelf',
            'seoDesc' => 'Discover the top book lenders and borrowers on OpenShelf this month. Compete to become the ultimate Boipoka (Bookworm) and earn your badge!',
            'seoOgType' => 'website'
        ]);
    }
}
