<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class InvoiceService
{
    /**
     * Transition invoice from draft -> confirmed.
     */
    public function confirm(Invoice $i): Invoice
    {
        $currentStatus = $i->status instanceof InvoiceStatus ? $i->status : InvoiceStatus::from((string) $i->status);

        if ($currentStatus !== InvoiceStatus::Draft) {
            throw new InvalidArgumentException("Cannot confirm invoice {$i->number} with status '{$currentStatus->value}'. Only draft invoices can be confirmed.");
        }

        $i->status = InvoiceStatus::Confirmed;
        $i->save();

        return $i->fresh();
    }

    /**
     * Transition invoice from draft or confirmed -> cancelled.
     * Reject when paid or when payments exist.
     */
    public function cancel(Invoice $i): Invoice
    {
        $currentStatus = $i->status instanceof InvoiceStatus ? $i->status : InvoiceStatus::from((string) $i->status);

        if ($currentStatus === InvoiceStatus::Paid) {
            throw new InvalidArgumentException("Cannot cancel invoice {$i->number} because it has already been paid.");
        }

        if ($i->payments()->exists() || (float) $i->amount_paid > 0) {
            throw new InvalidArgumentException("Cannot cancel invoice {$i->number} because payments have already been recorded against it.");
        }

        if (! in_array($currentStatus, [InvoiceStatus::Draft, InvoiceStatus::Confirmed], true)) {
            throw new InvalidArgumentException("Cannot cancel invoice {$i->number} with status '{$currentStatus->value}'.");
        }

        $i->status = InvoiceStatus::Cancelled;
        $i->save();

        return $i->fresh();
    }

    /**
     * Set sent_at and send InvoiceMail with the invoice PDF attached.
     */
    public function send(Invoice $i): Invoice
    {
        $i->sent_at = now();
        $i->save();

        $i->loadMissing('customer');

        if ($i->customer?->email) {
            Mail::to($i->customer->email)->send(new InvoiceMail($i));
        }

        return $i->fresh();
    }

    /**
     * Record a payment against an invoice.
     * Inside a DB transaction:
     * - validate amount > 0 and amount <= total - amount_paid
     * - create the Payment
     * - increase amount_paid
     * - set status paid when amount_paid >= total
     */
    public function recordPayment(Invoice $i, array $data): Invoice
    {
        return DB::transaction(function () use ($i, $data) {
            // Lock invoice row for update to ensure concurrency safety
            $invoice = Invoice::lockForUpdate()->findOrFail($i->id);

            $currentStatus = $invoice->status instanceof InvoiceStatus ? $invoice->status : InvoiceStatus::from((string) $invoice->status);

            if ($currentStatus === InvoiceStatus::Cancelled) {
                throw new InvalidArgumentException("Cannot record payment for cancelled invoice {$invoice->number}.");
            }

            if ($currentStatus === InvoiceStatus::Paid) {
                throw new InvalidArgumentException("Invoice {$invoice->number} is already fully paid.");
            }

            $amount = (float) ($data['amount'] ?? 0);
            $total = (float) $invoice->total;
            $amountPaid = (float) $invoice->amount_paid;
            $balance = round($total - $amountPaid, 2);

            if ($amount <= 0) {
                throw new InvalidArgumentException('Payment amount must be greater than 0.');
            }

            // Allow for minor precision rounding differences (up to 0.005)
            if ($amount > ($balance + 0.005)) {
                throw new InvalidArgumentException("Payment amount (\${$amount}) exceeds the remaining balance (\${$balance}).");
            }

            $method = $data['method'] ?? PaymentMethod::Card->value;
            if ($method instanceof PaymentMethod) {
                $method = $method->value;
            }

            $paidOn = $data['paid_on'] ?? today()->toDateString();
            $note = $data['note'] ?? null;
            $recordedBy = $data['recorded_by'] ?? auth()->id();

            Payment::create([
                'invoice_id' => $invoice->id,
                'method' => $method,
                'amount' => $amount,
                'paid_on' => $paidOn,
                'note' => $note,
                'recorded_by' => $recordedBy,
            ]);

            $newAmountPaid = round($amountPaid + $amount, 2);
            $invoice->amount_paid = $newAmountPaid;

            if ($newAmountPaid >= ($total - 0.005)) {
                $invoice->status = InvoiceStatus::Paid;
            } elseif ($currentStatus === InvoiceStatus::Draft) {
                // If it was draft and partially paid, advance to confirmed
                $invoice->status = InvoiceStatus::Confirmed;
            }

            $invoice->save();

            return $invoice->fresh(['payments', 'customer', 'subscription']);
        });
    }
}
