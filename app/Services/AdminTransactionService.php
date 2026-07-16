<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminTransactionService
{
    public function list(string $filterType = 'all', string $search = ''): Collection
    {
        $query = Transaction::query()
            ->with('supportUs')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('user_email', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%");
            });
        }

        if ($filterType === 'income') {
            $query->where('amount', '>=', 0);
        } elseif ($filterType === 'expenditure') {
            $query->where('amount', '<', 0);
        }

        return $query->get();
    }

    public function stats(): array
    {
        $totalIncome = (float) Transaction::query()->where('amount', '>=', 0)->sum('amount');
        $totalExpenditure = abs((float) Transaction::query()->where('amount', '<', 0)->sum('amount'));
        $totalTransactions = Transaction::query()->count();
        $incomeCount = Transaction::query()->where('amount', '>=', 0)->count();
        $expenditureCount = Transaction::query()->where('amount', '<', 0)->count();

        return [
            'total_income' => $totalIncome,
            'total_expenditure' => $totalExpenditure,
            'net_balance' => $totalIncome - $totalExpenditure,
            'total_transactions' => $totalTransactions,
            'income_count' => $incomeCount,
            'expenditure_count' => $expenditureCount,
        ];
    }

    public function addExpenditure(array $data, string $adminName): Transaction
    {
        $systemUserId = User::query()->value('id');

        if (! $systemUserId) {
            throw new \RuntimeException('A valid user record is required to save this expenditure.');
        }

        $expenseAmount = -abs((float) str_replace(',', '', $data['amount']));

        return Transaction::create([
            'id' => 'TXN' . strtoupper(substr(uniqid('', true), -12)),
            'support_us_id' => null,
            'user_id' => $systemUserId,
            'user_name' => $adminName,
            'user_email' => null,
            'provider' => $data['category'],
            'account_number' => '',
            'amount' => number_format($expenseAmount, 2, '.', ''),
            'transaction_id' => $data['transaction_id'],
            'invoice_number' => $data['invoice_number'],
            'status' => 'expense',
            'created_by' => $adminName,
            'created_at' => now(),
        ]);
    }

    public function generateReferenceId(): string
    {
        return 'REF' . strtoupper(substr(uniqid('', true), -8));
    }

    public function generateExpenseInvoice(): string
    {
        return 'EXP' . strtoupper(substr(uniqid('', true), -8));
    }
}
