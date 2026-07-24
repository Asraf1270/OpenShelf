<?php

namespace App\Http\Controllers\Admin;

use App\Services\AdminTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTransactionsController extends AdminController
{
    public function __construct(private AdminTransactionService $adminTransactionService)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        if ($request->isMethod('post')) {
            return $this->handleAction($request, $admin);
        }

        $filterType = $request->query('type', 'all');
        $search = trim($request->query('search', ''));

        return view('admin.transactions', [
            'admin' => $admin,
            'transactions' => $this->adminTransactionService->list($filterType, $search),
            'stats' => $this->adminTransactionService->stats(),
            'filterType' => $filterType,
            'search' => $search,
            'showExpenditureForm' => $request->session()->get('show_expenditure_form', false),
        ]);
    }

    private function handleAction(Request $request, $admin): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:add_expenditure'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:50'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validated['action'] !== 'add_expenditure') {
            return back()->with('error', 'Unsupported action.');
        }

        $transactionId = trim($validated['transaction_id'] ?? '') ?: $this->adminTransactionService->generateReferenceId();
        $invoiceNumber = trim($validated['invoice_number'] ?? '') ?: $this->adminTransactionService->generateExpenseInvoice();

        try {
            $transaction = $this->adminTransactionService->addExpenditure([
                'amount' => $validated['amount'],
                'category' => trim($validated['category'] ?? 'other'),
                'transaction_id' => $transactionId,
                'invoice_number' => $invoiceNumber,
            ], $admin->name);
        } catch (\RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('show_expenditure_form', true)
                ->with('error', $exception->getMessage());
        } catch (\Throwable) {
            return back()
                ->withInput()
                ->with('show_expenditure_form', true)
                ->with('error', 'Unable to save expenditure. Please try again.');
        }

        $amount = abs((float) $transaction->amount);

        return back()->with('success', 'Expenditure of ৳' . number_format($amount, 2) . ' recorded successfully.');
    }
}
