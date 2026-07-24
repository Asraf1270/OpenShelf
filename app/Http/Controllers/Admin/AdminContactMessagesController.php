<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminContactMessagesController extends AdminController
{
    public function index(Request $request): View|RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        if ($request->isMethod('post')) {
            return $this->handleAction($request, $admin);
        }

        $statusFilter = $request->string('status')->toString() ?: 'all';

        $query = ContactMessage::query()->latest('created_at');

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        return view('admin.contact-messages', [
            'admin' => $admin,
            'messages' => $query->get(),
            'statusFilter' => $statusFilter,
            'totalMsgs' => ContactMessage::query()->count(),
            'unreadMsgs' => ContactMessage::query()->where('status', 'unread')->count(),
            'repliedMsgs' => ContactMessage::query()->where('status', 'replied')->count(),
        ]);
    }

    private function handleAction(Request $request, $admin): RedirectResponse
    {
        $action = $request->input('action');

        if ($action === 'reply') {
            $validated = $request->validate([
                'msg_id' => ['required', 'string', 'exists:contact_messages,id'],
                'admin_reply' => ['required', 'string'],
            ]);

            $message = ContactMessage::query()->findOrFail($validated['msg_id']);
            $message->update([
                'status' => 'replied',
                'admin_reply' => trim($validated['admin_reply']),
                'replied_by' => $admin->name,
                'replied_at' => now(),
            ]);

            $emailSent = $this->sendReplyEmail($message);

            return back()->with(
                'success',
                $emailSent
                    ? 'Reply saved and email sent to ' . $message->email . '.'
                    : 'Reply saved but email could not be sent. Use the mailto link to send manually.',
            );
        }

        if ($action === 'mark_read') {
            $validated = $request->validate([
                'msg_id' => ['required', 'string', 'exists:contact_messages,id'],
            ]);

            ContactMessage::query()
                ->whereKey($validated['msg_id'])
                ->update(['status' => 'read']);

            return back()->with('success', 'Message marked as read.');
        }

        if ($action === 'delete') {
            $validated = $request->validate([
                'msg_id' => ['required', 'string', 'exists:contact_messages,id'],
            ]);

            ContactMessage::query()->whereKey($validated['msg_id'])->delete();

            return back()->with('success', 'Message deleted.');
        }

        return back()->with('error', 'Unsupported action.');
    }

    private function sendReplyEmail(ContactMessage $message): bool
    {
        try {
            $body = "প্রিয় {$message->name},\n\n"
                . $message->admin_reply . "\n\n"
                . "---\n"
                . "{$message->replied_by}\n"
                . "OpenShelf Support Team\n"
                . config('app.url') . "\n";

            Mail::raw($body, function ($mail) use ($message) {
                $mail->to($message->email, $message->name)
                    ->subject('Re: ' . $message->subject . ' — OpenShelf Support')
                    ->replyTo('support@duopenshelf.top', 'OpenShelf Support');
            });

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
