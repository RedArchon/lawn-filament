# MVP Multi-Tenant Lawn Care SaaS Implementation Plan

## Overview
Transform the existing lawn care management system into a multi-tenant SaaS product with complete billing, customer portal, and automation features.

---

## Phase 1: Multi-Tenancy Foundation ✅ COMPLETE

### 1.1 Create Company/Tenant Model ✅
- Create `Company` model (the tenant entity)
- Migration: `companies` table with name, slug, settings, is_active, etc.
- Factory and seeder for companies

### 1.2 Create Multi-Tenancy Trait & Interface ✅
- Create `BelongsToCompany` interface (enforces tenant relationship contract)
- Create `BelongsToCompany` trait with:
  - `company()` BelongsTo relationship
  - Global scope to auto-filter by current company
  - Boot method to auto-assign `company_id` on creation
- Helper to get current company from authenticated user

### 1.3 Add Multi-Tenancy to All Models ✅
- Add `company_id` foreign key migration to: users, customers, properties, service_types, service_schedules, service_appointments, teams, notes, seasonal_frequency_periods
- Implement `BelongsToCompany` interface on all tenant-scoped models
- Use `BelongsToCompany` trait on all tenant-scoped models

### 1.4 Update Factories & Seeders for Tenancy ✅
- Update ALL existing factories to include `company_id` or use `Company::factory()`
- Update `DatabaseSeeder` to create test companies first
- Ensure seeded data is properly assigned to companies
- Verify that `php artisan migrate:fresh --seed` works with tenancy

### 1.5 Tenant Context Management ✅
- Middleware to set current tenant from authenticated user
- Helper functions for accessing current tenant
- Update existing queries to respect tenant scope

### 1.6 Update Existing Resources ✅
- Ensure all Filament resources respect tenant scope
- Update form relationships to filter by tenant
- Update existing tests to work with tenancy

### 1.7 Phase 1 Testing & Acceptance Criteria ✅

**Acceptance Criteria:**
- ✓ Users can only see data belonging to their company
- ✓ Creating new records auto-assigns company_id
- ✓ All existing tests pass with multi-tenancy
- ✓ Seeders create multiple companies with isolated data
- ✓ No cross-tenant data leakage

**Tests to Write:**
- `MultiTenancyIsolationTest`: Verify users can't see other company's data
- `AutoAssignCompanyTest`: Verify new records auto-get company_id
- `TenantScopingTest`: Test global scopes work on all models
- `CompanyCrudTest`: Basic company CRUD operations
- Update existing feature tests to work with companies

---

## Phase 2: Invoicing System

### 2.1 Invoice Models & Database

**Invoice Model (`app/Models/Invoice.php`):**
- Fields: `company_id`, `customer_id`, `invoice_number`, `invoice_date`, `due_date`, `status`, `notes`, `sent_at`, `paid_at`
- Status enum: `draft`, `sent`, `paid`, `overdue`, `cancelled`, `refunded`
- Relationships: `belongsTo(Company)`, `belongsTo(Customer)`, `hasMany(InvoiceItem)`, `hasMany(Payment)`
- Implements `BelongsToCompany` interface/trait
- Scopes: `draft()`, `sent()`, `paid()`, `overdue()`, `forCustomer($id)`
- Calculated Accessors (aggregated from line items):
  - `subtotal()`: Sum of all line_total from items
  - `taxAmount()`: Sum of all tax_amount from items
  - `total()`: Sum of all total from items
  - `amountPaid()`: Sum of all payments
  - `amountDue()`: total - amountPaid
- Boolean Accessors: `isPaid()`, `isOverdue()`, `hasItems()`
- String Accessors: `formattedInvoiceNumber()`, `statusLabel()`, `statusColor()`

**InvoiceItem Model (`app/Models/InvoiceItem.php`):**
- Fields: `invoice_id`, `service_appointment_id`, `description`, `quantity`, `unit_price`, `tax_rate` (defaults to 0)
- Calculated/stored fields: `line_total`, `tax_amount`, `total`
- Relationships: `belongsTo(Invoice)`, `belongsTo(ServiceAppointment)`
- Auto-calculate on save:
  - `line_total` = `quantity * unit_price`
  - `tax_amount` = `line_total * (tax_rate / 100)`
  - `total` = `line_total + tax_amount`
- Boot method to recalculate parent invoice totals when item changes
- Observers to handle invoice total updates on item create/update/delete

**Migration:**
```php
// invoices table
- unique(['company_id', 'invoice_number']) // Critical for number uniqueness per tenant
- index(['company_id', 'customer_id', 'status'])
- index(['company_id', 'due_date'])
- index(['status'])

// invoice_items table
- foreign('invoice_id')->constrained()->cascadeOnDelete()
- index(['invoice_id'])
```

**Factories:**
- `InvoiceFactory`: Create invoices with various statuses, dates
- `InvoiceItemFactory`: Create items with realistic pricing
- State methods: `draft()`, `sent()`, `paid()`, `overdue()`

### 2.2 Invoice Number Generation Service

**Service Class (`app/Services/InvoiceNumberService.php`):**
```php
class InvoiceNumberService
{
    public function generate(Company $company): string
    {
        // Lock row to prevent race conditions
        DB::transaction(function() use ($company) {
            $year = now()->year;
            $prefix = $company->invoice_prefix ?? 'INV';
            
            // Get last invoice number for this company/year
            $lastInvoice = Invoice::where('company_id', $company->id)
                ->where('invoice_number', 'like', "{$prefix}-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('invoice_number')
                ->first();
            
            // Extract and increment number
            $nextNumber = 1;
            if ($lastInvoice) {
                preg_match('/-(\d+)$/', $lastInvoice->invoice_number, $matches);
                $nextNumber = ((int) $matches[1]) + 1;
            }
            
            return sprintf('%s-%d-%04d', $prefix, $year, $nextNumber);
        });
    }
    
    public function validateUnique(string $invoiceNumber, Company $company): bool
    {
        return !Invoice::where('company_id', $company->id)
            ->where('invoice_number', $invoiceNumber)
            ->exists();
    }
}
```

**Database Constraint:**
- Unique constraint on `(company_id, invoice_number)` enforces uniqueness at DB level
- Handle unique constraint violations gracefully in application layer

**Invoice Model Boot:**
```php
protected static function booted(): void
{
    static::creating(function (Invoice $invoice) {
        if (empty($invoice->invoice_number)) {
            $invoice->invoice_number = app(InvoiceNumberService::class)
                ->generate($invoice->company);
        }
    });
}
```

### 2.3 Invoice Filament Resource

**Resource Structure (`app/Filament/Resources/Invoices/`):**
- `InvoiceResource.php`
- `Pages/ListInvoices.php`
- `Pages/CreateInvoice.php`
- `Pages/EditInvoice.php`
- `Pages/ViewInvoice.php`
- `Schemas/InvoiceForm.php`
- `Tables/InvoicesTable.php`
- `RelationManagers/InvoiceItemsRelationManager.php`
- `RelationManagers/PaymentsRelationManager.php`

**Table Features:**
- Columns: invoice_number, customer, date, due_date, total, status (badge), actions
- Filters: status (select), date range, customer (select), overdue (toggle)
- Search: invoice_number, customer name
- Bulk actions: Send to Customer, Mark as Sent, Export PDFs
- Row actions: View, Edit, Download PDF, Send, Mark Paid, Cancel
- Default sort: invoice_date DESC

**Form Features:**
- Customer select (relationship, searchable)
- Invoice date (defaults to today)
- Due date (calculated from company payment terms, e.g., Net 30)
- Tax rate (defaults from company settings)
- Line items repeater:
  - Service appointment select (optional)
  - Description (required)
  - Quantity (default 1)
  - Unit price (required)
  - Calculated line total (read-only)
- Calculated fields (read-only): Subtotal, Tax Amount, Total
- Status select (only draft/sent/cancelled editable)
- Notes textarea

**Actions:**
- `SendToCustomerAction`: Email invoice PDF to customer
- `MarkPaidAction`: Modal with payment date, method, transaction ID
- `DownloadPdfAction`: Generate and download PDF
- `CancelInvoiceAction`: Mark as cancelled with reason
- `DuplicateInvoiceAction`: Create new draft from existing invoice

### 2.4 Invoice PDF Generation

**Install Package:**
```bash
composer require barryvdh/laravel-dompdf
```

**PDF View (`resources/views/invoices/pdf.blade.php`):**
- Company logo and details (from Company model)
- Invoice number, date, due date prominently displayed
- Bill To: Customer name, address, contact
- Line items table: Description, Quantity, Unit Price, Total
- Subtotal, Tax, Grand Total
- Payment instructions (bank details, online payment link)
- Notes/Terms section
- Professional styling with CSS

**Service (`app/Services/InvoicePdfService.php`):**
```php
class InvoicePdfService
{
    public function generate(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->load(['customer', 'items', 'company']);
        
        return PDF::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'company' => $invoice->company,
            'customer' => $invoice->customer,
        ])->setPaper('a4');
    }
    
    public function download(Invoice $invoice): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $pdf = $this->generate($invoice);
        $filename = "Invoice-{$invoice->invoice_number}.pdf";
        
        return $pdf->download($filename);
    }
}
```

**Storage Approach:**
- Generate on-demand only (simpler, always current, no storage management needed)
- PDFs are generated fresh each time they're requested/downloaded
- Remove `store()` method from `InvoicePdfService` - only need `generate()` and `download()`

### 2.5 Invoice Business Logic

**Invoice Service (`app/Services/InvoiceService.php`):**
```php
class InvoiceService
{
    public function createFromAppointments(
        Company $company,
        Customer $customer,
        array $appointmentIds
    ): Invoice {
        return DB::transaction(function() use ($company, $customer, $appointmentIds) {
            $invoice = Invoice::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'invoice_date' => now(),
                'due_date' => now()->addDays($company->payment_terms_days ?? 30),
                'status' => 'draft',
                'tax_rate' => $company->default_tax_rate ?? 0,
            ]);
            
            foreach ($appointmentIds as $appointmentId) {
                $appointment = ServiceAppointment::findOrFail($appointmentId);
                
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'service_appointment_id' => $appointment->id,
                    'description' => "{$appointment->serviceType->name} - {$appointment->property->full_address}",
                    'quantity' => 1,
                    'unit_price' => $appointment->serviceType->default_price,
                ]);
                
                $appointment->update(['invoiced_at' => now()]);
            }
            
            $this->calculateTotals($invoice);
            
            return $invoice;
        });
    }
    
    public function calculateTotals(Invoice $invoice): void
    {
        $invoice->load('items');
        
        $subtotal = $invoice->items->sum('line_total');
        $taxAmount = $subtotal * ($invoice->tax_rate / 100);
        $total = $subtotal + $taxAmount;
        
        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }
    
    public function markPaid(Invoice $invoice, array $paymentData): void
    {
        DB::transaction(function() use ($invoice, $paymentData) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => $paymentData['paid_at'] ?? now(),
            ]);
            
            // Record payment (Phase 6)
            // Payment::create([...]);
        });
    }
    
    public function send(Invoice $invoice): void
    {
        // Update status
        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        
        // Send notification (Phase 5)
        // $invoice->customer->notify(new InvoiceGenerated($invoice));
    }
}
```

### 2.6 Phase 2 Testing & Acceptance Criteria

**Acceptance Criteria:**
- ✓ Invoice numbers are unique per company
- ✓ Invoice numbers auto-increment within each company independently
- ✓ Invoices can be created from service appointments
- ✓ Line items calculate totals correctly (quantity × unit_price)
- ✓ Invoice totals calculate correctly (subtotal + tax)
- ✓ PDF generation works and displays all invoice data
- ✓ Invoices are tenant-scoped (users only see their company's invoices)
- ✓ Status transitions work correctly (draft → sent → paid)
- ✓ Cannot create duplicate invoice numbers (database constraint enforced)
- ✓ Service appointments marked with invoiced_at when added to invoice

**Tests to Write:**

**`InvoiceNumberGenerationTest`:**
- Test invoice numbers increment correctly per company
- Test invoice numbers reset each year (INV-2025-0001, INV-2026-0001)
- Test concurrent invoice creation doesn't create duplicates
- Test unique constraint prevents duplicate numbers
- Test different companies can have same number (INV-2025-0001 for each)

**`InvoiceCreationTest`:**
- Test creating invoice manually with line items
- Test creating invoice from service appointments
- Test line item totals calculate correctly
- Test invoice subtotal, tax, and total calculate correctly
- Test invoice auto-assigns company_id
- Test invoice requires customer_id

**`InvoicePdfTest`:**
- Test PDF generates successfully
- Test PDF contains all required fields
- Test PDF download returns valid file

**`InvoiceStatusTest`:**
- Test draft → sent transition
- Test sent → paid transition
- Test marking invoice as paid updates paid_at
- Test cannot transition from paid to draft
- Test cancelled invoices cannot be marked paid

**`InvoiceTenancyTest`:**
- Test users only see their company's invoices
- Test invoice creation auto-assigns correct company_id
- Test invoice items only reference appointments from same company
- Test PDF generation respects tenant isolation

**`InvoiceFilamentResourceTest`:**
- Test invoice list page shows company invoices
- Test invoice creation through Filament
- Test invoice editing through Filament
- Test mark paid action works
- Test download PDF action works
- Test send to customer action (placeholder for Phase 5)

**Unit Tests:**
- `InvoiceNumberServiceTest`: Test number generation logic
- `InvoiceServiceTest`: Test business logic methods
- `InvoicePdfServiceTest`: Test PDF generation service

---

## Phase 3: Customer Portal

### 4.1 Customer User Authentication
- Separate `customer_users` table OR add role to existing users table
- Customer authentication guard/panel
- Customer registration (invited by tenant)
- Password reset flow for customers

### 4.2 Customer Portal Panel
- New Filament panel for customers
- Dashboard showing upcoming services, recent invoices
- Read-only property information
- Service history view

### 4.3 Customer Portal Pages
- **Appointments Page**: List upcoming/past appointments
- **Invoices Page**: List invoices with status, download PDF
- **Properties Page**: View their properties and service schedules
- **Profile Page**: Update contact info, password

### 4.4 Customer Portal Access Control
- Customers only see their own data
- Policies to enforce customer-level permissions
- Middleware to ensure customers can't access tenant panel

---

## Phase 4: Notifications System

### 5.1 Notification Infrastructure
- Configure mail driver (queue)
- Email templates for each notification type
- SMS integration setup (Twilio optional)

### 5.2 Customer Notifications
- Appointment reminder (1-2 days before)
- Appointment completion confirmation
- Invoice generated notification
- Invoice due reminder
- Payment received confirmation

### 5.3 Tenant Notifications
- New customer registered
- Appointment completed
- Payment received
- Overdue invoice alerts

### 5.4 Notification Preferences
- Per-customer notification settings
- Email vs SMS preferences
- Notification schedule/timing

---

## Phase 5: Payment Processing

### 6.1 Stripe Integration
- Install Laravel Cashier or Stripe PHP SDK
- Store Stripe customer IDs on customers table
- Webhook endpoint for payment confirmations
- Store payment method tokens securely

### 6.2 Payment Recording
- `Payment` model: invoice_id, amount, method, transaction_id, date
- Migration and relationships
- Payment history on invoices

### 6.3 Online Payment Flow
- Customer portal: Pay Invoice button
- Stripe Checkout integration
- Payment success/failure handling
- Automatic invoice status update on payment

### 6.4 Payment Management
- Tenant view of all payments
- Refund capability
- Failed payment handling

---

## Phase 6: Admin Features

### 7.1 Super Admin Panel
- Create separate admin Filament panel
- Company/Tenant management resource
- View all tenants and their data
- System-wide metrics

### 7.2 Impersonation
- Install impersonation package
- Super admin can impersonate any tenant user
- Clear indication when impersonating
- Exit impersonation action

---

## Phase 7: Testing & Refinement

### 8.1 Test Coverage
- Multi-tenancy isolation tests
- Invoice generation tests
- Payment processing tests
- Customer portal access tests
- Notification delivery tests

### 8.2 Policies & Authorization
- Create policies for all models
- Ensure tenant data isolation
- Customer portal permissions
- Admin impersonation authorization

### 8.3 UI/UX Polish
- Consistent styling across panels
- Mobile-responsive customer portal
- Loading states and error handling
- Success notifications

---

## Implementation Notes

**Key Files to Create:**
- `app/Models/Company.php` ✅
- `app/Models/Invoice.php`
- `app/Models/InvoiceItem.php`
- `app/Models/Payment.php`
- `app/Models/CustomerUser.php` (or extend User)
- `app/Filament/Admin/` (super admin resources)
- `app/Filament/Customer/` (customer portal pages)
- `app/Services/InvoiceService.php`
- `app/Services/PaymentService.php`
- Middleware for tenant context ✅
- Global scopes for tenant filtering ✅

**Migrations Order:**
1. Create companies table ✅
2. Add company_id to all existing tables ✅
3. Create invoices, invoice_items, payments tables
4. Add invoice-related fields to service_appointments ✅
5. Customer authentication tables/fields

**Configuration Updates:**
- `config/filament.php` - Add admin and customer panels
- `config/services.php` - Stripe keys
- `.env` - Add Stripe, mail, SMS credentials

