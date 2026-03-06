"use client";

import Link from "next/link";
import cn from "clsx";
import { Anchor, Button } from "nextra/components";
import { GitHubIcon, MenuIcon } from "nextra/icons";
import { setMenu, useMenu, useThemeConfig } from "nextra-theme-docs";
import { DocsControls } from "./docs-controls";

const GITHUB_REPO = "https://github.com/MakFly/stripe-cashier-bundle";

type CustomNavbarProps = {
  lang: string;
};

export function CustomNavbar({ lang }: CustomNavbarProps) {
  const { search } = useThemeConfig();
  const menuOpen = useMenu();

  return (
    <header
      className={cn(
        "nextra-navbar x:sticky x:top-0 x:z-30 x:w-full x:bg-transparent x:print:hidden",
        "x:max-md:[.nextra-banner:not([class$=hidden])~&]:top-(--nextra-banner-height)",
      )}
    >
      <div
        className={cn(
          "nextra-navbar-blur",
          "x:absolute x:-z-1 x:size-full",
          "nextra-border x:border-b",
          "x:backdrop-blur-md x:bg-nextra-bg/70",
        )}
      />

      <nav
        style={{ height: "var(--nextra-navbar-height)" }}
        className={cn(
          "x:mx-auto x:flex x:max-w-(--nextra-content-width)",
          "x:items-center x:gap-4",
          "x:pl-[max(env(safe-area-inset-left),1.5rem)] x:pr-[max(env(safe-area-inset-right),1.5rem)]",
          "x:justify-end",
        )}
      >
        {/* Logo */}
        <Link
          href={`/${lang}`}
          className={cn(
            "x:flex x:items-center x:gap-2 x:me-auto",
            "x:transition-opacity x:focus-visible:nextra-focus x:hover:opacity-75",
          )}
          aria-label="Home page"
        >
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            style={{ color: "#10b981" }}
          >
            <path
              d="M12 2L2 12L12 22L22 12L12 2Z"
              fill="currentColor"
              opacity="0.85"
            />
            <path
              d="M12 6L6 12L12 18L18 12L12 6Z"
              fill="currentColor"
              opacity="0.4"
            />
          </svg>
          <span className="x:text-base x:font-semibold x:tracking-tight">
            Cashier Symfony
          </span>
        </Link>

        {/* Nav links — desktop */}
        <div className="x:flex x:gap-4 x:overflow-x-auto nextra-scrollbar x:py-1.5 x:max-md:hidden">
          <Link
            href={`/${lang}/docs`}
            className={cn(
              "x:text-sm x:whitespace-nowrap",
              "x:text-gray-600 x:hover:text-black x:dark:text-gray-400 x:dark:hover:text-gray-200",
              "x:transition-colors",
            )}
          >
            Documentation
          </Link>
        </div>

        {/* Search — desktop */}
        {search && <div className="x:max-md:hidden">{search}</div>}

        {/* Controls — desktop */}
        <div className="x:max-md:hidden x:flex x:items-center x:gap-1">
          <DocsControls />
        </div>

        {/* GitHub icon */}
        <Anchor href={GITHUB_REPO} aria-label="GitHub repository">
          <GitHubIcon height="24" />
        </Anchor>

        {/* Hamburger — mobile */}
        <Button
          aria-label="Menu"
          className={cn(
            "nextra-hamburger x:md:hidden",
            menuOpen && "x:bg-gray-400/20",
          )}
          onClick={() => setMenu((prev) => !prev)}
        >
          <MenuIcon height="24" className={menuOpen ? "x:rotate-90" : ""} />
        </Button>
      </nav>
    </header>
  );
}
