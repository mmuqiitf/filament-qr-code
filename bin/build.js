import * as esbuild from "esbuild";

const isDev = process.argv.includes("--dev");

async function compile(options) {
  const ctx = await esbuild.context(options);

  if (isDev) {
    await ctx.watch();
    console.log("Watching for changes...");
  } else {
    await ctx.rebuild();
    await ctx.dispose();
  }
}

const defaultOptions = {
  define: {
    "process.env.NODE_ENV": isDev ? '"development"' : '"production"',
  },
  bundle: true,
  mainFields: ["module", "main"],
  platform: "neutral",
  sourcemap: isDev ? "inline" : false,
  sourcesContent: isDev,
  treeShaking: true,
  target: ["es2020"],
  minify: !isDev,
  format: "esm",
  external: ["alpinejs"],
};

// Compile Alpine component
compile({
  ...defaultOptions,
  entryPoints: ["./resources/js/components/qr-scanner.js"],
  outfile: "./resources/dist/js/components/qr-scanner.js",
});

console.log(
  isDev ? "Development build started..." : "Production build complete!",
);
