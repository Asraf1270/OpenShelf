<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->session()->has('user_id')) {
            return redirect()->route('books');
        }

        return view('home', [
            'seoTitle' => 'OpenShelf - Share Books, Share Knowledge',
            'seoDesc' => 'OpenShelf is a student-led, peer-to-peer book sharing platform. Share and borrow textbooks, novels, and guides within your campus community for free.',
        ]);
    }
}
