# You Aspirant — career guidance website

A responsive career guidance and training website for a college competition. It includes a student account area, a MySQL enquiry inbox, counselling bookings, an interactive career reflection quiz, expert assignment, a small blog CMS, SEO controls, and a warm editorial visual style.

## Run locally

Requirements: PHP 8+ with PDO MySQL enabled, and MySQL or MariaDB.

1. Create a database named `you_aspirant`.
2. Select it and import `database/schema.sql` in phpMyAdmin. The script creates the tables and safely adds the new columns used by these features to an earlier version of this project.
3. Copy `config.example.php` to `config.php`. Set the database host, database name, MySQL username, and MySQL password.
4. From this folder, run `php create_admin.php` in a terminal. Enter your admin email and choose a password of at least 12 characters. The app stores a protected password verifier in MySQL; you do not need to create a hash yourself.
5. Start PHP’s local server from this folder:

   ```sh
   php -S 127.0.0.1:8000 router.php
   ```

6. Open `http://127.0.0.1:8000/`. Admin sign-in is `http://127.0.0.1:8000/admin/login`.

After signing in, add experts and publish available counselling times before students try to book. Set the real website URL and WhatsApp number in **SEO & settings**. Students should create an account and sign in before sending an enquiry if they want to follow replies in their dashboard.

## Main features

- **Student website:** career guidance and training services, stories, FAQ, responsive enquiry form, sticky enquiry CTA, and configurable WhatsApp CTA.
- **Career assessment:** a short self-reflection quiz that suggests areas to explore. It is not a formal psychometric assessment.
- **Student accounts:** students can register, send linked enquiries, read expert assignments and replies, reply to the team, and view counselling request status. Have students sign in before submitting if they need account-based follow-up.
- **Counselling calendar:** admins publish available dates and times; students request an open slot; admins assign an expert and confirm or update the booking. Requests are not confirmed until the team confirms them.
- **Admin inbox:** search and filter enquiries, assign experts, reply to account-linked students, and monitor new leads, unread student replies, and pending bookings. The in-panel count refreshes every 15 seconds.
- **Expert directory:** add experts with service specialties so the admin can match them to a student’s selected service.
- **Video testimonials:** add approved YouTube stories from the admin panel. Only publish videos you have permission to share. Existing sample quotes are clearly marked as mock content.
- **Blog CMS:** draft or publish plain-text articles, set the URL slug, SEO title, description, keywords, and robots directive.
- **SEO and integrations:** set website URL, default title, description, keywords, and index/follow directive; add Google Search Console verification, a Google Analytics measurement ID, WhatsApp phone, and trusted ad/tracking snippets. `sitemap.xml` and `robots.txt` are generated automatically. Set the real website URL in Admin → SEO & settings before submitting the sitemap to Search Console.

The site uses clean URLs such as `/account`, `/quiz`, `/blog`, and `/admin/login`; old `.php` links redirect to those addresses. Admin pages are available after signing in. For an Apache host, `.htaccess` disables directory listings, blocks internal files, and routes extensionless pages plus `robots.txt` and `sitemap.xml`. Nginx hosts need equivalent server rules.

## Before publishing

Replace sample testimonials with approved student feedback, add the company’s real contact details and WhatsApp number, set the production site URL, and review the privacy wording. The project does not send email automatically: the admin can respond using the student’s email address or use the account reply feature. Admin notifications appear in the inbox panel.

GitHub Pages cannot run PHP or MySQL. Use GitHub for the competition repository, and deploy the working website to a PHP/MySQL host. Do not commit `config.php` or real credentials; `config.php` is excluded by `.gitignore`.

## Push this local repo to GitHub

This folder is initialized as a local Git repository on the `main` branch. Create an empty repository on GitHub, then run these commands from this folder, replacing the username:

```sh
git add .
git commit -m "Build You Aspirant PHP and MySQL website"
git remote add origin https://github.com/YOUR-USERNAME/you-aspirant-website.git
git push -u origin main
```
