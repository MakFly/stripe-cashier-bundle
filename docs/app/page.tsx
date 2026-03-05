import { Instrument_Serif, DM_Sans } from "next/font/google";
import Link from "next/link";

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

const features = [
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
];

const code = `<span class="c">// Souscrire un utilisateur</span>
<span class="v">$user</span><span class="p">->newSubscription(</span>
<span class="s">    'default'</span><span class="p">,</span> <span class="s">'price_monthly'</span>
<span class="p">)->create(</span><span class="v">$paymentMethod</span><span class="p">);</span>

<span class="c">// Vérifier le statut</span>
<span class="v">$user</span><span class="p">->subscribed(</span><span class="s">'default'</span><span class="p">);</span> <span class="c">// true</span>

<span class="c">// Annuler en fin de période</span>
<span class="v">$user</span><span class="p">->subscription(</span><span class="s">'default'</span><span class="p">)</span>
     <span class="p">->cancel();</span>`;

export default function HomePage() {
  return (
    <main
      className={`${display.variable} ${body.variable}`}
      style={{
        background: "#09090b",
        minHeight: "100vh",
        color: "#f4f0eb",
        fontFamily: "var(--font-body), sans-serif",
        overflowX: "hidden",
      }}
    >
      <style>{`
        :root { color-scheme: dark; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        .c { color: #4b5563; }
        .v { color: #a78bfa; }
        .s { color: #10b981; }
        .p { color: #f4f0eb; }
        @media (max-width: 768px) {
          .hero-grid { grid-template-columns: 1fr !important; gap: 3rem !important; }
          .feature-grid { grid-template-columns: 1fr !important; }
          .hero-headline { font-size: clamp(2.5rem, 10vw, 4rem) !important; }
        }
      `}</style>

      {/* — NAV — */}
      <nav
        style={{
          position: "sticky",
          top: 0,
          zIndex: 50,
          borderBottom: "1px solid rgba(244,240,235,0.07)",
          backdropFilter: "blur(16px)",
          background: "rgba(9,9,11,0.85)",
          height: "58px",
        }}
      >
        <div
          style={{
            maxWidth: "1140px",
            margin: "0 auto",
            padding: "0 clamp(1.5rem, 5vw, 3rem)",
            display: "flex",
            alignItems: "center",
            justifyContent: "space-between",
            height: "100%",
          }}
        >
          <span
            style={{
              fontFamily: "var(--font-display)",
              fontSize: "1.05rem",
              letterSpacing: "-0.01em",
            }}
          >
            Cashier Symfony
          </span>
          <div
            style={{ display: "flex", gap: "1.75rem", alignItems: "center" }}
          >
            <Link
              href="/docs"
              style={{
                color: "rgba(244,240,235,0.5)",
                fontSize: "0.875rem",
                textDecoration: "none",
              }}
            >
              Docs
            </Link>
            <a
              href="https://github.com/MakFly/stripe-cashier-bundle"
              target="_blank"
              rel="noreferrer"
              style={{
                color: "rgba(244,240,235,0.5)",
                fontSize: "0.875rem",
                textDecoration: "none",
              }}
            >
              GitHub
            </a>
          </div>
        </div>
      </nav>

      {/* — HERO — */}
      <section
        style={{
          padding:
            "clamp(5rem, 14vw, 11rem) clamp(1.5rem, 5vw, 3rem) clamp(4rem, 8vw, 6rem)",
          maxWidth: "1140px",
          margin: "0 auto",
        }}
      >
        {/* badge */}
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

        {/* headline */}
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
          Billing Stripe
          <br />
          <em style={{ fontStyle: "italic", color: "#10b981" }}>
            sans friction.
          </em>
        </h1>

        {/* sub */}
        <p
          style={{
            fontSize: "clamp(1rem, 1.8vw, 1.2rem)",
            color: "rgba(244,240,235,0.5)",
            maxWidth: "520px",
            lineHeight: 1.7,
            marginBottom: "2.75rem",
          }}
        >
          Un bundle Symfony pour Stripe Billing. Checkout, abonnements, webhooks et archivage PDF des factures, avec Doctrine et Symfony.
        </p>

        {/* CTAs */}
        <div style={{ display: "flex", gap: "0.875rem", flexWrap: "wrap" }}>
          <Link
            href="/docs"
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
            Démarrer →
          </Link>
          <a
            href="https://github.com/MakFly/stripe-cashier-bundle"
            target="_blank"
            rel="noreferrer"
            style={{
              border: "1px solid rgba(244,240,235,0.12)",
              color: "rgba(244,240,235,0.65)",
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
            background: "rgba(244,240,235,0.03)",
            border: "1px solid rgba(244,240,235,0.08)",
            borderRadius: "10px",
            padding: "1rem 1.5rem",
            fontFamily: "var(--font-geist-mono, 'Courier New', monospace)",
            fontSize: "0.875rem",
          }}
        >
          <span style={{ color: "#10b981", userSelect: "none" }}>$</span>
          <span>composer require kev/cashier-bundle</span>
        </div>
      </section>

      {/* — FEATURES — */}
      <section
        style={{
          padding: "clamp(3rem, 6vw, 5rem) clamp(1.5rem, 5vw, 3rem)",
          maxWidth: "1140px",
          margin: "0 auto",
          borderTop: "1px solid rgba(244,240,235,0.06)",
        }}
      >
        <p
          style={{
            fontSize: "0.7rem",
            letterSpacing: "0.14em",
            textTransform: "uppercase",
            color: "rgba(244,240,235,0.25)",
            marginBottom: "3rem",
            fontWeight: 600,
          }}
        >
          Ce qui est inclus
        </p>

        <div
          className="feature-grid"
          style={{
            display: "grid",
            gridTemplateColumns: "repeat(3, 1fr)",
            border: "1px solid rgba(244,240,235,0.07)",
            borderRadius: "14px",
            overflow: "hidden",
          }}
        >
          {features.map((f, i) => (
            <div
              key={f.num}
              style={{
                padding: "2.5rem",
                borderRight:
                  i < features.length - 1
                    ? "1px solid rgba(244,240,235,0.07)"
                    : "none",
                background: "#09090b",
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
                  color: "rgba(244,240,235,0.45)",
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
          borderTop: "1px solid rgba(244,240,235,0.06)",
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
          {/* left */}
          <div>
            <p
              style={{
                fontSize: "0.7rem",
                letterSpacing: "0.14em",
                textTransform: "uppercase",
                color: "rgba(244,240,235,0.25)",
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
              Une interface
              <br />
              <em style={{ fontStyle: "italic", color: "#10b981" }}>
                expressive.
              </em>
            </h2>
            <p
              style={{
                color: "rgba(244,240,235,0.45)",
                fontSize: "0.9rem",
                lineHeight: 1.75,
                marginBottom: "2rem",
              }}
            >
              Conçue pour être lisible et intuitive. Tout ce dont vous avez
              besoin, rien de superflu.
            </p>
            <Link
              href="/docs/installation"
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
              Voir la doc d&apos;installation →
            </Link>
          </div>

          {/* code block */}
          <div
            style={{
              background: "#111116",
              border: "1px solid rgba(244,240,235,0.08)",
              borderRadius: "14px",
              overflow: "hidden",
            }}
          >
            <div
              style={{
                borderBottom: "1px solid rgba(244,240,235,0.06)",
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
                  color: "rgba(244,240,235,0.25)",
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
              <code dangerouslySetInnerHTML={{ __html: code }} />
            </pre>
          </div>
        </div>
      </section>

      {/* — FOOTER — */}
      <footer
        style={{
          borderTop: "1px solid rgba(244,240,235,0.06)",
          padding: "2rem clamp(1.5rem, 5vw, 3rem)",
          maxWidth: "1140px",
          margin: "0 auto",
          display: "flex",
          justifyContent: "space-between",
          alignItems: "center",
          color: "rgba(244,240,235,0.25)",
          fontSize: "0.8rem",
        }}
      >
        <span style={{ fontFamily: "var(--font-display)" }}>
          Cashier Symfony
        </span>
        <span>MIT 2025</span>
      </footer>
    </main>
  );
}
