import type { MDXComponents } from 'mdx/types'

export function useMDXComponents(components: MDXComponents): MDXComponents {
  return {
    ...components,
    h1: ({ children, ...props }) => (
      <h1
        {...props}
        style={{
          fontFamily: 'Plus Jakarta Sans, system-ui, sans-serif',
          fontWeight: 800,
          fontSize: '2.5rem',
          background: 'linear-gradient(135deg, #fff 0%, #94a3b8 100%)',
          WebkitBackgroundClip: 'text',
          WebkitTextFillColor: 'transparent',
          marginBottom: '1.5rem',
        }}
      >
        {children}
      </h1>
    ),
    h2: ({ children, ...props }) => (
      <h2
        {...props}
        style={{
          fontFamily: 'Plus Jakarta Sans, system-ui, sans-serif',
          fontWeight: 700,
          fontSize: '1.75rem',
          color: '#f8fafc',
          marginTop: '2.5rem',
          marginBottom: '1rem',
          display: 'flex',
          alignItems: 'center',
          gap: '0.75rem',
        }}
      >
        <span style={{
          width: '4px',
          height: '1.5rem',
          background: 'linear-gradient(135deg, #635bff 0%, #7c3aed 50%, #ec4899 100%)',
          borderRadius: '9999px',
        }} />
        {children}
      </h2>
    ),
    h3: ({ children, ...props }) => (
      <h3
        {...props}
        style={{
          fontFamily: 'Plus Jakarta Sans, system-ui, sans-serif',
          fontWeight: 600,
          fontSize: '1.25rem',
          color: '#f8fafc',
          marginTop: '2rem',
          marginBottom: '0.75rem',
        }}
      >
        {children}
      </h3>
    ),
    p: ({ children, ...props }) => (
      <p
        {...props}
        style={{
          fontSize: '1rem',
          lineHeight: 1.75,
          color: '#94a3b8',
          marginBottom: '1rem',
        }}
      >
        {children}
      </p>
    ),
    a: ({ children, href, ...props }) => (
      <a
        href={href}
        {...props}
        style={{
          color: '#635bff',
          textDecoration: 'none',
          borderBottom: '1px solid rgba(99, 91, 255, 0.3)',
          transition: 'all 150ms ease',
        }}
      >
        {children}
      </a>
    ),
    ul: ({ children, ...props }) => (
      <ul
        {...props}
        style={{
          margin: '1rem 0',
          paddingLeft: '1.5rem',
          color: '#94a3b8',
        }}
      >
        {children}
      </ul>
    ),
    li: ({ children, ...props }) => (
      <li
        {...props}
        style={{
          marginBottom: '0.5rem',
          lineHeight: 1.6,
        }}
      >
        {children}
      </li>
    ),
    code: ({ children, className, ...props }) => {
      const isInline = !className
      if (isInline) {
        return (
          <code
            {...props}
            style={{
              fontFamily: 'JetBrains Mono, ui-monospace, monospace',
              fontSize: '0.875em',
              padding: '0.125rem 0.375rem',
              background: 'rgba(99, 91, 255, 0.1)',
              border: '1px solid rgba(99, 91, 255, 0.2)',
              borderRadius: '6px',
              color: '#635bff',
            }}
          >
            {children}
          </code>
        )
      }
      return (
        <code {...props} className={className}>
          {children}
        </code>
      )
    },
    pre: ({ children, ...props }) => (
      <pre
        {...props}
        style={{
          fontFamily: 'JetBrains Mono, ui-monospace, monospace',
          fontSize: '0.875rem',
          lineHeight: 1.7,
          padding: '1.5rem',
          background: '#12121a',
          border: '1px solid rgba(148, 163, 184, 0.1)',
          borderRadius: '12px',
          overflow: 'auto',
          margin: '1.5rem 0',
        }}
      >
        {children}
      </pre>
    ),
    blockquote: ({ children, ...props }) => (
      <blockquote
        {...props}
        style={{
          margin: '1.5rem 0',
          padding: '1rem 1.5rem',
          background: 'rgba(99, 91, 255, 0.05)',
          borderLeft: '4px solid #635bff',
          borderRadius: '0 12px 12px 0',
          color: '#94a3b8',
        }}
      >
        {children}
      </blockquote>
    ),
    table: ({ children, ...props }) => (
      <div style={{ overflowX: 'auto', margin: '1.5rem 0' }}>
        <table
          {...props}
          style={{
            width: '100%',
            borderCollapse: 'collapse',
            fontSize: '0.9375rem',
          }}
        >
          {children}
        </table>
      </div>
    ),
    th: ({ children, ...props }) => (
      <th
        {...props}
        style={{
          padding: '0.75rem 1rem',
          textAlign: 'left',
          background: '#12121a',
          borderBottom: '2px solid rgba(99, 91, 255, 0.3)',
          color: '#f8fafc',
          fontWeight: 600,
        }}
      >
        {children}
      </th>
    ),
    td: ({ children, ...props }) => (
      <td
        {...props}
        style={{
          padding: '0.75rem 1rem',
          borderBottom: '1px solid rgba(148, 163, 184, 0.1)',
          color: '#94a3b8',
        }}
      >
        {children}
      </td>
    ),
    hr: (props) => (
      <hr
        {...props}
        style={{
          border: 'none',
          height: '1px',
          background: 'linear-gradient(90deg, transparent, rgba(99, 91, 255, 0.3), transparent)',
          margin: '2.5rem 0',
        }}
      />
    ),
  }
}
