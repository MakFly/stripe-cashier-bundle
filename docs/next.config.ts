import type { NextConfig } from "next";
import nextra from "nextra";

const withNextra = nextra({
  defaultShowCopyCode: true,
});

const nextConfig: NextConfig = {
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
    ];
  },
};

export default withNextra(nextConfig);
