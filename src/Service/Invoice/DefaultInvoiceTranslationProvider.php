<?php

declare(strict_types=1);

namespace CashierBundle\Service\Invoice;

use CashierBundle\Contract\InvoiceTranslationProviderInterface;

final class DefaultInvoiceTranslationProvider implements InvoiceTranslationProviderInterface
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const TRANSLATIONS = [
        'en' => [
            'title' => 'Invoice',
            'from' => 'From',
            'bill_to' => 'Bill to',
            'invoice_details' => 'Invoice details',
            'date_issued' => 'Date issued',
            'due_date' => 'Due date',
            'currency' => 'Currency',
            'reference' => 'Reference',
            'description' => 'Description',
            'quantity' => 'Qty',
            'unit_price' => 'Unit price',
            'amount' => 'Amount',
            'subtotal' => 'Subtotal',
            'tax' => 'Tax',
            'discount' => 'Discount',
            'total' => 'Total',
            'amount_due' => 'Amount due',
            'amount_paid' => 'Amount paid',
            'payment_reference' => 'Payment reference',
            'customer_unavailable' => 'Customer information unavailable',
            'thank_you' => 'Thank you for your business.',
            'support' => 'Support',
            'statuses' => [
                'paid' => 'Paid',
                'open' => 'Open',
                'pending' => 'Pending',
                'draft' => 'Draft',
                'void' => 'Void',
                'uncollectible' => 'Uncollectible',
            ],
        ],
        'fr' => [
            'title' => 'Facture',
            'from' => 'Émetteur',
            'bill_to' => 'Facturé à',
            'invoice_details' => 'Détails de la facture',
            'date_issued' => "Date d'émission",
            'due_date' => "Date d'échéance",
            'currency' => 'Devise',
            'reference' => 'Référence',
            'description' => 'Description',
            'quantity' => 'Qté',
            'unit_price' => 'Prix unitaire',
            'amount' => 'Montant',
            'subtotal' => 'Sous-total',
            'tax' => 'TVA',
            'discount' => 'Remise',
            'total' => 'Total',
            'amount_due' => 'Montant restant',
            'amount_paid' => 'Montant payé',
            'payment_reference' => 'Référence de paiement',
            'customer_unavailable' => 'Informations client indisponibles',
            'thank_you' => 'Merci pour votre confiance.',
            'support' => 'Support',
            'statuses' => [
                'paid' => 'Payée',
                'open' => 'Ouverte',
                'pending' => 'En attente',
                'draft' => 'Brouillon',
                'void' => 'Annulée',
                'uncollectible' => 'Irrécouvrable',
            ],
        ],
    ];

    public function getTranslations(string $locale): array
    {
        return self::TRANSLATIONS[$locale] ?? self::TRANSLATIONS['en'];
    }
}
