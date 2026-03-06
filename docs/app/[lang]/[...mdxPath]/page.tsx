import { generateStaticParamsFor, importPage } from "nextra/pages";
import { notFound } from "next/navigation";
import type { ComponentType, FC, ReactNode } from "react";
import { useMDXComponents as getMDXComponents } from "../../../mdx-components";

export const generateStaticParams = generateStaticParamsFor("mdxPath");
const supportedLocales = new Set(["fr", "en"]);

export async function generateMetadata(props: PageProps) {
  const params = await props.params;
  if (!supportedLocales.has(params.lang)) {
    notFound();
  }
  const { metadata } = await importPage(params.mdxPath, params.lang);
  return metadata;
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
