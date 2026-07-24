<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function show(Request $request)
    {
        $user = null;

        if ($userId = $request->session()->get('user_id')) {
            $user = User::query()->select('id', 'name', 'email')->find($userId);
        }

        return view('pages.report', [
            'user' => $user,
            'success' => session('success'),
            'error' => session('error'),
            'seoTitle' => 'Report an Issue - OpenShelf',
            'seoDesc' => 'Report bugs, misconduct, or other issues on OpenShelf.',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'type' => ['required', Rule::in(['bug', 'user', 'book', 'suggestion', 'other'])],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        try {
            Report::create([
                'id' => 'rep_' . uniqid(),
                'user_id' => $request->session()->get('user_id'),
                'name' => trim($validated['name']),
                'email' => trim($validated['email']),
                'type' => $validated['type'],
                'subject' => trim($validated['subject']),
                'message' => trim($validated['message']),
                'status' => 'pending',
            ]);
        } catch (\Throwable) {
            return back()->withInput()->with('error', 'রিপোর্ট সংরক্ষণ করতে ব্যর্থ হয়েছে। দয়া করে পরে আবার চেষ্টা করুন।');
        }

        return redirect()->route('report')->with('success', 'আপনার রিপোর্ট জমা দেওয়া হয়েছে। উন্নতি করতে সাহায্য করার জন্য ধন্যবাদ।');
    }
}
