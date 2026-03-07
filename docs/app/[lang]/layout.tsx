import type { Metadata } from "next";
import { notFound } from "next/navigation";
import type { FC, ReactNode } from "react";
import { GeistSans } from "geist/font/sans";
import { GeistMono } from "geist/font/mono";
import { Layout } from "nextra-theme-docs";
import { getPageMap } from "nextra/page-map";
import { CustomNavbar } from "../../components/custom-navbar";
import "nextra-theme-docs/style.css";
import "../globals.css";

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://stripe-cashier-bundle.vercel.app';

export const metadata: Metadata = {
  metadataBase: new URL(SITE_URL),
  title: {
    template: '%s | Cashier Symfony',
    default: 'Cashier Symfony – Stripe Billing for Symfony',
  },
  description: 'Cashier Symfony is a Symfony bundle providing an expressive, fluent interface to Stripe Billing. Manage subscriptions, payments, invoices, checkout sessions and webhooks with Doctrine.',
  keywords: ['Symfony', 'Stripe', 'billing', 'subscriptions', 'payments', 'cashier', 'bundle', 'PHP', 'Doctrine', 'webhooks', 'checkout', 'invoices'],
  openGraph: {
    type: 'website',
    siteName: 'Cashier Symfony',
    locale: 'en',
  },
  twitter: {
    card: 'summary_large_image',
  },
};

type LayoutProps = Readonly<{
  children: ReactNode;
  params: Promise<{ lang: string }>;
}>;

const GITHUB_REPO = "https://github.com/MakFly/stripe-cashier-bundle";

const footer = (
  <footer
    style={{
      margin: "0 auto",
      maxWidth: "var(--nextra-content-width)",
      padding: "3rem 1.5rem",
      color: "rgb(115 115 115)",
    }}
  >
    MIT {new Date().getFullYear()} © Cashier Symfony.
  </footer>
);

const supportedLocales = new Set(["fr", "en"]);

const RootLayout: FC<LayoutProps> = async ({ children, params }) => {
  const { lang } = await params;
  if (!supportedLocales.has(lang)) {
    notFound();
  }
  const rawPageMap = await getPageMap(`/${lang}`);
  // useFSRoute() returns "/" for home when locale is stripped, so we need route "/" to match
  const indexItem = {
    name: "__index__",
    route: "/",
    type: "page" as const,
    theme: { navbar: true, sidebar: false },
  };
  const pageMap = Array.isArray(rawPageMap)
    ? rawPageMap[0] && "data" in rawPageMap[0]
      ? [rawPageMap[0], indexItem, ...rawPageMap.slice(1)]
      : [indexItem, ...rawPageMap]
    : [indexItem, rawPageMap];

  return (
    <html
      lang={lang}
      dir="ltr"
      className={`${GeistSans.variable} ${GeistMono.variable}`}
      suppressHydrationWarning
    >
      <body suppressHydrationWarning>
        <Layout
          navbar={<CustomNavbar lang={lang} />}
          pageMap={pageMap}
          docsRepositoryBase={GITHUB_REPO}
          footer={footer}
          nextThemes={{ defaultTheme: "dark" }}
          editLink="Edit this page on GitHub"
          darkMode={false}
          toc={{
            float: true,
            title: lang === "fr" ? "Sur cette page" : "On this page",
            backToTop: lang === "fr" ? "Retour en haut" : "Back to top",
          }}
          sidebar={{
            defaultMenuCollapseLevel: 1,
            autoCollapse: true,
            defaultOpen: true,
            toggleButton: false,
          }}
        >
          {children}
        </Layout>
      </body>
    </html>
  );
};

export default RootLayout;
