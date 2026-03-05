cashier:
    key: '%env(STRIPE_KEY)%'
    secret: '%env(STRIPE_SECRET)%'
    path: cashier
    webhook:
        secret: '%env(STRIPE_WEBHOOK_SECRET)%'
        tolerance: 300
        events:
            - customer.subscription.created
            - customer.subscription.updated
            - customer.subscription.deleted
            - customer.updated
            - customer.deleted
            - payment_method.automatically_updated
            - invoice.payment_action_required
            - invoice.paid
            - invoice.payment_failed
            - checkout.session.completed
    currency: usd
    currency_locale: en
    default_subscription_type: default
    invoices:
        renderer: CashierBundle\Service\InvoiceRenderer\DompdfInvoiceRenderer
