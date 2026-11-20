<?php
// Database settings (XAMPP defaults)
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'bakery_loyalty');
define('DB_USER', 'root');
define('DB_PASS', '');

// Timezone used for all dates shown and stored. Examples: 'Asia/Dhaka', 'Europe/London', 'America/New_York'.
define('APP_TIMEZONE', 'UTC');

// Optional: country calling code (digits only, e.g. '880'). When set, local numbers that
// start with 0 (01712345678) are stored the same way as international ones (8801712345678),
// so one person never gets two customer records. Leave '' to store numbers as typed (digits only).
define('DEFAULT_COUNTRY_CODE', '');

define('CODE_LENGTH', 8);
define('MAX_CODES_PER_BATCH', 200);
define('PER_PAGE', 25);
