'use client'

import Link from 'next/link'
import {
  CreditCard,
  Clock,
  Zap,
  FileText,
  Tag,
  Webhook,
  ArrowRight,
  Github,
  Terminal,
  Sparkles
} from 'lucide-react'

const features = [
  {
    icon: CreditCard,
    title: 'Abonnements récurrents',
    description: 'Gérez facilement les abonnements mensuels et annuels avec Stripe. Support complet des cycles de facturation.',
    gradient: 'from-violet-500 to-purple-500'
  },
  {
    icon: Clock,
    title: 'Périodes d\'essai',
    description: 'Offrez des périodes d\'essai flexibles à vos nouveaux utilisateurs pour augmenter les conversions.',
    gradient: 'from-blue-500 to-cyan-500'
  },
  {
    icon: Zap,
    title: 'Paiements ponctuels',
    description: 'Gérez les paiements uniques et les factures avec une API élégante et intuitive.',
    gradient: 'from-amber-500 to-orange-500'
  },
  {
    icon: FileText,
    title: 'Factures PDF',
    description: 'Génération automatique des factures professionnelles au format PDF pour vos clients.',
    gradient: 'from-emerald-500 to-teal-500'
  },
  {
    icon: Tag,
    title: 'Coupons & promotions',
    description: 'Créez et gérez des codes promo, réductions et offres spéciales pour vos utilisateurs.',
    gradient: 'from-pink-500 to-rose-500'
  },
  {
    icon: Webhook,
    title: 'Webhooks intégrés',
    description: 'Gestion transparente et sécurisée des événements Stripe en temps réel.',
    gradient: 'from-indigo-500 to-violet-500'
  }
]

export default function HomePage() {
  return (
    <div className="cb-page-wrapper">
      {/* Hero Section */}
      <section className="cb-hero">
        <div className="cb-hero-content">
          <div className="cb-hero-badge cb-animate-in">
            <span className="cb-hero-badge-dot" />
            Symfony 8.X Ready
          </div>

          <h1 className="cb-hero-title cb-animate-in cb-animate-delay-1">
            <span className="cb-hero-title-accent">CashierBundle</span>
            <br />
            Stripe Cashier pour Symfony
          </h1>

          <p className="cb-hero-description cb-animate-in cb-animate-delay-2">
            Une intégration élégante et puissante de Stripe Cashier pour Symfony.
            Gérez vos abonnements, paiements et webhooks avec une API expressive.
          </p>

          <div className="cb-hero-actions cb-animate-in cb-animate-delay-3">
            <Link href="/docs/installation" className="cb-btn cb-btn-primary">
              <Sparkles className="cb-btn-icon" />
              Commencer maintenant
            </Link>
            <a
              href="https://github.com/MakFly/cashier-symfony"
              className="cb-btn cb-btn-secondary"
              target="_blank"
              rel="noopener noreferrer"
            >
              <Github className="cb-btn-icon" />
              Voir sur GitHub
            </a>
          </div>
        </div>
      </section>

      {/* Quick Install */}
      <section className="cb-code-section cb-animate-in cb-animate-delay-4">
        <div className="cb-code-header">
          <span className="cb-code-header-dot" />
          <span className="cb-code-header-dot" />
          <span className="cb-code-header-dot" />
          <span style={{ marginLeft: 'auto' }}>terminal</span>
        </div>
        <div className="cb-install">
          <Terminal className="cb-btn-icon" style={{ color: '#94a3b8' }} />
          <code className="cb-install-code">composer require cashier/cashier-bundle</code>
          <button className="cb-install-copy" onClick={() => {
            navigator.clipboard.writeText('composer require cashier/cashier-bundle')
          }}>
            Copier
          </button>
        </div>
      </section>

      {/* Features Grid */}
      <section>
        <h2 className="cb-section-title">Fonctionnalités</h2>
        <div className="cb-features">
          {features.map((feature, index) => (
            <div
              key={feature.title}
              className={`cb-feature-card cb-animate-in cb-animate-delay-${index + 1}`}
            >
              <div className={`cb-feature-icon bg-gradient-to-br ${feature.gradient}`}>
                <feature.icon />
              </div>
              <h3 className="cb-feature-title">{feature.title}</h3>
              <p className="cb-feature-description">{feature.description}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Code Example */}
      <section className="cb-code-section">
        <div className="cb-code-header">
          <span className="cb-code-header-dot" />
          <span className="cb-code-header-dot" />
          <span className="cb-code-header-dot" />
          <span style={{ marginLeft: 'auto' }}>example.php</span>
        </div>
        <pre style={{
          fontFamily: 'JetBrains Mono, monospace',
          fontSize: '0.9rem',
          lineHeight: 1.7,
          color: '#e2e8f0',
          margin: 0,
          padding: '1rem 0'
        }}>
          <code>{`// Créer un abonnement avec période d'essai
$subscription = $user->newSubscription('default', 'price_premium')
    ->trialDays(14)
    ->create($paymentMethodId);

// Vérifier si l'utilisateur est abonné
if ($user->subscribed('default')) {
    // Accès au contenu premium
}

// Gérer les webhooks automatiquement
#[AsEventListener(event: StripeWebhookEvent::INVOICE_PAID)]
public function onInvoicePaid(StripeWebhookEvent $event): void
{
    $invoice = $event->getObject();
    // Logique personnalisée
}`}</code>
        </pre>
      </section>

      {/* CTA Section */}
      <section className="cb-cta cb-animate-in">
        <div className="cb-cta-content">
          <div className="cb-cta-text">
            <h3>Prêt à commencer ?</h3>
            <p>Plongez dans la documentation et intégrez CashierBundle en quelques minutes.</p>
          </div>
          <Link href="/docs/installation" className="cb-btn cb-btn-primary">
            Voir la documentation
            <ArrowRight className="cb-btn-icon" />
          </Link>
        </div>
      </section>

      <style jsx global>{`
        .cb-page-wrapper {
          max-width: 1100px;
          margin: 0 auto;
          padding: 0 var(--cb-space-xl);
        }

        @media (max-width: 768px) {
          .cb-page-wrapper {
            padding: 0 var(--cb-space-md);
          }
        }
      `}</style>
    </div>
  )
}
