import type { MetadataRoute } from 'next'

const baseUrl =
  process.env.NEXT_PUBLIC_SITE_URL ||
  'https://stripe-cashier-bundle.vercel.app'

const docSlugs = [
  'api',
  'checkout',
  'commands',
  'configuration',
  'coupons',
  'customers',
  'events',
  'installation',
  'introduction',
  'invoices',
  'payment-methods',
  'payments',
  'subscriptions',
  'taxes',
  'twig',
  'webhooks',
]

const locales = ['en', 'fr'] as const

export default function sitemap(): MetadataRoute.Sitemap {
  const lastModified = new Date()

  const entries: MetadataRoute.Sitemap = []

  // Homepage for each locale
  entries.push({
    url: `${baseUrl}`,
    lastModified,
    alternates: {
      languages: {
        en: `${baseUrl}/en`,
        fr: `${baseUrl}/fr`,
      },
    },
  })

  // Doc pages
  for (const slug of docSlugs) {
    entries.push({
      url: `${baseUrl}/en/docs/${slug}`,
      lastModified,
      alternates: {
        languages: {
          en: `${baseUrl}/en/docs/${slug}`,
          fr: `${baseUrl}/fr/docs/${slug}`,
        },
      },
    })
  }

  return entries
}
