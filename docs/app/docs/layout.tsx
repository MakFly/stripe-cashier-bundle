import { Layout, Navbar, ThemeSwitch } from "nextra-theme-docs";
import { getPageMap } from "nextra/page-map";
import "nextra-theme-docs/style.css";

const navbar = (
  <Navbar
    logo={<b>Cashier Symfony</b>}
    projectLink="https://github.com/kev/cashier-symfony"
  >
    <ThemeSwitch />
  </Navbar>
);

const footer = (
  <div className="x:bg-gray-100 x:pb-[env(safe-area-inset-bottom)] x:dark:bg-neutral-900 x:print:bg-transparent">
    <hr className="nextra-border" />
    <footer className="x:mx-auto x:flex x:max-w-(--nextra-content-width) x:justify-center x:py-12 x:text-gray-600 x:dark:text-gray-400 x:md:justify-start x:pl-[max(env(safe-area-inset-left),1.5rem)] x:pr-[max(env(safe-area-inset-right),1.5rem)]">
      MIT 2025 © Cashier Symfony.
    </footer>
  </div>
);

export default async function DocsLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <Layout
      navbar={navbar}
      pageMap={await getPageMap()}
      docsRepositoryBase="https://github.com/kev/cashier-symfony"
      footer={footer}
      nextThemes={{ defaultTheme: "dark" }}
      editLink="Edit this page on GitHub"
      toc={{
        float: true,
        title: "Sur cette page",
        backToTop: "Retour en haut",
      }}
      sidebar={{
        defaultMenuCollapseLevel: 1,
        autoCollapse: true,
        defaultOpen: true,
        toggleButton: true,
      }}
    >
      {children}
    </Layout>
  );
}
