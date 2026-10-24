const quizForm = document.querySelector('#career-quiz');
const quizResult = document.querySelector('#quiz-result');
if (quizForm && quizResult) {
  const paths = {
    people: ['People-centred work', 'You seem energised by listening, supporting, and helping people make progress. Explore education, counselling, community programmes, people operations, or customer success.'],
    ideas: ['Creative communication', 'You seem drawn to expressing ideas and imagining new possibilities. Explore writing, design, media, communications, marketing, or content work.'],
    analysis: ['Research and problem solving', 'You seem to enjoy investigating questions, noticing patterns, and making sense of evidence. Explore data, technology, research, finance, or quality roles.'],
    practical: ['Building and organising', 'You seem to like turning a plan into something that works. Explore operations, project coordination, logistics, product support, or hands-on technical training.'],
  };
  quizForm.addEventListener('submit', (event) => {
    event.preventDefault();
    if (!quizForm.reportValidity()) return;
    const scores = { people: 0, ideas: 0, analysis: 0, practical: 0 };
    new FormData(quizForm).forEach((value) => { if (Object.prototype.hasOwnProperty.call(scores, value)) scores[value] += 1; });
    const top = Object.keys(scores).filter((key) => scores[key] === Math.max(...Object.values(scores)));
    const results = top.map((key) => `<article><h2>${paths[key][0]}</h2><p>${paths[key][1]}</p></article>`).join('');
    quizResult.innerHTML = `<p class="eyebrow eyebrow-muted">A reflection, not a label</p>${results}<p>Your answers are a starting point. A career conversation can help connect your interests with your strengths and real options.</p><div class="feature-actions"><a class="button button-dark" href="book">Talk it through ↗</a><a class="text-link" href="/#contact">Ask a question</a></div>`;
    quizResult.hidden = false;
    quizResult.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
}
