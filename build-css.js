const fs = require('fs');
const path = require('path');
const sass = require('sass');
const postcss = require('postcss');
const autoprefixer = require('autoprefixer');
const customMedia = require('postcss-custom-media');

const themeDir = path.join(__dirname, 'wp-content', 'themes', 'chassesautresor');
const srcDir = path.join(themeDir, 'assets', 'scss');
const distDir = path.join(themeDir, 'dist');

const entries = [
    { src: 'main.scss', dest: 'style.css' },
    { src: 'front-page.scss', dest: 'front-page.css' },
    { src: 'single-chasse.scss', dest: 'single-chasse.css' },
    { src: 'single-enigme.scss', dest: 'single-enigme.css' },
    { src: 'single-organisateur.scss', dest: 'single-organisateur.css' },
    { src: 'account.scss', dest: 'account.css' },
    { src: 'edition-mode.scss', dest: 'edition-mode.css' },
    { src: 'woocommerce.scss', dest: 'woocommerce.css' },
];

async function build() {
    if (!fs.existsSync(distDir)) {
        fs.mkdirSync(distDir, { recursive: true });
    }

    for (const entry of entries) {
        const input = path.join(srcDir, entry.src);
        const sassResult = sass.compile(input, { style: 'expanded' });

        const result = await postcss([
            customMedia(),
            autoprefixer(),
        ]).process(sassResult.css, { from: input });

        fs.writeFileSync(path.join(distDir, entry.dest), result.css);
    }
}

build().catch((error) => {
    console.error(error);
    process.exit(1);
});
