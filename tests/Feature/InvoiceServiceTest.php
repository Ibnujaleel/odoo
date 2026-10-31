<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Mail\InvoiceMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceService $service;

    protected Customer $customer;

    protected Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InvoiceService::class);

        $plan = Plan::factory()->create();
        $this->customer = Customer::factory()->create([
            'email' => 'client@brightloop.test',
        ]);
        $this->subscription = Subscription::factory()->create([
            'customer_id' => $this->customer->id,
            'plan_id' => $plan->id,
        ]);
    }

    private function createTestInvoice(array $attributes = []): Invoice
    {
        $invoice = Invoice::create(array_merge([
            'number' => 'INV-TEST-'.uniqid(),
            'subscription_id' => $this->subscription->id,
            'customer_id' => $this->customer->id,
            'billing_period_start' => Carbon::today()->startOfMonth(),
            'billing_period_end' => Carbon::today()->endOfMonth(),
            'status' => InvoiceStatus::Draft->value,
            'subtotal' => 100.00,
            'discount_total' => 0.00,
            'tax_total' => 10.00,
            'total' => 110.00,
            'amount_paid' => 0.00,
            'due_date' => Carbon::today()->addDays(15),
            'sent_at' => null,
        ], $attributes));

        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'description' => 'Test Service Line',
            'quantity' => 1,
            'unit_price' => 100.00,
            'discount_amount' => 0.00,
            'tax_amount' => 10.00,
            'amount' => 110.00,
        ]);

        return $invoice->fresh();
    }

    public function test_can_confirm_draft_invoice(): void
    {
        $invoice = $this->createTestInvoice(['status' => InvoiceStatus::Draft->value]);

        $confirmed = $this->service->confirm($invoice);

        $this->assertEquals(InvoiceStatus::Confirmed, $confirmed->status);
    }

    public function test_cannot_confirm_already_confirmed_or_paid_invoice(): void
    {
        $invoice = $this->createTestInvoice(['status' => InvoiceStatus::Confirmed->value]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->confirm($invoice);
    }

    public function test_can_cancel_draft_invoice_without_payments(): void
    {
        $invoice = $this->createTestInvoice(['status' => InvoiceStatus::Draft->value]);

        $cancelled = $this->service->cancel($invoice);

        $this->assertEquals(InvoiceStatus::Cancelled, $cancelled->status);
    }

    public function test_can_cancel_confirmed_invoice_without_payments(): void
    {
        $invoice = $this->createTestInvoice(['status' => InvoiceStatus::Confirmed->value]);

        $cancelled = $this->service->cancel($invoice);

        $this->assertEquals(InvoiceStatus::Cancelled, $cancelled->status);
    }

    public function test_cannot_cancel_paid_invoice(): void
    {
        $invoice = $this->createTestInvoice([
            'status' => InvoiceStatus::Paid->value,
            'amount_paid' => 110.00,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->cancel($invoice);
    }

    public function test_cannot_cancel_invoice_with_existing_payments(): void
    {
        $invoice = $this->createTestInvoice([
            'status' => InvoiceStatus::Confirmed->value,
            'amount_paid' => 50.00,
        ]);
        Payment::create([
            'invoice_id' => $invoice->id,
            'method' => PaymentMethod::Card->value,
            'amount' => 50.00,
            'paid_on' => today(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->cancel($invoice);
    }

    public function test_send_invoice_sets_sent_at_and_dispatches_email(): void
    {
        Mail::fake();

        $invoice = $this->createTestInvoice(['status' => InvoiceStatus::Confirmed->value]);

        $sent = $this->service->send($invoice);

        $this->assertNotNull($sent->sent_at);
        Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail) use ($invoice) {
            return $mail->invoice->id === $invoice->id;
        });
    }

    public function test_partial_payment_keeps_status_confirmed_and_increases_amount_paid(): void
    {
        $invoice = $this->createTestInvoice([
            'status' => InvoiceStatus::Confirmed->value,
            'total' => 200.00,
            'amount_paid' => 0.00,
        ]);

        $updated = $this->service->recordPayment($invoice, [
            'amount' => 50.00,
            'method' => PaymentMethod::Card->value,
            'paid_on' => today()->toDateString(),
            'note' => 'Partial payment',
        ]);

        $this->assertEquals(InvoiceStatus::Confirmed, $updated->status);
        $this->assertEquals(50.00, (float) $updated->amount_paid);
        $this->assertCount(1, $updated->payments);
        $this->assertEquals(50.00, (float) $updated->payments->first()->amount);
    }

    public function test_full_payment_sets_status_paid(): void
    {
        $invoice = $this->createTestInvoice([
            'status' => InvoiceStatus::Confirmed->value,
            'total' => 200.00,
            'amount_paid' => 50.00,
        ]);

        $updated = $this->service->recordPayment($invoice, [
            'amount' => 150.00,
            'method' => PaymentMethod::BankTransfer->value,
            'paid_on' => today()->toDateString(),
        ]);

        $this->assertEquals(InvoiceStatus::Paid, $updated->status);
        $this->assertEquals(200.00, (float) $updated->amount_paid);
        $this->assertEquals(0, $updated->balance());
    }

    public function test_overpayment_is_rejected(): void
    {
        $invoice = $this->createTestInvoice([
            'status' => InvoiceStatus::Confirmed->value,
            'total' => 100.00,
            'amount_paid' => 0.00,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->recordPayment($invoice, [
            'amount' => 105.00,
            'method' => PaymentMethod::Card->value,
        ]);
    }

    public function test_zero_or_negative_payment_is_rejected(): void
    {
        $invoice = $this->createTestInvoice([
            'status' => InvoiceStatus::Confirmed->value,
            'total' => 100.00,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->recordPayment($invoice, [
            'amount' => 0,
            'method' => PaymentMethod::Cash->value,
        ]);
    }

    public function test_payment_on_already_paid_invoice_is_rejected(): void
    {
        $invoice = $this->createTestInvoice([
            'status' => InvoiceStatus::Paid->value,
            'total' => 100.00,
            'amount_paid' => 100.00,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->recordPayment($invoice, [
            'amount' => 10.00,
            'method' => PaymentMethod::Card->value,
        ]);
    }

    public function test_customer_can_download_only_their_own_invoice_pdf(): void
    {
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $userCustomer = User::factory()->create();
        $userCustomer->assignRole('customer');
        $this->customer->update(['user_id' => $userCustomer->id]);

        $otherCustomer = Customer::factory()->create();
        $otherUser = User::factory()->create();
        $otherUser->assignRole('customer');
        $otherCustomer->update(['user_id' => $otherUser->id]);

        $invoice = $this->createTestInvoice([
            'customer_id' => $this->customer->id,
            'status' => InvoiceStatus::Confirmed->value,
        ]);

        // Owning customer can view
        $response = $this->actingAs($userCustomer)->get(route('portal.invoices.pdf', $invoice));
        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));

        // Other customer cannot view (403 forbidden)
        $forbiddenResponse = $this->actingAs($otherUser)->get(route('portal.invoices.pdf', $invoice));
        $forbiddenResponse->assertForbidden();
    }
}
