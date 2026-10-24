<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$formFlash = is_string($_SESSION['form_flash'] ?? null) ? $_SESSION['form_flash'] : '';
unset($_SESSION['form_flash']);
$seoTitle = app_setting('seo_title', 'You Aspirant — Career guidance that starts with you');
$seoDescription = app_setting('seo_description', 'Explore career guidance and practical training with You Aspirant. Get support to understand your strengths, build useful skills, and choose a next step that feels right for you.');
$seoKeywords = app_setting('seo_keywords', 'career guidance, career counselling, student training, career assessment');
$robotsDirective = app_setting('robots_directive', 'index,follow');
$verification = app_setting('google_site_verification');
$canonical = canonical_url('/');
$videoTestimonials = [];
$recentPosts = [];
try {
    $videoTestimonials = database_connection()->query('SELECT id, student_name, story_title, video_url, quote FROM video_testimonials WHERE active = 1 ORDER BY id DESC LIMIT 3')->fetchAll();
    $recentPosts = database_connection()->query("SELECT title, slug, excerpt, published_at FROM blog_posts WHERE status = 'published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 3")->fetchAll();
} catch (Throwable $exception) {
    // The public site still renders while optional content is being set up.
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f4f3e9">
  <meta name="description" content="<?= escape_html($seoDescription) ?>">
  <meta name="keywords" content="<?= escape_html($seoKeywords) ?>">
  <meta name="robots" content="<?= escape_html($robotsDirective) ?>">
  <?php if ($canonical !== ''): ?><link rel="canonical" href="<?= escape_html($canonical) ?>"><?php endif; ?>
  <?php if ($verification !== ''): ?><meta name="google-site-verification" content="<?= escape_html($verification) ?>"><?php endif; ?>
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= escape_html($seoTitle) ?>">
  <meta property="og:description" content="<?= escape_html($seoDescription) ?>">
  <title><?= escape_html($seoTitle) ?></title>
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Crect width='40' height='40' rx='14' fill='%23214f3d'/%3E%3Cpath d='M11 11l9 18 9-18M20 20v9' fill='none' stroke='%23e4f39e' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="features.css">
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "You Aspirant",
    "description": "Career guidance and practical training to help students and early-career people find a direction that fits."
  }
  </script>
  <?php require __DIR__ . '/includes/tracking-head.php'; ?>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="site-header">
    <div class="header-inner page-wrap">
      <a class="brand" href="#top" aria-label="You Aspirant home">
        <span class="brand-mark" aria-hidden="true"><span>Y</span></span>
        <span class="brand-copy"><strong>you aspirant</strong><small>Make your next step yours.</small></span>
      </a>
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" aria-label="Open navigation"><span></span><span></span></button>
      <nav class="primary-nav" id="primary-nav" aria-label="Main navigation">
        <a href="#services">What we do</a><a href="quiz">Career quiz</a><a href="#stories">Student stories</a><a href="blog">Blog</a><a href="<?= is_user_authenticated() ? 'my-account' : 'account' ?>"><?= is_user_authenticated() ? 'My account' : 'Sign in' ?></a><a class="nav-cta" href="#contact">Let’s talk <span aria-hidden="true">↗</span></a>
      </nav>
    </div>
  </header>
  <main id="main">
    <section class="hero page-wrap" id="top" aria-labelledby="hero-title">
      <div class="hero-copy">
        <p class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span> Career guidance, with you in mind</p>
        <h1 id="hero-title">You don’t have to have it all <span class="hand-underline">figured out.</span></h1>
        <p class="hero-intro">Not sure what comes after college? You Aspirant helps you explore your options, build practical skills, and move forward with a plan that feels like <em>you.</em></p>
        <div class="hero-actions"><a class="button button-dark" href="#contact">Find my next step <span aria-hidden="true">↗</span></a><a class="text-link" href="quiz">Take the career quiz <span aria-hidden="true">↗</span></a></div>
        <div class="hero-note"><span class="note-star" aria-hidden="true">✳</span><span>For students, graduates, and anyone at a crossroads.</span></div>
      </div>
      <div class="hero-visual">
        <div class="hero-sun" aria-hidden="true"></div>
        <figure class="hero-photo"><img src="assets/campus-mentoring.png" alt="Three college students sharing notes and talking together in a bright campus study space" width="2040" height="768" fetchpriority="high"></figure>
        <div class="photo-caption"><span class="caption-spark" aria-hidden="true">✳</span><span>Small steps.<br><strong>New possibilities.</strong></span></div>
        <div class="photo-sticker" aria-hidden="true"><span>Start where<br>you are.</span><b>↘</b></div>
      </div>
    </section>
    <section class="feature-callout page-wrap" aria-label="Try a career assessment">
      <div><p class="eyebrow eyebrow-muted">A small first step</p><h2>Not sure where to begin?</h2><p>Try a short career assessment, see which work styles resonate, and get ideas for what to explore next.</p></div>
      <a class="button button-dark" href="quiz">Take the career quiz <span aria-hidden="true">↗</span></a>
      <a class="text-link" href="book">Book a counselling slot <span aria-hidden="true">↗</span></a>
    </section>
    <div class="word-ribbon" aria-label="Curiosity, confidence, direction"><div class="ribbon-track" aria-hidden="true"><span>Curiosity <i>✳</i></span><span>Confidence <i>✳</i></span><span>Direction <i>✳</i></span><span>One step at a time <i>✳</i></span><span>Curiosity <i>✳</i></span><span>Confidence <i>✳</i></span><span>Direction <i>✳</i></span></div></div>
    <section class="intro-section section-pad page-wrap" aria-labelledby="intro-title">
      <div class="section-label"><span>01</span><span>A place to begin</span></div>
      <div class="intro-grid"><h2 id="intro-title">You bring the questions.<br><em>We’ll figure out the next bit together.</em></h2><div class="intro-text"><p>Choosing a path can feel like a lot when everyone seems to have an opinion. A good conversation can make it easier to hear your own.</p><a class="text-link link-underline" href="#services">Get to know our services <span aria-hidden="true">↗</span></a></div></div>
      <div class="value-row" aria-label="What to expect">
        <div class="value-item"><span class="value-icon icon-sun" aria-hidden="true">✳</span><div><strong>Listen first</strong><small>Your interests and questions set the direction.</small></div></div>
        <div class="value-item"><span class="value-icon icon-leaf" aria-hidden="true">↗</span><div><strong>Keep it practical</strong><small>Turn good conversations into doable actions.</small></div></div>
        <div class="value-item"><span class="value-icon icon-heart" aria-hidden="true">♡</span><div><strong>Go at your pace</strong><small>Make room to think, ask, and change your mind.</small></div></div>
      </div>
    </section>
    <section class="services-section section-pad" id="services" aria-labelledby="services-title">
      <div class="page-wrap"><div class="section-label"><span>02</span><span>How we can help</span></div>
        <div class="section-heading-row"><div><p class="eyebrow eyebrow-muted">A little support goes a long way</p><h2 id="services-title">Find the kind<br>that fits <em>you.</em></h2></div><p class="section-heading-note">Start with one question, or bring the whole list. We’ll help you work out what would be useful right now.</p></div>
        <div class="service-grid">
          <article class="service-card service-card-sage"><div class="card-topline"><span class="service-index">01</span><span class="service-doodle doodle-orbit" aria-hidden="true">◎</span></div><h3>Career<br>guidance</h3><p>Make sense of your interests, strengths, and options, then find a direction worth exploring.</p><a href="#contact" class="card-link" aria-label="Ask about career guidance">Let’s explore <span aria-hidden="true">↗</span></a></article>
          <article class="service-card service-card-yellow"><div class="card-topline"><span class="service-index">02</span><span class="service-doodle doodle-spark" aria-hidden="true">✳</span></div><h3>Skills &<br>training</h3><p>Practise useful skills, build confidence, and feel more prepared for what you want to try next.</p><a href="#contact" class="card-link" aria-label="Ask about skills and training">Build your toolkit <span aria-hidden="true">↗</span></a></article>
          <article class="service-card service-card-peach"><div class="card-topline"><span class="service-index">03</span><span class="service-doodle doodle-arrow" aria-hidden="true">↗</span></div><h3>Interview<br>practice</h3><p>Work through your story, prepare for common questions, and get comfortable speaking up.</p><a href="#contact" class="card-link" aria-label="Ask about interview practice">Find your words <span aria-hidden="true">↗</span></a></article>
        </div><p class="service-footnote"><span aria-hidden="true">✳</span> Not sure which one? That’s a perfectly good place to start.</p>
      </div>
    </section>
    <section class="approach-section section-pad page-wrap" id="approach" aria-labelledby="approach-title">
      <div class="approach-left"><div class="section-label"><span>03</span><span>A simple way forward</span></div><p class="eyebrow eyebrow-muted">No big leaps required</p><h2 id="approach-title">Clarity comes<br><em>one step at a time.</em></h2><p class="approach-intro">There’s no test you need to pass before asking for guidance. We start with where you are and take it from there.</p><a class="button button-outline" href="#contact">Talk through your options <span aria-hidden="true">↗</span></a></div>
      <div class="steps-list">
        <article class="step-row"><span class="step-number">01</span><div><h3>Start with you</h3><p>Talk about what you enjoy, what feels unclear, and what you’d like life to look like.</p></div><span class="step-mark" aria-hidden="true">↗</span></article>
        <article class="step-row"><span class="step-number">02</span><div><h3>Explore the options</h3><p>Look at possible paths and skills together, without pretending there’s only one right answer.</p></div><span class="step-mark" aria-hidden="true">◎</span></article>
        <article class="step-row"><span class="step-number">03</span><div><h3>Choose a next step</h3><p>Leave with something small and useful to try, plus a clearer idea of what comes after.</p></div><span class="step-mark" aria-hidden="true">✳</span></article>
      </div>
    </section>
    <section class="stories-section section-pad" id="stories" aria-labelledby="stories-title">
      <div class="page-wrap"><div class="section-label"><span>04</span><span>There’s more than one way</span></div><div class="stories-heading"><div><p class="eyebrow eyebrow-muted">Progress is personal</p><h2 id="stories-title">Every journey<br>starts <em>somewhere.</em></h2></div><p>Sometimes the first win is a shortlist. Sometimes it’s walking into an interview with a steadier voice. Your version of progress counts.</p></div>
        <div class="story-grid">
          <article class="story-card story-card-green"><div class="story-label"><span class="story-star" aria-hidden="true">✳</span> An illustrative journey</div><h3>From “too many choices” to a first shortlist</h3><p>Start with what gives you energy, then narrow the possibilities into a few paths you can actually look into.</p><div class="story-end"><span>A starting point, not a final answer</span><span aria-hidden="true">↘</span></div></article>
          <article class="story-card story-card-cream"><div class="story-label"><span class="story-star" aria-hidden="true">✳</span> An illustrative journey</div><h3>From “I’m not ready” to “I can try”</h3><p>Practise explaining your strengths out loud, make a few notes, and take the next opportunity one question at a time.</p><div class="story-end"><span>Confidence grows with practice</span><span aria-hidden="true">↘</span></div></article>
          <article class="story-card story-card-orange"><div class="story-label"><span class="story-star" aria-hidden="true">✳</span> An illustrative journey</div><h3>From a vague goal to a plan for this month</h3><p>Break a big career question into small actions you can take between classes, work, and everything else.</p><div class="story-end"><span>Progress that fits real life</span><span aria-hidden="true">↘</span></div></article>
        </div>
      </div>
    </section>
    <section class="voices-section section-pad page-wrap" aria-labelledby="voices-title">
      <div class="voices-heading"><div class="section-label"><span>05</span><span>Student voices</span></div><h2 id="voices-title">A little more room<br>to <em>believe in yourself.</em></h2><p>Here’s where real student experiences will live. The quotes below are clearly marked sample copy for this competition prototype.</p></div>
      <div class="quote-grid">
        <figure class="quote-card quote-card-one"><div class="quote-mark" aria-hidden="true">“</div><blockquote>We talked through what I actually enjoy, instead of picking a career just because it sounded impressive.</blockquote><figcaption><span class="avatar avatar-one" aria-hidden="true">S</span><span><strong>Sample student voice</strong><small>Mock quote · replace before launch</small></span></figcaption></figure>
        <figure class="quote-card quote-card-two"><div class="quote-mark" aria-hidden="true">“</div><blockquote>Practising out loud made the interview feel less like a test and more like a conversation.</blockquote><figcaption><span class="avatar avatar-two" aria-hidden="true">A</span><span><strong>Sample student voice</strong><small>Mock quote · replace before launch</small></span></figcaption></figure>
        <figure class="quote-card quote-card-three"><div class="quote-mark" aria-hidden="true">“</div><blockquote>I left with one thing to try this week. That made the whole career question feel smaller.</blockquote><figcaption><span class="avatar avatar-three" aria-hidden="true">R</span><span><strong>Sample student voice</strong><small>Mock quote · replace before launch</small></span></figcaption></figure>
      </div><p class="quote-note"><span aria-hidden="true">✳</span> These are demo quotes, not verified testimonials. Add approved student feedback before publishing.</p>
    </section>
    <section class="video-section section-pad" aria-labelledby="video-title"><div class="page-wrap">
      <div class="section-label"><span>06</span><span>Hear it from students</span></div><div class="stories-heading"><div><p class="eyebrow eyebrow-muted">Real voices, real next steps</p><h2 id="video-title">Stories in their<br><em>own words.</em></h2></div><p>Short reflections from students who have taken a next step with a little support.</p></div>
      <?php if ($videoTestimonials): ?><div class="video-grid">
        <?php foreach ($videoTestimonials as $video): $videoId = youtube_video_id((string) $video['video_url']); if ($videoId === '') continue; ?>
          <article class="video-card"><div class="video-frame"><iframe src="https://www.youtube-nocookie.com/embed/<?= escape_html($videoId) ?>" title="<?= escape_html($video['story_title']) ?> — student video testimonial" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div><div class="video-copy"><p><?= escape_html($video['quote']) ?></p><strong><?= escape_html($video['student_name']) ?></strong><small><?= escape_html($video['story_title']) ?></small></div></article>
        <?php endforeach; ?>
      </div><?php else: ?><div class="content-empty"><p>Approved student video stories will appear here. Add a YouTube link from the admin panel when you have permission to share it.</p></div><?php endif; ?>
    </div></section>
    <?php if ($recentPosts): ?><section class="blog-preview section-pad" aria-labelledby="blog-preview-title"><div class="page-wrap"><div class="stories-heading"><div><p class="eyebrow eyebrow-muted">Notes for the road ahead</p><h2 id="blog-preview-title">From the<br><em>You Aspirant blog.</em></h2></div><a class="text-link" href="blog">All articles ↗</a></div><div class="blog-card-grid">
      <?php foreach ($recentPosts as $post): ?><article class="blog-card"><small><?= escape_html(date('M j, Y', strtotime((string) $post['published_at']))) ?></small><h3><a href="article?slug=<?= rawurlencode((string) $post['slug']) ?>"><?= escape_html($post['title']) ?></a></h3><p><?= escape_html($post['excerpt']) ?></p><a class="card-link" href="article?slug=<?= rawurlencode((string) $post['slug']) ?>">Read article <span aria-hidden="true">↗</span></a></article><?php endforeach; ?>
    </div></div></section><?php endif; ?>
    <section class="faq-section section-pad" aria-labelledby="faq-title"><div class="page-wrap faq-grid">
      <div class="faq-heading"><div class="section-label"><span>07</span><span>A few good questions</span></div><h2 id="faq-title">Wondering<br>about <em>something?</em></h2><p>You don’t need to have everything figured out to get in touch. Here are a few things people often ask.</p></div>
      <div class="faq-list">
        <details><summary>What if I have no idea what career I want?<span aria-hidden="true">+</span></summary><p>That’s a common place to begin. Guidance can help you sort through what you enjoy, what matters to you, and which options are worth exploring.</p></details>
        <details><summary>Who is career guidance for?<span aria-hidden="true">+</span></summary><p>Students and early-career people who would like help thinking through choices, preparing for opportunities, or building practical skills.</p></details>
        <details><summary>Do you guarantee a job or placement?<span aria-hidden="true">+</span></summary><p>No. Career outcomes depend on many things. The aim is to offer thoughtful guidance and useful practice so you can take your next step with more confidence.</p></details>
        <details><summary>What should I bring to a first conversation?<span aria-hidden="true">+</span></summary><p>Bring whatever’s on your mind. A question, a course you’re considering, a CV draft, or simply “I’m not sure yet” is plenty to start with.</p></details>
      </div>
    </div></section>
    <section class="contact-section section-pad" id="contact" aria-labelledby="contact-title"><div class="contact-wrap page-wrap">
      <div class="contact-copy"><div class="section-label section-label-light"><span>08</span><span>Your next step starts here</span></div><p class="eyebrow eyebrow-lime">No pressure. Just a conversation.</p><h2 id="contact-title">Let’s make<br>the next step <em>clearer.</em></h2><p>Tell us a little about what you’re looking for. We’ll help you work out where to begin.</p><div class="contact-aside"><span aria-hidden="true">✳</span><span>It’s okay if your answer is<br><strong>“I’m still figuring it out.”</strong></span></div></div>
      <form class="contact-form" id="lead-form" action="submit" method="post">
          <input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>">
          <div class="form-trap" aria-hidden="true"><label for="company-website">Leave this field blank</label><input id="company-website" name="website" type="text" tabindex="-1" autocomplete="off"></div><div class="form-heading"><span class="form-kicker">A good place to start</span><h3>What’s on your mind?</h3><p>Share a few details and we’ll map out a first step.</p></div>
        <label for="full-name">Your name <span aria-hidden="true">*</span></label><input id="full-name" name="name" type="text" maxlength="120" placeholder="What should we call you?" value="<?= escape_html($_SESSION['user_name'] ?? '') ?>" autocomplete="name" required>
        <label for="email">Email address <span aria-hidden="true">*</span></label><input id="email" name="email" type="email" maxlength="254" placeholder="you@example.com" value="<?= escape_html($_SESSION['user_email'] ?? '') ?>" autocomplete="email" required>
        <label for="interest">I’m looking for… <span aria-hidden="true">*</span></label><select id="interest" name="interest" required><option value="" selected disabled>Choose what sounds closest</option><option>Career guidance</option><option>Skills and training</option><option>Interview practice</option><option>A little help figuring that out</option></select>
        <label for="message">Anything you’d like us to know? <span class="optional">Optional</span></label><textarea id="message" name="message" rows="3" maxlength="3000" placeholder="A question, a goal, or whatever’s on your mind…"></textarea>
        <button class="button button-lime form-submit" type="submit">Send my enquiry <span aria-hidden="true">↗</span></button><p class="privacy-note">We’ll use your details to respond to this enquiry. <a href="<?= is_user_authenticated() ? 'my-account' : 'account' ?>"><?= is_user_authenticated() ? 'Follow replies in your account.' : 'Create an account to follow replies.' ?></a></p><p class="form-status" id="form-status" role="status" aria-live="polite"><?= escape_html($formFlash) ?></p>
      </form>
    </div></section>
  </main>
  <footer class="site-footer"><div class="footer-main page-wrap"><a class="brand brand-footer" href="#top" aria-label="You Aspirant home"><span class="brand-mark" aria-hidden="true"><span>Y</span></span><span class="brand-copy"><strong>you aspirant</strong><small>Make your next step yours.</small></span></a><p>Good guidance starts with a good conversation.</p><a class="back-top" href="#top">Back to top <span aria-hidden="true">↑</span></a></div><div class="footer-bottom page-wrap"><span>© <span id="year"></span> You Aspirant</span><span>Made with care for the next generation of doers.</span><a href="#contact">Start a conversation <span aria-hidden="true">↗</span></a><a href="blog">Blog</a><a href="<?= is_user_authenticated() ? 'my-account' : 'account' ?>">Student account</a><a class="team-signin" href="admin/login">Team sign in</a></div></footer>
  <?php require __DIR__ . '/includes/sticky-ctas.php'; ?>
  <?php require __DIR__ . '/includes/tracking-body.php'; ?>
  <script src="script.js" defer></script>
</body>
</html>


