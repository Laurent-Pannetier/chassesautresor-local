const fs = require('fs');
const path = require('path');
const postcss = require('postcss');
const cssnano = require('cssnano');

const themeDir = path.join(__dirname, 'wp-content', 'themes', 'chassesautresor');
const srcDir = path.join(themeDir, 'assets', 'css');
const distDir = path.join(themeDir, 'dist');

async function build() {
    const files = fs.readdirSync(srcDir)
        .filter((file) => file.endsWith('.css'))
        .sort();

    const css = files
        .map((file) => fs.readFileSync(path.join(srcDir, file), 'utf8'))
        .join('\n');

    const result = await postcss([cssnano]).process(css, { from: undefined });

    if (!fs.existsSync(distDir)) {
        fs.mkdirSync(distDir, { recursive: true });
    }

    fs.writeFileSync(path.join(distDir, 'style.min.css'), result.css);
}

build().catch((error) => {
    console.error(error);
    process.exit(1);
});
