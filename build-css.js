const fs = require('fs');
const path = require('path');
const sass = require('sass');
const postcss = require('postcss');
const autoprefixer = require('autoprefixer');
const customMedia = require('postcss-custom-media');

const themeDir = path.join(__dirname, 'wp-content', 'themes', 'chassesautresor');
const srcDir = path.join(themeDir, 'assets', 'scss');
const distDir = path.join(themeDir, 'dist');

async function build() {
    const mainFile = path.join(srcDir, 'main.scss');
    const sassResult = sass.compile(mainFile, { style: 'expanded' });

    const result = await postcss([
        customMedia(),
        autoprefixer(),
    ]).process(sassResult.css, { from: mainFile });

    if (!fs.existsSync(distDir)) {
        fs.mkdirSync(distDir, { recursive: true });
    }

    fs.writeFileSync(path.join(distDir, 'style.css'), result.css);
}

build().catch((error) => {
    console.error(error);
    process.exit(1);
});
