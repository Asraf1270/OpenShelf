<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function show(Request $request)
    {
        $user = null;

        if ($userId = $request->session()->get('user_id')) {
            $user = User::query()->select('id', 'name', 'email')->find($userId);
        }

        return view('pages.contact', [
            'user' => $user,
            'success' => session('success'),
            'error' => session('error'),
            'seoTitle' => 'Contact Us - OpenShelf',
            'seoDesc' => 'Get in touch with the OpenShelf support team.',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        try {
            ContactMessage::create([
                'id' => 'msg_' . uniqid(),
                'user_id' => $request->session()->get('user_id'),
                'name' => trim($validated['name']),
                'email' => trim($validated['email']),
                'subject' => trim($validated['subject']),
                'message' => trim($validated['message']),
                'status' => 'unread',
            ]);
        } catch (\Throwable) {
            return back()->withInput()->with('error', 'মেসেজ পাঠাতে ব্যর্থ হয়েছে। দয়া করে পরে আবার চেষ্টা করুন।');
        }

        return redirect()->route('contact')->with('success', 'আপনার মেসেজ সফলভাবে পাঠানো হয়েছে! আমরা শীঘ্রই আপনার সাথে যোগাযোগ করব।');
    }
}
