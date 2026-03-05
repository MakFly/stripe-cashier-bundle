import type { Metadata } from 'next'
import { Layout, Navbar } from 'nextra-theme-docs'
import { Head } from 'nextra/components'
import { getPageMap } from 'nextra/page-map'
import 'nextra-theme-docs/style.css'
import './globals.css'

export const metadata: Metadata = {
  title: {
    template: '%s – CashierBundle',
    default: 'CashierBundle - Stripe Cashier pour Symfony'
  },
  description: 'Documentation CashierBundle - Intégration Stripe Cashier pour Symfony 8.X',
  icons: {
    icon: '/favicon.ico',
  },
}

const navbar = (
  <Navbar
    logo={
      <span style={{
        display: 'flex',
        alignItems: 'center',
        gap: '0.5rem',
        fontWeight: 700,
        fontSize: '1.125rem',
        background: 'linear-gradient(135deg, #fff 0%, #94a3b8 100%)',
        WebkitBackgroundClip: 'text',
        WebkitTextFillColor: 'transparent',
        fontFamily: 'Plus Jakarta Sans, system-ui, sans-serif'
      }}>
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect width="28" height="28" rx="8" fill="url(#logo-gradient)"/>
          <path d="M8 14L12 18L20 10" stroke="white" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
          <defs>
            <linearGradient id="logo-gradient" x1="0" y1="0" x2="28" y2="28">
              <stop stopColor="#635bff"/>
              <stop offset="0.5" stopColor="#7c3aed"/>
              <stop offset="1" stopColor="#ec4899"/>
            </linearGradient>
          </defs>
        </svg>
        CashierBundle
      </span>
    }
    projectLink="https://github.com/MakFly/cashier-symfony"
    chatLink="https://github.com/MakFly/cashier-symfony/discussions"
  />
)

export default async function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="fr" className="dark" suppressHydrationWarning>
      <Head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
      </Head>
      <body style={{ background: '#0a0a0f' }}>
        <Layout
          navbar={navbar}
          pageMap={await getPageMap()}
          docsRepositoryBase="https://github.com/MakFly/cashier-symfony/tree/main/docs-nextra"
          sidebar={{
            defaultMenuCollapseLevel: 1,
            toggleButton: true,
          }}
          toc={{
            backToTop: true,
          }}
          editLink="Éditer cette page sur GitHub →"
          feedback={{
            content: 'Question ? Donnez-nous votre feedback →',
            labels: 'feedback'
          }}
        >
          {children}
        </Layout>
      </body>
    </html>
  )
}
