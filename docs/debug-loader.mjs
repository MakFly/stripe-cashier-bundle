import { readFileSync } from 'fs';

const source = readFileSync('node_modules/nextra/dist/server/page-map/get.js', 'utf8');
const rawImport = 'import(`./placeholder.js?lang=${lang}`)';
const locales = ['fr', 'en'];

function replaceDynamicResourceQuery(rawJs, rawImport, locales) {
  const m = rawJs.match(/import\(`(?<importPath>.+?)\?lang=\${lang}`\)/);
  console.log('regex match:', m ? m[0] : 'NO MATCH');
  console.log('importPath:', m?.groups?.importPath);

  const importPath = m?.groups?.importPath;
  if (!importPath) {
    throw new Error("Can't find import statement");
  }

  const replaced = `{
${locales.map((lang) => `"${lang}": () => import("${importPath}?lang=${lang}")`).join(",\n")}
}[lang]()`;

  console.log('\n=== Replaced code ===');
  console.log(replaced);

  const result = rawJs.replace(rawImport, replaced);
  console.log('\n=== Full transformed source ===');
  console.log(result);
  return result;
}

replaceDynamicResourceQuery(source, rawImport, locales);
