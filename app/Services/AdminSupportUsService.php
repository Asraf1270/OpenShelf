<?php

namespace App\Services;

use App\Models\SupportUs;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminSupportUsService
{
    public function list(string $status = 'all', string $search = ''): Collection
    {
        $query = SupportUs::query()
            ->with('transaction')
            ->orderByDesc('submitted_at');

        if ($status === 'pending') {
            $query->where('status', 'pending');
        } elseif ($status === 'approved') {
            $query->where('status', 'approved');
        }

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('user_name', 'like', "%{$search}%")
                    ->orWhere('user_email', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function approve(SupportUs $support, string $adminId, string $adminName): bool
    {
        if ($support->status !== 'pending') {
            return false;
        }

        $invoiceNumber = $this->createInvoiceNumber();

        $support->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $adminId,
            'invoice_number' => $invoiceNumber,
        ]);

        if (Transaction::query()->where('support_us_id', $support->id)->exists()) {
            return true;
        }

        Transaction::create([
            'id' => 'TRX' . strtoupper(substr(uniqid('', true), -10)),
            'support_us_id' => $support->id,
            'user_id' => $support->user_id,
            'user_name' => $support->user_name,
            'user_email' => $support->user_email,
            'provider' => $support->provider,
            'account_number' => $support->account_number,
            'amount' => $support->amount,
            'transaction_id' => $support->transaction_id,
            'invoice_number' => $invoiceNumber,
            'status' => 'completed',
            'created_by' => $adminName,
            'created_at' => now(),
        ]);

        return true;
    }

    private function createInvoiceNumber(): string
    {
        return 'INV' . now()->format('YmdHis') . strtoupper(substr(uniqid('', true), -6));
    }
}
