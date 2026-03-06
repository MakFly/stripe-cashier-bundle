"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useTheme } from "next-themes";
import { useEffect, useState } from "react";

const locales = [
  { code: "fr", label: "FR" },
  { code: "en", label: "EN" },
] as const;

function getLocalizedPath(pathname: string, nextLocale: string) {
  const segments = pathname.split("/");
  if (segments.length > 1 && locales.some(({ code }) => code === segments[1])) {
    segments[1] = nextLocale;
    return segments.join("/") || "/";
  }
  return `/${nextLocale}${pathname.startsWith("/") ? pathname : `/${pathname}`}`;
}

export function DocsControls() {
  const pathname = usePathname();
  const { resolvedTheme, setTheme } = useTheme();
  const [mounted, setMounted] = useState(false);
  const currentLocale =
    locales.find(({ code }) => pathname.split("/")[1] === code)?.code ?? "fr";

  useEffect(() => setMounted(true), []);

  return (
    <div style={{ display: "flex", alignItems: "center", gap: "0.5rem" }}>
      {/* Locale switcher */}
      <div className="locale-pill-group">
        {locales.map(({ code, label }) => (
          <Link
            key={code}
            href={getLocalizedPath(pathname, code)}
            className="locale-pill"
            data-active={code === currentLocale}
          >
            {label}
          </Link>
        ))}
      </div>

      {/* Theme toggle — hidden until mounted to avoid hydration mismatch */}
      <button
        type="button"
        onClick={() => setTheme(resolvedTheme === "dark" ? "light" : "dark")}
        aria-label="Toggle theme"
        className="theme-toggle"
        style={{ visibility: mounted ? "visible" : "hidden" }}
      >
        {mounted && resolvedTheme === "dark" ? (
          <svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          >
            <circle cx="12" cy="12" r="5" />
            <line x1="12" y1="1" x2="12" y2="3" />
            <line x1="12" y1="21" x2="12" y2="23" />
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
            <line x1="1" y1="12" x2="3" y2="12" />
            <line x1="21" y1="12" x2="23" y2="12" />
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
          </svg>
        ) : (
          <svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          >
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
          </svg>
        )}
      </button>
    </div>
  );
}
