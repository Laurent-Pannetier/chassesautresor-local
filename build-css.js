const fs = require('fs');
const path = require('path');
const postcss = require('postcss');
const postcssImport = require('postcss-import');
const autoprefixer = require('autoprefixer');
const customMedia = require('postcss-custom-media');

const themeDir = path.join(__dirname, 'wp-content', 'themes', 'chassesautresor');
const srcDir = path.join(themeDir, 'assets', 'css');
const distDir = path.join(themeDir, 'dist');

async function build() {
    const mainFile = path.join(srcDir, 'main.css');
    const css = fs.readFileSync(mainFile, 'utf8');

    const result = await postcss([
        postcssImport(),
        customMedia(),
        autoprefixer(),
    ]).process(css, { from: mainFile });

    if (!fs.existsSync(distDir)) {
        fs.mkdirSync(distDir, { recursive: true });
    }

    fs.writeFileSync(path.join(distDir, 'style.css'), result.css);
}

build().catch((error) => {
    console.error(error);
    process.exit(1);
});
