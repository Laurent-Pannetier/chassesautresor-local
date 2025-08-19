document.querySelectorAll('.accordeon-bloc').forEach(bloc => {
  const toggle = bloc.querySelector('.accordeon-toggle');
  const contenu = bloc.querySelector('.accordeon-contenu');
  const aside = bloc.closest('.menu-lateral');

  if (!toggle || !contenu || !aside) return;

  // Synchronise l’état initial
  const estOuvert = toggle.getAttribute('aria-expanded') === 'true';
  contenu.classList.toggle('accordeon-ferme', !estOuvert);
  aside.classList.toggle('has-open-accordeon', estOuvert);

  toggle.addEventListener('click', () => {
    const estActuellementOuvert = toggle.getAttribute('aria-expanded') === 'true';

    // Ferme tous les autres blocs
    document.querySelectorAll('.accordeon-bloc').forEach(otherBloc => {
      const otherToggle = otherBloc.querySelector('.accordeon-toggle');
      const otherContenu = otherBloc.querySelector('.accordeon-contenu');
      const otherAside = otherBloc.closest('.menu-lateral');

      if (!otherToggle || !otherContenu || !otherAside) return;

      otherToggle.setAttribute('aria-expanded', 'false');
      otherContenu.classList.add('accordeon-ferme');
      otherAside.classList.remove('has-open-accordeon');
    });

    // Ouvre uniquement si ce n’était pas déjà ouvert
    if (!estActuellementOuvert) {
      toggle.setAttribute('aria-expanded', 'true');
      contenu.classList.remove('accordeon-ferme');
      aside.classList.add('has-open-accordeon');
    }
  });

  toggle.addEventListener('mouseenter', () => {
    toggle.classList.add('is-hovered');
  });

  toggle.addEventListener('mouseleave', () => {
    toggle.classList.remove('is-hovered');
  });
});
