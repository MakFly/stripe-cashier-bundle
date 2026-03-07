import { generateStaticParamsFor, importPage } from "nextra/pages";
import { notFound } from "next/navigation";
import type { ComponentType, FC, ReactNode } from "react";
import { useMDXComponents as getMDXComponents } from "../../../mdx-components";

export const generateStaticParams = generateStaticParamsFor("mdxPath");
const supportedLocales = new Set(["fr", "en"]);

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://stripe-cashier-bundle.vercel.app';

export async function generateMetadata(props: PageProps) {
  const params = await props.params;
  if (!supportedLocales.has(params.lang)) {
    notFound();
  }
  const { metadata } = await importPage(params.mdxPath, params.lang);
  const path = params.mdxPath.join('/');
  const otherLang = params.lang === 'fr' ? 'en' : 'fr';
  return {
    ...metadata,
    alternates: {
      canonical: `${SITE_URL}/${params.lang}/${path}`,
      languages: {
        fr: `${SITE_URL}/fr/${path}`,
        en: `${SITE_URL}/en/${path}`,
      },
    },
  };
}

type PageProps = Readonly<{
  params: Promise<{
    mdxPath: string[];
    lang: string;
  }>;
}>;

const Wrapper = getMDXComponents({}).wrapper as ComponentType<{
  children: ReactNode;
  metadata: unknown;
  sourceCode: string;
  toc: unknown;
}>;

const Page: FC<PageProps> = async (props) => {
  const params = await props.params;
  if (!supportedLocales.has(params.lang)) {
    notFound();
  }
  const result = await importPage(params.mdxPath, params.lang);
  const { default: MDXContent, toc, metadata, sourceCode } = result;
  return (
    <Wrapper toc={toc} metadata={metadata} sourceCode={sourceCode}>
      <MDXContent {...props} params={params} />
    </Wrapper>
  );
};

export default Page;
