import type { NextConfig } from "next";
import nextra from "nextra";

const withNextra = nextra({
  defaultShowCopyCode: true,
  contentDirBasePath: "/",
});

const nextConfig: NextConfig = {
  i18n: {
    locales: ["fr", "en"],
    defaultLocale: "fr",
  },
  transpilePackages: ["geist"],
  eslint: { ignoreDuringBuilds: true },
  outputFileTracingRoot: __dirname,
  async redirects() {
    return [
      {
        source: "/docs",
        destination: "/docs/introduction",
        permanent: false,
      },
      {
        source: "/fr/docs",
        destination: "/fr/docs/introduction",
        permanent: false,
      },
      {
        source: "/en/docs",
        destination: "/en/docs/introduction",
        permanent: false,
      },
    ];
  },
};

export default withNextra(nextConfig);
