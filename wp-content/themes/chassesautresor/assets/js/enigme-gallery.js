document.addEventListener('DOMContentLoaded', function() {
    const vignettes = document.querySelectorAll('.vignette');
    const principale = document.getElementById('image-enigme-active');
    const lien = principale?.closest('a');
    const container = principale?.closest('.image-principale');
    const picture = principale?.parentElement;

    vignettes.forEach(v => {
        v.addEventListener('click', () => {
            const id = v.getAttribute('data-image-id');
            if (!id || !principale || !lien || !picture) return;

            const base = '/voir-image-enigme?id=' + id;

            const offsetBefore = container ? container.getBoundingClientRect().top : 0;
            if (container) {
                container.style.minHeight = container.offsetHeight + 'px';
            }

            const preload = new Image();
            preload.onload = () => {
                picture.querySelectorAll('source').forEach(source => {
                    const size = source.getAttribute('data-size');
                    source.srcset = base + '&taille=' + size;
                });

                principale.src = base + '&taille=thumbnail';
                lien.href = base + '&taille=full';

                if (container) {
                    container.style.minHeight = '';
                    const offsetAfter = container.getBoundingClientRect().top;
                    window.scrollBy(0, offsetBefore - offsetAfter);
                }

                vignettes.forEach(x => x.classList.remove('active'));
                v.classList.add('active');
            };

            preload.src = base + '&taille=thumbnail';
        });
    });
});
