# Bakery Cards - collectible card & loyalty manager

Plain PHP + MySQL + HTML/CSS/JS. Runs locally on XAMPP. Only the owner logs in; customers have no access.

## Folder structure
```
bakery/
  database.sql          import this in phpMyAdmin
  config.php            database login, timezone, phone-number option
  index.php login.php logout.php password.php
  dashboard.php         statistics + quick actions
  generate.php          create codes (optionally assign right away)
  assign.php            assign Available codes to a mobile number
  claim.php             claim a card (code + mobile number)
  search_customer.php   customer, codes, claim history, loyalty progress, redeem
  search_code.php       one code's status and dates, revoke button
  customers.php claims.php rewards.php redeem.php
  includes/             init, functions (all business rules), layout
  assets/css/style.css  assets/js/app.js
```

## Run it with XAMPP
1. Install XAMPP and open the XAMPP Control Panel. Start **Apache** and **MySQL**.
2. Copy the `bakery` folder into `C:\xampp\htdocs\` (Mac: `/Applications/XAMPP/htdocs/`).
3. Open http://localhost/phpmyadmin, click **Import**, choose `database.sql`, click **Import**.
   This creates the database `bakery_loyalty`, all tables, the reward setting and the default admin.
4. Open http://localhost/bakery/ and log in.
5. If your MySQL root user has a password, set it in `config.php` (XAMPP's default is empty, so no change is needed).
6. Set `APP_TIMEZONE` in `config.php` to your timezone (for example `Asia/Dhaka`), otherwise dates show in UTC.

## Default admin
Username `admin`, password `ChangeMe123!`. Change it straight away under **Password** in the menu.
Forgot it? In phpMyAdmin, table `admins`, replace `password_hash` with a hash from `<?php echo password_hash('NewPassword', PASSWORD_DEFAULT);`.

## Phone numbers
Numbers are stored as digits only, so `+1 (555) 123-4567` and `15551234567` are the same customer.
A number written with a leading 0 (`0171...`) and the same number with a country code (`880171...`) look different to the computer.
To treat them as one customer, set `DEFAULT_COUNTRY_CODE` in `config.php` (for example `'880'`) **before** adding customers.

## How to use it
**Generate.** Go to *Generate codes*, enter how many (1 to 200). Add the customer's mobile number to assign them immediately, or leave it empty. The codes appear in large type: write each on a card. Codes have 8 characters and never contain O, 0, I or 1.

**Assign.** Codes generated without a number are *Available*. In *Assign codes*, enter the customer's mobile number and the codes (click codes in the list to add them). They become *Assigned*. Only Available codes can be assigned. A new mobile number creates the customer automatically.

**Claim.** When a customer shows a card, open *Claim a card*, type the code and the customer's mobile number. The code is accepted only if it exists, is Assigned, is not Claimed or Revoked, and belongs to that mobile number. Then it becomes *Claimed*, the time is recorded, and the customer's claimed count goes up. Every N claimed cards (default 5) unlocks one reward.

**Rewards.** *Loyalty rewards* sets the number of claimed cards per reward and lists rewards waiting to be redeemed. Click **Redeem** when you hand the reward over. A customer's page shows progress toward the next reward.

**Revoke.** If a card is lost, open it in *Search unique code* and click *Revoke code*. A Claimed code cannot be revoked.

## How duplicates are prevented
* `codes.code` has a UNIQUE index; on a clash the generator picks another code and retries.
* `customers.mobile` has a UNIQUE index (normalised number).
* A claim runs in one transaction: the code row is locked (`SELECT ... FOR UPDATE`), the status change is `UPDATE ... WHERE status='Assigned'`, and `claims.code_id` is UNIQUE, so a second claim fails even if two requests arrive at the same instant.
* `customer_rewards` has UNIQUE (customer, milestone), so a milestone cannot be issued twice. Redeeming is an atomic `UPDATE ... WHERE redeemed_at IS NULL`.

## Notes
* Security: bcrypt password hashes, PHP sessions (id regenerated at login), CSRF token on every form, prepared statements everywhere, output escaped.
* Changing "cards per reward" does not remove rewards already issued. New rewards are issued on the customer's next claim.
* Back up the `bakery_loyalty` database regularly (phpMyAdmin > Export).
* Do not expose this site to the internet without HTTPS.
