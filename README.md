### System Architecture Overview


[ Customer Portal / UI ]
        │
        ├── Product Discovery & Configuration
        └── Multi-step Quotation Request Form (Guest / User)
        │
        ▼
[ Backend API & Workflow Engine ]
        │
        ├── Request Processing & Validation
        ├── Price Calculation Engine (Rules & Inventory DB)
        └── Event Dispatcher (Email Notification / Lead Routing)
        │
        ├──► [ Sales Portal / CRM ]
        │           │
        │           ├── Lead Management & Activities
        │           ├── Quotation Builder & CPQ Engine
        │           └── Discount Approval Routing
        │
        └──► [ Customer Acceptance Interface ]
                    │
                    ├── Accept / Request Changes / Reject
                    └── Digital Signature & Timestamp Freeze
                    │
                    ▼
          [ Core Subscription Engine ]
                    │
                    ├── Provisioning & Account Mapping
                    ├── Billing Schedule Engine
                    └── Finance Notification

```


### Component Architecture & Responsibilities

#### 1. Customer Portal (Frontend)

* **Product Catalog Module:** Renders product details, variants, features, and base subscription plans sourced directly from the inventory database.
* **Dynamic Configuration Engine:** Calculates instant indicative pricing client-side based on variant selection, license volume, billing cycle, and add-on modules.
* **Multi-Step Request Form:**
* Auto-prefills identity data for authenticated users.
* Captures contact details, product scope, and special requirements for guest visitors.





#### 2. Backend API & Processing Layer

* **Quotation Request Service (`/api/quotation-requests`):**
* Validates request payloads.
* Generates unique sequence identifier (`REQ-YYYY-XXXXX`).
* Maps guest details to existing customer accounts if email matches.


* **Notification & Routing Engine:**
* Auto-assigns leads based on sales territory or rep availability.
* Fires confirmation triggers to customer and alert triggers to assigned sales staff.





#### 3. Sales Portal & CPQ Module

* **Lead Workspace:** Table and detailed activity view to track interactions, call logs, notes, and request statuses.
* **Quotation Builder (`/api/quotations`):**
* Snapshots product rates at the time of offer creation.
* Enforces discount ceiling thresholds based on sales rep permission levels.
* Triggers manager approval workflows if discount thresholds are breached.
* PDF Generator service to generate frozen proposal documents (`QT-YYYY-XXXXX`).





#### 4. Offer Review & Acceptance Module

* **Customer Presentation Link:** Secure, tokenized portal for customer review.
* **Handshake Engine:**
* **Accept:** Captures audit details (IP, user agent, timestamp, signature hash), locks the quotation version, and triggers subscription conversion.
* **Modify:** Changes request state back to `Revision Requested` in Sales Workspace.
* **Reject:** Closes lead as lost and logs rejection reasons.




#### 5. Core Subscription & Billing Layer

* **Subscription Provisioner (`/api/subscriptions/from-quotation/:id`):**
* Atomically converts accepted quotes into active customer subscriptions.
* Initializes recurring payment schedules based on agreed billing cycles (Monthly/Yearly).
* Triggers downstream account provisioning and Finance alerts.


