import type { Metadata } from "next";
import { Instrument_Serif, DM_Sans } from "next/font/google";
import Link from "next/link";
import type { FC } from "react";
import { DocsControls } from "../../components/docs-controls";

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://stripe-cashier-bundle.vercel.app';

const metaDict = {
  fr: {
    title: 'Cashier Symfony – Billing Stripe pour Symfony',
    description: 'Cashier Symfony est un bundle Symfony offrant une interface expressive et fluide pour Stripe Billing. Gérez abonnements, paiements, factures, sessions checkout et webhooks avec Doctrine.',
    locale: 'fr',
  },
  en: {
    title: 'Cashier Symfony – Stripe Billing for Symfony',
    description: 'Cashier Symfony is a Symfony bundle providing an expressive, fluent interface to Stripe Billing. Manage subscriptions, payments, invoices, checkout sessions and webhooks with Doctrine.',
    locale: 'en',
  },
} as const;

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { lang } = await params;
  const m = metaDict[lang as keyof typeof metaDict] ?? metaDict.en;
  const otherLang = lang === 'fr' ? 'en' : 'fr';
  return {
    title: m.title,
    description: m.description,
    alternates: {
      canonical: `${SITE_URL}/${lang}`,
      languages: {
        fr: `${SITE_URL}/fr`,
        en: `${SITE_URL}/en`,
      },
    },
    openGraph: {
      locale: m.locale,
      alternateLocale: otherLang,
    },
  };
}

const display = Instrument_Serif({
  subsets: ["latin"],
  weight: "400",
  style: ["normal", "italic"],
  variable: "--font-display",
});

const body = DM_Sans({
  subsets: ["latin"],
  weight: ["400", "500", "600"],
  variable: "--font-body",
});

const dict = {
  fr: {
    features: [
      {
        num: "01",
        title: "Abonnements récurrents",
        desc: "Créez et gérez des abonnements Stripe avec une API élégante. Trials, upgrades, downgrades et annulations.",
      },
      {
        num: "02",
        title: "Facturation automatique",
        desc: "Génération de factures, gestion des méthodes de paiement et portail client Stripe intégré.",
      },
      {
        num: "03",
        title: "Webhooks sécurisés",
        desc: "Vérification de signature Stripe et dispatch d'events Symfony automatiques.",
      },
      {
        num: "04",
        title: "Paiements & Checkout",
        desc: "Encaissez des paiements uniques, créez des Payment Intents ou redirigez vers Stripe Checkout. Remboursements et portail client en une ligne.",
      },
      {
        num: "05",
        title: "Gestion des clients",
        desc: "Synchronisez vos utilisateurs Doctrine avec Stripe. Création à la volée, mise à jour et récupération du profil client.",
      },
      {
        num: "06",
        title: "Méthodes de paiement",
        desc: "Ajoutez, listez et définissez la méthode de paiement par défaut. Support multi-types via l'API Stripe.",
      },
    ],
    code: `<span class="hp-c">// Souscrire un utilisateur</span>
<span class="hp-v">$user</span><span class="hp-p">->newSubscription(</span>
<span class="hp-s">    'default'</span><span class="hp-p">,</span> <span class="hp-s">'price_monthly'</span>
<span class="hp-p">)->create(</span><span class="hp-v">$paymentMethod</span><span class="hp-p">);</span>

<span class="hp-c">// Vérifier le statut</span>
<span class="hp-v">$user</span><span class="hp-p">->subscribed(</span><span class="hp-s">'default'</span><span class="hp-p">);</span> <span class="hp-c">// true</span>

<span class="hp-c">// Annuler en fin de période</span>
<span class="hp-v">$user</span><span class="hp-p">->subscription(</span><span class="hp-s">'default'</span><span class="hp-p">)</span>
     <span class="hp-p">->cancel();</span>`,
    heroLine1: "Billing Stripe",
    heroLine2: "sans friction.",
    heroSub:
      "Un bundle Symfony pour Stripe Billing. Checkout, abonnements, Payment Intents, webhooks et archivage PDF des factures, avec Doctrine et Symfony.",
    cta: "Démarrer →",
    included: "Ce qui est inclus",
    apiTitle1: "Une interface",
    apiTitle2: "expressive.",
    apiDesc:
      "Conçue pour être lisible et intuitive. Tout ce dont vous avez besoin, rien de superflu.",
    installLink: "Voir la doc d\u2019installation →",
  },
  en: {
    features: [
      {
        num: "01",
        title: "Recurring subscriptions",
        desc: "Create and manage Stripe subscriptions with an elegant API. Trials, upgrades, downgrades and cancellations.",
      },
      {
        num: "02",
        title: "Automatic billing",
        desc: "Invoice generation, payment method management and integrated Stripe customer portal.",
      },
      {
        num: "03",
        title: "Secure webhooks",
        desc: "Stripe signature verification and automatic Symfony event dispatch.",
      },
      {
        num: "04",
        title: "Payments & Checkout",
        desc: "Collect one-time payments, create Payment Intents or redirect to Stripe Checkout. Refunds and customer portal in a single line.",
      },
      {
        num: "05",
        title: "Customer management",
        desc: "Sync your Doctrine users with Stripe. On-the-fly creation, updates and customer profile retrieval.",
      },
      {
        num: "06",
        title: "Payment methods",
        desc: "Add, list and set the default payment method. Multi-type support through the Stripe API.",
      },
    ],
    code: `<span class="hp-c">// Subscribe a user</span>
<span class="hp-v">$user</span><span class="hp-p">->newSubscription(</span>
<span class="hp-s">    'default'</span><span class="hp-p">,</span> <span class="hp-s">'price_monthly'</span>
<span class="hp-p">)->create(</span><span class="hp-v">$paymentMethod</span><span class="hp-p">);</span>

<span class="hp-c">// Check status</span>
<span class="hp-v">$user</span><span class="hp-p">->subscribed(</span><span class="hp-s">'default'</span><span class="hp-p">);</span> <span class="hp-c">// true</span>

<span class="hp-c">// Cancel at end of period</span>
<span class="hp-v">$user</span><span class="hp-p">->subscription(</span><span class="hp-s">'default'</span><span class="hp-p">)</span>
     <span class="hp-p">->cancel();</span>`,
    heroLine1: "Stripe Billing",
    heroLine2: "without friction.",
    heroSub:
      "A Symfony bundle for Stripe Billing. Checkout, subscriptions, Payment Intents, webhooks and PDF archiving of invoices, with Doctrine and Symfony.",
    cta: "Get started →",
    included: "What's included",
    apiTitle1: "An expressive",
    apiTitle2: "interface.",
    apiDesc:
      "Designed to be readable and intuitive. Everything you need, nothing more.",
    installLink: "See installation docs →",
  },
} as const;

type PageProps = Readonly<{
  params: Promise<{ lang: string }>;
}>;

const HomePage: FC<PageProps> = async ({ params }) => {
  const { lang } = await params;
  const t = dict[lang as keyof typeof dict] ?? dict.fr;

  return (
    <main
      data-homepage
      className={`${display.variable} ${body.variable}`}
      style={{
        background: "var(--hp-bg)",
        minHeight: "100vh",
        color: "var(--hp-text)",
        fontFamily: "var(--font-body), sans-serif",
        overflowX: "hidden",
      }}
    >
      {/* Floating controls — locale + theme */}
      <div
        style={{
          position: "fixed",
          top: "1.25rem",
          right: "1.5rem",
          zIndex: 50,
        }}
      >
        <DocsControls />
      </div>

      <style>{`
        .hp-c { color: var(--hp-code-comment); }
        .hp-v { color: var(--hp-code-var); }
        .hp-s { color: var(--hp-code-str); }
        .hp-p { color: var(--hp-code-punct); }
        @media (max-width: 768px) {
          .hero-grid { grid-template-columns: 1fr !important; gap: 3rem !important; }
          .feature-grid { grid-template-columns: 1fr !important; }
          .hero-headline { font-size: clamp(2.5rem, 10vw, 4rem) !important; }
        }
      `}</style>

      {/* — HERO — */}
      <section
        style={{
          padding:
            "clamp(5rem, 14vw, 11rem) clamp(1.5rem, 5vw, 3rem) clamp(4rem, 8vw, 6rem)",
          maxWidth: "1140px",
          margin: "0 auto",
        }}
      >
        <div
          style={{
            display: "inline-flex",
            alignItems: "center",
            gap: "0.5rem",
            background: "rgba(16,185,129,0.08)",
            border: "1px solid rgba(16,185,129,0.2)",
            borderRadius: "100px",
            padding: "0.3rem 0.875rem",
            marginBottom: "2.75rem",
            fontSize: "0.75rem",
            color: "#10b981",
            letterSpacing: "0.06em",
            textTransform: "uppercase",
            fontWeight: 600,
          }}
        >
          <span
            style={{
              width: "5px",
              height: "5px",
              borderRadius: "50%",
              background: "#10b981",
              display: "inline-block",
            }}
          />
          Symfony 7.x · 8.x · Stripe
        </div>

        <h1
          className="hero-headline"
          style={{
            fontFamily: "var(--font-display)",
            fontSize: "clamp(3.5rem, 7.5vw, 6.5rem)",
            lineHeight: 1.04,
            letterSpacing: "-0.035em",
            marginBottom: "1.75rem",
            fontWeight: 400,
          }}
        >
          {t.heroLine1}
          <br />
          <em style={{ fontStyle: "italic", color: "#10b981" }}>
            {t.heroLine2}
          </em>
        </h1>

        <p
          style={{
            fontSize: "clamp(1rem, 1.8vw, 1.2rem)",
            color: "var(--hp-text-muted)",
            maxWidth: "520px",
            lineHeight: 1.7,
            marginBottom: "2.75rem",
          }}
        >
          {t.heroSub}
        </p>

        <div style={{ display: "flex", gap: "0.875rem", flexWrap: "wrap" }}>
          <Link
            href={`/${lang}/docs`}
            style={{
              background: "#10b981",
              color: "#09090b",
              padding: "0.8rem 1.75rem",
              borderRadius: "8px",
              fontWeight: 600,
              fontSize: "0.9rem",
              textDecoration: "none",
              display: "inline-flex",
              alignItems: "center",
              gap: "0.4rem",
              transition: "opacity 0.15s",
            }}
          >
            {t.cta}
          </Link>
          <a
            href="https://github.com/MakFly/stripe-cashier-bundle"
            target="_blank"
            rel="noreferrer"
            style={{
              border: "1px solid var(--hp-btn-secondary-border)",
              color: "var(--hp-btn-secondary-text)",
              padding: "0.8rem 1.75rem",
              borderRadius: "8px",
              fontWeight: 500,
              fontSize: "0.9rem",
              textDecoration: "none",
              display: "inline-flex",
              alignItems: "center",
              gap: "0.4rem",
            }}
          >
            GitHub
          </a>
        </div>
      </section>

      {/* — INSTALL STRIP — */}
      <section
        style={{
          padding: "0 clamp(1.5rem, 5vw, 3rem) 5rem",
          maxWidth: "1140px",
          margin: "0 auto",
        }}
      >
        <div
          style={{
            display: "inline-flex",
            alignItems: "center",
            gap: "1rem",
            background: "var(--hp-bg-code)",
            border: "1px solid var(--hp-border)",
            borderRadius: "10px",
            padding: "1rem 1.5rem",
            fontFamily: "var(--font-geist-mono, 'Courier New', monospace)",
            fontSize: "0.875rem",
          }}
        >
          <span style={{ color: "#10b981", userSelect: "none" }}>$</span>
          <span>composer require makfly/stripe-cashier-bundle</span>
        </div>
      </section>

      {/* — FEATURES — */}
      <section
        style={{
          padding: "clamp(3rem, 6vw, 5rem) clamp(1.5rem, 5vw, 3rem)",
          maxWidth: "1140px",
          margin: "0 auto",
          borderTop: "1px solid var(--hp-border-faint)",
        }}
      >
        <p
          style={{
            fontSize: "0.7rem",
            letterSpacing: "0.14em",
            textTransform: "uppercase",
            color: "var(--hp-text-faint)",
            marginBottom: "3rem",
            fontWeight: 600,
          }}
        >
          {t.included}
        </p>

        <div
          className="feature-grid"
          style={{
            display: "grid",
            gridTemplateColumns: "repeat(3, 1fr)",
            border: "1px solid var(--hp-border)",
            borderRadius: "14px",
            overflow: "hidden",
          }}
        >
          {t.features.map((f, i) => (
            <div
              key={f.num}
              style={{
                padding: "2.5rem",
                borderRight:
                  (i + 1) % 3 !== 0 ? "1px solid var(--hp-border)" : "none",
                borderBottom: i < 3 ? "1px solid var(--hp-border)" : "none",
                background: "var(--hp-bg)",
              }}
            >
              <div
                style={{
                  fontFamily: "var(--font-geist-mono, monospace)",
                  fontSize: "0.68rem",
                  color: "#10b981",
                  letterSpacing: "0.1em",
                  marginBottom: "1.5rem",
                  opacity: 0.75,
                }}
              >
                {f.num}
              </div>
              <h3
                style={{
                  fontFamily: "var(--font-display)",
                  fontSize: "1.3rem",
                  fontWeight: 400,
                  marginBottom: "0.875rem",
                  letterSpacing: "-0.01em",
                  lineHeight: 1.2,
                }}
              >
                {f.title}
              </h3>
              <p
                style={{
                  color: "var(--hp-text-muted)",
                  fontSize: "0.875rem",
                  lineHeight: 1.7,
                }}
              >
                {f.desc}
              </p>
            </div>
          ))}
        </div>
      </section>

      {/* — CODE + CTA — */}
      <section
        style={{
          padding:
            "clamp(3rem, 6vw, 5rem) clamp(1.5rem, 5vw, 3rem) clamp(5rem, 10vw, 9rem)",
          maxWidth: "1140px",
          margin: "0 auto",
          borderTop: "1px solid var(--hp-border-faint)",
        }}
      >
        <div
          className="hero-grid"
          style={{
            display: "grid",
            gridTemplateColumns: "1fr 1.6fr",
            gap: "5rem",
            alignItems: "center",
          }}
        >
          <div>
            <p
              style={{
                fontSize: "0.7rem",
                letterSpacing: "0.14em",
                textTransform: "uppercase",
                color: "var(--hp-text-faint)",
                marginBottom: "1.25rem",
                fontWeight: 600,
              }}
            >
              API
            </p>
            <h2
              style={{
                fontFamily: "var(--font-display)",
                fontSize: "clamp(2rem, 3.5vw, 3rem)",
                fontWeight: 400,
                lineHeight: 1.1,
                letterSpacing: "-0.025em",
                marginBottom: "1.25rem",
              }}
            >
              {t.apiTitle1}
              <br />
              <em style={{ fontStyle: "italic", color: "#10b981" }}>
                {t.apiTitle2}
              </em>
            </h2>
            <p
              style={{
                color: "var(--hp-text-muted)",
                fontSize: "0.9rem",
                lineHeight: 1.75,
                marginBottom: "2rem",
              }}
            >
              {t.apiDesc}
            </p>
            <Link
              href={`/${lang}/docs/installation`}
              style={{
                color: "#10b981",
                fontSize: "0.875rem",
                textDecoration: "none",
                display: "inline-flex",
                alignItems: "center",
                gap: "0.35rem",
                fontWeight: 500,
              }}
            >
              {t.installLink}
            </Link>
          </div>

          <div
            style={{
              background: "var(--hp-bg-card)",
              border: "1px solid var(--hp-border)",
              borderRadius: "14px",
              overflow: "hidden",
            }}
          >
            <div
              style={{
                borderBottom: "1px solid var(--hp-border-faint)",
                padding: "0.875rem 1.375rem",
                display: "flex",
                gap: "7px",
                alignItems: "center",
              }}
            >
              {["#ef4444", "#f59e0b", "#10b981"].map((c) => (
                <span
                  key={c}
                  style={{
                    width: "10px",
                    height: "10px",
                    borderRadius: "50%",
                    background: c,
                    opacity: 0.7,
                  }}
                />
              ))}
              <span
                style={{
                  marginLeft: "auto",
                  fontFamily: "var(--font-geist-mono, monospace)",
                  fontSize: "0.7rem",
                  color: "var(--hp-text-faint)",
                }}
              >
                UserController.php
              </span>
            </div>
            <pre
              style={{
                padding: "1.75rem",
                fontFamily: "var(--font-geist-mono, 'Courier New', monospace)",
                fontSize: "0.82rem",
                lineHeight: 1.85,
                overflowX: "auto",
                margin: 0,
              }}
            >
              <code dangerouslySetInnerHTML={{ __html: t.code }} />
            </pre>
          </div>
        </div>
      </section>
    </main>
  );
};

export default HomePage;
