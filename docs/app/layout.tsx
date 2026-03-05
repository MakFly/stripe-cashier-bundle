import type { Metadata } from 'next'
import { Layout, Navbar } from 'nextra-theme-docs'
import { Head } from 'nextra/components'
import { getPageMap } from 'nextra/page-map'
import 'nextra-theme-docs/style.css'

export const metadata: Metadata = {
  title: {
    template: '%s – CashierBundle',
    default: 'CashierBundle - Stripe Cashier pour Symfony'
  },
  description: 'Documentation CashierBundle - Intégration Stripe Cashier pour Symfony 8.X',
}

const navbar = (
  <Navbar
    logo={<b>CashierBundle</b>}
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
      </Head>
      <body>
        <Layout
          navbar={navbar}
          pageMap={await getPageMap()}
          docsRepositoryBase="https://github.com/MakFly/cashier-symfony/tree/main/docs-nextra"
        >
          {children}
        </Layout>
      </body>
    </html>
  )
}
