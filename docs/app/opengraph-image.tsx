import { ImageResponse } from 'next/og'

export const runtime = 'edge'

export const alt = 'Cashier Symfony - Stripe Billing for Symfony'

export const size = {
  width: 1200,
  height: 630,
}

export const contentType = 'image/png'

export default function Image() {
  return new ImageResponse(
    (
      <div
        style={{
          width: '100%',
          height: '100%',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          backgroundColor: '#09090b',
          padding: '60px',
        }}
      >
        <div
          style={{
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            gap: '24px',
          }}
        >
          <div
            style={{
              fontSize: 72,
              fontWeight: 700,
              color: '#10b981',
              fontFamily: 'system-ui, sans-serif',
            }}
          >
            Cashier Symfony
          </div>
          <div
            style={{
              fontSize: 36,
              color: '#a1a1aa',
              fontFamily: 'system-ui, sans-serif',
            }}
          >
            Stripe Billing for Symfony
          </div>
        </div>
        <div
          style={{
            position: 'absolute',
            bottom: 0,
            left: 0,
            right: 0,
            height: '4px',
            background: 'linear-gradient(to right, #10b981, #059669)',
          }}
        />
      </div>
    ),
    { ...size },
  )
}
