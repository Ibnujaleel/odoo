<div class="space-y-6">
    <x-ui.page-header
        :title="'Quotation '.$subscription->number"
        :subtitle="$subscription->plan?->name"
    >
        <x-slot name="actions">
            <div class="flex items-center gap-2">
                <a href="{{ route('portal.quotations.pdf', $subscription) }}" target="_blank" class="btn-secondary">
                    Download PDF
                </a>
                <a href="{{ route('portal.quotations') }}" class="btn-secondary">
                    Back to quotations
                </a>
            </div>
        </x-slot>
    </x-ui.page-header>

    @if (session('status'))
        <div class="rounded-card border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @error('status')
        <div class="rounded-card border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-800">
            {{ $message }}
        </div>
    @enderror

    @if ($this->isExpired())
        <div class="rounded-card border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <strong>Quotation Expired:</strong> This quotation expired on {{ $subscription->quotation_valid_until?->format('M j, Y') }} and cannot be accepted. Please reach out to your Brightloop representative to request a renewed proposal.
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Status" :value="$subscription->status->label()" hint="Current quotation stage" tone="primary" />
        <x-ui.stat-card label="Valid until" :value="$subscription->quotation_valid_until?->format('M j, Y') ?? '—'" hint="Acceptance deadline" tone="{{ $this->isExpired() ? 'danger' : 'warning' }}" />
        <x-ui.stat-card label="Plan Price" value="${{ number_format((float) $subscription->plan?->price, 2) }}" hint="Per {{ $subscription->plan?->billing_period?->label() ?? 'cycle' }}" tone="slate" />
        <x-ui.stat-card label="Total Amount" value="${{ number_format($calc['grand_total'], 2) }}" hint="Includes taxes &amp; discounts" tone="success" />
    </div>

    <x-ui.card title="Quotation Line Items" subtitle="Configured products, licenses and services">
        @if ($subscription->lines->isEmpty())
            <x-ui.empty-state title="No line items configured on this quotation." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Product / Service</th>
                            <th class="px-4 py-3">Variant</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                            <th class="px-4 py-3 text-right">Unit Price</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($subscription->lines as $line)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $line->product?->name ?? 'Product' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $line->variant?->displayName() ?? 'Standard' }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $line->quantity }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">${{ number_format((float) $line->unit_price, 2) }}</td>
                                <td class="px-4 py-3 text-right font-medium text-slate-900">${{ number_format((float) $line->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end border-t border-slate-100 pt-4">
                <div class="w-full max-w-xs space-y-2 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal:</span>
                        <span class="font-medium text-slate-900">${{ number_format($calc['subtotal'], 2) }}</span>
                    </div>
                    @if ($calc['tax'] > 0)
                        <div class="flex justify-between text-slate-600">
                            <span>Estimated Tax:</span>
                            <span class="font-medium text-slate-900">${{ number_format($calc['tax'], 2) }}</span>
                        </div>
                    @endif
                    @if ($calc['discount'] > 0)
                        <div class="flex justify-between text-emerald-600">
                            <span>Discount:</span>
                            <span class="font-medium">-${{ number_format($calc['discount'], 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold text-slate-900">
                        <span>Grand Total:</span>
                        <span class="text-primary-600">${{ number_format($calc['grand_total'], 2) }}</span>
                    </div>
                </div>
            </div>
        @endif

        <x-slot name="footer">
            @if ($subscription->status->value === 'quotation')
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="text-xs text-slate-500">
                        Payment Terms: <strong class="text-slate-700">{{ strtoupper(str_replace('_', ' ', $subscription->payment_terms ?? 'Net 30')) }}</strong>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="openRejectModal"
                            class="btn-danger"
                        >
                            Reject Quotation
                        </button>

                        <button
                            type="button"
                            wire:click="accept"
                            @disabled($this->isExpired())
                            class="btn-primary {{ $this->isExpired() ? 'opacity-50 cursor-not-allowed' : '' }}"
                        >
                            Accept Quotation
                        </button>
                    </div>
                </div>
            @endif
        </x-slot>
    </x-ui.card>

    {{-- Proceed to payment step --}}
    @if ($subscription->status->value === 'confirmed' || ($showPaymentStep && $subscription->status->value !== 'active'))
        <x-ui.card title="Step 2: Payment Confirmation &amp; Subscription Activation" subtitle="Review payment terms to activate your recurring plan">
            @error('payment')
                <div class="mb-4 rounded-card border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                    {{ $message }}
                </div>
            @enderror

            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-4 rounded-card border border-slate-200 bg-slate-50 p-4">
                    <h4 class="text-sm font-semibold text-slate-900 uppercase tracking-wide">Agreed Terms</h4>
                    <p class="text-sm text-slate-600">
                        Plan: <strong>{{ $subscription->plan?->name }}</strong> ({{ ucfirst($subscription->plan?->billing_period?->value ?? 'Monthly') }})
                    </p>
                    <p class="text-sm text-slate-600">
                        Payment Terms: <strong>{{ strtoupper(str_replace('_', ' ', $subscription->payment_terms ?? 'Net 30')) }}</strong>
                    </p>
                    <p class="text-sm text-slate-600">
                        Amount to authorize: <strong class="text-lg text-slate-900">${{ number_format($calc['grand_total'], 2) }}</strong>
                    </p>
                    <p class="text-xs text-slate-500">
                        By confirming, you agree to recurring billing per your agreed terms. Invoices will automatically be issued per your billing period.
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Payment Method</label>
                        <select wire:model="paymentMethod" class="field-input mt-1 w-full">
                            <option value="card">Credit / Debit Card</option>
                            <option value="bank_transfer">Bank Wire Transfer</option>
                            <option value="upi">UPI / Instant Transfer</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Payment Reference or Note (Optional)</label>
                        <input type="text" wire:model="paymentNote" placeholder="e.g. Purchase Order #, transaction ID" class="field-input mt-1 w-full" />
                    </div>

                    <div class="pt-2">
                        <button
                            type="button"
                            wire:click="confirmPaymentAndActivate"
                            class="btn-primary w-full justify-center py-2.5"
                        >
                            Confirm Payment &amp; Activate Subscription
                        </button>
                    </div>
                </div>
            </div>
        </x-ui.card>
    @endif

    @if ($subscription->status->value === 'active')
        <div class="rounded-card border border-emerald-200 bg-emerald-50 p-6 text-center text-emerald-900">
            <h3 class="text-lg font-semibold text-emerald-800">Subscription Active</h3>
            <p class="mt-2 text-sm text-emerald-700">
                This quotation has been confirmed and activated. Your subscription is active and scheduled for automated recurring billing.
            </p>
            <div class="mt-4">
                <a href="{{ route('portal.subscriptions') }}" class="btn-primary inline-block">
                    View in My Subscriptions
                </a>
            </div>
        </div>
    @endif

    {{-- Rejection modal --}}
    @if ($showRejectModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-lg rounded-card bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-slate-900">Reject Quotation</h3>
                <p class="mt-1 text-sm text-slate-600">
                    Please tell us why you are declining this quotation so our sales team can assist you with an adjusted proposal.
                </p>

                <div class="mt-4">
                    <label for="rejection_reason" class="block text-sm font-medium text-slate-700">Reason for Rejection</label>
                    <textarea
                        id="rejection_reason"
                        wire:model="rejection_reason"
                        rows="4"
                        placeholder="e.g. Budget constraints, feature requirements not met, timeframe changes..."
                        class="field-input mt-1 w-full"
                    ></textarea>
                    @error('rejection_reason')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="closeRejectModal" class="btn-secondary">
                        Cancel
                    </button>
                    <button type="button" wire:click="reject" class="btn-danger">
                        Confirm Rejection
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
