import type { MetadataRoute } from 'next'

const baseUrl =
  process.env.NEXT_PUBLIC_SITE_URL ||
  'https://stripe-cashier-bundle.vercel.app'

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: '*',
        allow: '/',
        disallow: ['/_next/', '/_pagefind/'],
      },
    ],
    sitemap: `${baseUrl}/sitemap.xml`,
  }
}
