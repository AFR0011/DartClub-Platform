# FREE_DEPLOYMENT_GUIDE

## Goal
Prepare this legacy PHP/MySQL app for a low-cost or free deployment path without rewriting it into a static or serverless app.

## Recommended Target
For this repo, the best free long-term option is an **Oracle Cloud Always Free VM** running a standard Apache + PHP + MySQL/MariaDB stack.

Why:
- this app already matches the LAMP model
- it uses PHP sessions
- it writes uploaded files to local disk under `files/`
- it expects a normal relational database

Official Oracle Free Tier reference:
- https://www.oracle.com/cloud/free/

Important Oracle Free Tier constraints from Oracle:
- Always Free services are available for an unlimited time
- accounts idle for 30 days or more may be suspended or terminated
- capacity is not guaranteed in every region

## Free Hosting Options

### Option A: Oracle Cloud Always Free VM
Best overall fit.

Pros:
- runs the app with minimal code changes
- supports full PHP/MySQL behavior
- supports file uploads and local storage
- can stay free long-term if you stay within Always Free limits
- gives you SSH access, Apache config, and full control

Cons:
- hardest setup
- requires account/card verification
- you are responsible for OS updates, backups, firewall rules, and SSL
- capacity in your preferred region may be unavailable
- idle or abandoned free accounts can be reclaimed

### Option B: Free shared PHP hosting
Practical only as a fallback for a small demo or short-term public preview.

One current example with official free-hosting marketing is AwardSpace:
- https://www.awardspace.com/free-hosting/

Pros:
- easier than running a VPS
- includes PHP, MySQL/phpMyAdmin, file manager, and no server administration
- good for a quick public demo

Cons:
- tighter traffic/storage/runtime limits
- less predictable performance
- no real server control
- provider-specific restrictions can break uploads, mail, or larger imports
- weaker long-term reliability than a VPS you control

### Option C: Static/serverless hosts like Netlify
Not recommended for this repo.

Why:
- this app is not static
- it depends on PHP request handlers under `services/`
- it depends on MySQL
- it depends on filesystem uploads and PHP sessions

## Current Repo Prep Included
- `services/config.php` now loads an optional `services/config.local.php` first, so deployment credentials can be overridden without editing tracked files
- `services/config.local.example.php` shows the expected override shape
- root `.htaccess` blocks direct web access to sensitive repo files and disables directory listing

## Pre-Deployment Decisions

### Decision 1: True production vs public demo
Choose one:

- **Production-like deployment**
  Use Oracle Cloud Always Free.
  Best when you want the full app live.

- **Temporary public demo**
  Use a free shared PHP host.
  Best when you mainly need something online quickly.

### Decision 2: Keep uploaded files in local disk or move them later
Current code stores uploads inside `files/`.

Pros of keeping it as-is:
- no rewrite
- simplest deployment

Cons:
- backups must include both DB and filesystem
- restoring the site requires restoring both pieces
- scaling later is harder

### Decision 3: Mail delivery now or later
The repo contains PHPMailer support, but SMTP still needs real deployment verification.

Pros of configuring SMTP immediately:
- full membership/auth flows work closer to production expectations

Cons:
- extra setup and another failure point on day one

Recommendation:
- deploy the site first
- verify core pages and DB-backed flows
- add SMTP after the app is stable on the host

## Oracle Cloud Always Free Deployment

### Step 1: Create the cloud account
1. Sign up at https://www.oracle.com/cloud/free/
2. Complete identity and card verification.
3. Pick a region that has available Always Free compute capacity.
4. Keep a record of:
   - tenancy/account
   - region
   - public IP
   - SSH private key location

What can go wrong:
- region has no free-capacity VM available
- card verification fails

How to fix it:
- try an adjacent region
- retry with a real credit/debit card that allows temporary verification holds

### Step 2: Create a VM
1. Create an Always Free compute instance.
2. Use Ubuntu if you want the most common LAMP guides.
3. Keep the default SSH key or upload your own public key.
4. Record the public IP.

Recommended baseline:
- 1 small Always Free VM
- Ubuntu LTS

What can go wrong:
- no capacity for the chosen shape
- SSH key misconfigured

How to fix it:
- retry with another eligible Always Free shape/region
- replace the key from the console if needed

### Step 3: Open the firewall correctly
You need:
- `22` for SSH
- `80` for HTTP
- `443` for HTTPS

There are two layers:
- Oracle network security rules
- the VM OS firewall

What can go wrong:
- site or SSH looks down even though Apache is running

How to fix it:
- verify OCI ingress rules
- verify `ufw` or other local firewall rules

### Step 4: Install the web stack
SSH into the VM and install:

```bash
sudo apt update
sudo apt install -y apache2 mysql-server php libapache2-mod-php php-mysql php-mbstring php-xml php-curl php-zip unzip
```

Enable Apache modules you may need:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

What can go wrong:
- package install partially fails
- Apache starts but PHP pages download as files

How to fix it:
- rerun install and confirm `libapache2-mod-php` is present
- restart Apache and recheck with a small `phpinfo()` file if needed

### Step 5: Create the database
1. Secure MySQL first:

```bash
sudo mysql_secure_installation
```

2. Create DB and app user:

```sql
CREATE DATABASE dart_club CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dartclub_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON dart_club.* TO 'dartclub_user'@'localhost';
FLUSH PRIVILEGES;
```

What can go wrong:
- wrong password or user host
- import succeeds but app still cannot connect

How to fix it:
- verify the DB credentials in `services/config.local.php`
- verify the MySQL user is allowed from `localhost`

### Step 6: Upload the repo
Put the project on the VM, for example under:
- `/var/www/dart-club`

You can use:
- `scp`
- `rsync`
- Git clone if the repo is pushed somewhere

What to upload:
- `pages/`
- `services/`
- `css/`
- `js/`
- `files/` if you want existing uploads/content
- `vendor/`
- `dart_club.sql` only if you still need to import it on-server

What not to expose publicly:
- local logs
- docs
- repo metadata
- scratch notes

The root `.htaccess` added in this prep pass helps with that.

### Step 7: Add the production DB override
On the server:
1. copy `services/config.local.example.php` to `services/config.local.php`
2. replace the placeholder DB values

Example:

```php
<?php
putenv('APP_DB_HOST=localhost');
putenv('APP_DB_PORT=3306');
putenv('APP_DB_NAME=dart_club');
putenv('APP_DB_USER=dartclub_user');
putenv('APP_DB_PASS=strong_password_here');
putenv('APP_DB_CHARSET=utf8mb4');
```

What can go wrong:
- you edit `services/config.php` directly and later lose changes during an update

How to fix it:
- keep tracked defaults unchanged
- use `services/config.local.php` only on the server

### Step 8: Import the database
If you exported locally to `dart_club.sql`, import it:

```bash
mysql -u dartclub_user -p dart_club < dart_club.sql
```

What can go wrong:
- import fails due to size/timeouts in a weak shared host
- charset/collation mismatch

How to fix it:
- on Oracle VM, import over SSH from CLI instead of through a browser
- keep the DB in `utf8mb4`

### Step 9: Set permissions for uploads
This repo writes into `files/`.

Make Apache own or at least write to those directories:

```bash
sudo chown -R www-data:www-data /var/www/dart-club
sudo find /var/www/dart-club -type d -exec chmod 755 {} \\;
sudo find /var/www/dart-club -type f -exec chmod 644 {} \\;
sudo chmod -R 775 /var/www/dart-club/files
```

What can go wrong:
- uploads fail
- documents/images show as missing after upload

How to fix it:
- check `files/` ownership and write permissions
- check the stored relative paths in the DB

### Step 10: Configure Apache virtual host
Example vhost:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/dart-club

    <Directory /var/www/dart-club>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/dart-club-error.log
    CustomLog ${APACHE_LOG_DIR}/dart-club-access.log combined
</VirtualHost>
```

Enable it:

```bash
sudo a2ensite dart-club.conf
sudo systemctl reload apache2
```

What can go wrong:
- Apache still serves the default site
- `.htaccess` rules do nothing

How to fix it:
- disable the default vhost if needed
- confirm `AllowOverride All`

### Step 11: Add HTTPS
Use Certbot:

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache
```

What can go wrong:
- DNS not pointing at the server yet
- cert issuance fails because port 80 is blocked

How to fix it:
- wait for DNS propagation
- verify OCI ingress + local firewall rules

### Step 12: Run smoke tests
Minimum checks:
1. home page
2. login
3. tournaments list
4. tournament detail
5. blog
6. gallery
7. membership application upload
8. admin login
9. one DB-backed write action

Use `docs/TESTING_CHECKLIST.md` for the full pass.

## Free Shared Hosting Fallback
Only use this if Oracle Cloud is not viable.

### When it makes sense
- you need something public quickly
- you accept tighter limits
- you do not want to manage a server

### Basic flow
1. Create the hosting account.
2. Create a MySQL database in the provider panel.
3. Upload the repo by FTP/file manager.
4. Import `dart_club.sql` with phpMyAdmin if the host allows it.
5. Create `services/config.local.php` with the host's DB values.
6. Set the PHP version high enough for the codebase.
7. Confirm `files/` is writable if the host allows permission management.

### What could go wrong
- SQL import too large for browser import
- provider blocks long requests or certain PHP functions
- file permissions cannot be adjusted enough for uploads
- free plan traffic/storage limits get hit

### How to fix it
- split the SQL import if needed
- remove nonessential seed/demo data before upload
- move to Oracle VM if uploads/admin flows are unstable

## Options You Should Not Choose For This Repo

### Netlify
Pros:
- very easy static deployment

Cons:
- wrong runtime model for PHP/MySQL
- backend breaks immediately

### Render or Railway free plans
Pros:
- modern deploy workflow

Cons:
- not reliably free long-term for a PHP app plus persistent MySQL
- more moving parts than this repo needs right now

Recommendation:
- only revisit these after a later containerization/refactor effort

## Recommended Final Decision
If you want the app to stay online long-term without rewriting it:
- choose **Oracle Cloud Always Free VM**

If Oracle account setup blocks you and you need something online the same day:
- use **free shared PHP hosting** as a temporary fallback

## First Deployment Checklist
1. push or archive the repo state you want to deploy
2. export the latest local DB
3. choose Oracle VM or free shared host
4. deploy code
5. add `services/config.local.php`
6. import DB
7. fix file permissions
8. enable HTTPS
9. run `docs/TESTING_CHECKLIST.md`
10. only then switch real traffic to the deployment
