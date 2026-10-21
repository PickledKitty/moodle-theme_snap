#!/bin/bash
set -e

MOODLE_VERSION="MOODLE_503_STABLE"
MOODLE_DIR="/var/www/html"
PLUGIN_DIR="${MOODLE_DIR}/public/theme/snap"

# Node.js + Grunt (Moodle's toolchain) for AMD builds, stylelint and moodle-plugin-ci grunt.
# Set to false for a faster first build if you won't touch JavaScript.
INSTALL_GRUNT=true

# Generate demo courses/users so Snap's course pages and menus have content.
# Sizes: XS, S, M, L, XL, XXL (see admin/tool/generator). Empty to skip.
TEST_SITE_SIZE=XS

# In Codespaces the browser reaches Moodle through the HTTPS port-forwarding proxy.
if [ -n "${CODESPACE_NAME}" ] && [ -n "${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}" ]; then
    WWWROOT="https://${CODESPACE_NAME}-80.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"
    IN_CODESPACE=true
else
    WWWROOT="http://localhost:8080"
    IN_CODESPACE=false
fi

if [ ! -f "${PLUGIN_DIR}/version.php" ]; then
    echo "ERROR: Snap theme not found at ${PLUGIN_DIR} - check the volume mount in docker-compose.yml" >&2
    exit 1
fi

echo "=== Creating PHP error log ==="
# Written by both Apache (www-data) and CLI scripts (root).
touch /var/log/php_errors.log
chown www-data:www-data /var/log/php_errors.log
chmod 666 /var/log/php_errors.log

git config --global --add safe.directory ${MOODLE_DIR}
git config --global --add safe.directory ${PLUGIN_DIR}

echo "=== Cloning Moodle 5.3 ==="
if [ ! -f "${MOODLE_DIR}/public/version.php" ]; then
    git clone --depth=1 --branch=${MOODLE_VERSION} \
        https://github.com/moodle/moodle.git /tmp/moodle
    cp -a /tmp/moodle/. ${MOODLE_DIR}/
    rm -rf /tmp/moodle
fi

echo "=== Installing Composer and the Moodle coding standard ==="
export COMPOSER_HOME=/usr/local/share/composer
if ! command -v composer &>/dev/null; then
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi
if [ ! -x "${COMPOSER_HOME}/vendor/bin/phpcs" ]; then
    composer global config --no-interaction allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
    composer global require --no-interaction moodlehq/moodle-cs \
        || echo "WARNING: moodle-cs install failed; PHP CodeSniffer will not work" >&2
fi
for bin in phpcs phpcbf; do
    [ -x "${COMPOSER_HOME}/vendor/bin/${bin}" ] && ln -sf "${COMPOSER_HOME}/vendor/bin/${bin}" /usr/local/bin/${bin}
done

echo "=== Installing moodle-plugin-ci (run the same checks as CI) ==="
if [ ! -x /opt/moodle-plugin-ci/bin/moodle-plugin-ci ]; then
    composer create-project --no-interaction --no-dev --prefer-dist \
        moodlehq/moodle-plugin-ci /opt/moodle-plugin-ci ^4 \
        || echo "WARNING: moodle-plugin-ci install failed" >&2
fi
[ -x /opt/moodle-plugin-ci/bin/moodle-plugin-ci ] \
    && ln -sf /opt/moodle-plugin-ci/bin/moodle-plugin-ci /usr/local/bin/moodle-plugin-ci

echo "=== Waiting for PostgreSQL ==="
until php -r "
    \$sock = @fsockopen('db', 5432, \$errno, \$errstr, 3);
    if (\$sock) { fclose(\$sock); exit(0); } else { exit(1); }
" 2>/dev/null; do
    echo "-- Waiting for database..."
    sleep 2
done
echo "-- Database is ready"

FRESH_INSTALL=false
if [ ! -f "${MOODLE_DIR}/config.php" ]; then
    FRESH_INSTALL=true
    echo "=== Installing Moodle via CLI (wwwroot: ${WWWROOT}) ==="
    php ${MOODLE_DIR}/admin/cli/install.php \
        --wwwroot="${WWWROOT}" \
        --dataroot=/var/www/moodledata \
        --dbtype=pgsql \
        --dbhost=db \
        --dbname=moodle \
        --dbuser=moodle \
        --dbpass=moodle \
        --fullname="Moodle Dev" \
        --shortname="dev" \
        --adminuser=admin \
        --adminpass=Admin1234! \
        --adminemail=admin@example.com \
        --non-interactive \
        --agree-license

    echo "=== Adding dev settings to Moodle config.php ==="
    # Must go before the require_once of lib/setup.php, or it is applied too late.
    cat > /tmp/devconfig.php << 'EOF'
// Developer settings
@error_reporting(E_ALL);
@ini_set('display_errors', '1');
$CFG->debug = E_ALL; // DEBUG_DEVELOPER (constant not defined yet here).
$CFG->debugdisplay = 1;
$CFG->debugsmtp = true;
$CFG->smtphosts = 'mailpit:1025';
$CFG->noreplyaddress = 'noreply@example.com';

// Theme development: SCSS, JS, templates and strings update on reload, no cache purge needed.
// Expect slower page loads. Comment these out to check real-world performance.
$CFG->themedesignermode = true;
$CFG->cachejs = false;
$CFG->cachetemplates = false;
$CFG->langstringcache = false;

// Password for users created by the test site generator (tool_generator_000001 etc.).
$CFG->tool_generator_users_password = 'Test1234!';

// PHPUnit settings
$CFG->phpunit_prefix = 'phpu_';
$CFG->phpunit_dataroot = '/var/www/phpunitdata';

// Behat settings
$CFG->behat_prefix = 'behat_';
$CFG->behat_dataroot = '/var/www/behatdata';
$CFG->behat_wwwroot = 'http://moodle';
$CFG->behat_faildump_path = '/var/www/behatfaildumps';
$CFG->behat_selenium_url = 'http://selenium:4444/wd/hub';

EOF
    if [ "${IN_CODESPACE}" = "true" ]; then
        cat >> /tmp/devconfig.php << 'EOF'
// Codespaces: web requests arrive via an HTTPS proxy. Skip for CLI and for
// Behat requests from Selenium (host "moodle"), which use plain HTTP.
if (PHP_SAPI !== 'cli' && ($_SERVER['HTTP_HOST'] ?? '') !== 'moodle') {
    $CFG->reverseproxy = true;
    $CFG->sslproxy = true;
}

EOF
    fi
    awk -v f=/tmp/devconfig.php '
        /require_once.*lib\/setup\.php/ && !done { while ((getline line < f) > 0) print line; done = 1 }
        { print }
    ' ${MOODLE_DIR}/config.php > /tmp/config.php
    if ! grep -q "phpunit_prefix" /tmp/config.php; then
        echo "ERROR: could not find the lib/setup.php line in config.php" >&2
        exit 1
    fi
    mv /tmp/config.php ${MOODLE_DIR}/config.php
    rm -f /tmp/devconfig.php
fi

echo "=== Registering and upgrading all plugins ==="
php ${MOODLE_DIR}/admin/cli/upgrade.php --non-interactive

echo "=== Activating Snap theme ==="
php ${MOODLE_DIR}/admin/cli/cfg.php --name=theme --set=snap

echo "=== Applying custom menu items ==="
# Site administration > Appearance > Advanced theme settings > Custom menu items.
# Re-applied on every rebuild; edits made in the admin UI are overwritten.
# To use your own menu, put it in .devcontainer/custommenuitems.local (gitignored).
CUSTOMMENU_LOCAL="${PLUGIN_DIR}/.devcontainer/custommenuitems.local"
if [ -f "${CUSTOMMENU_LOCAL}" ]; then
    CUSTOMMENUITEMS=$(cat "${CUSTOMMENU_LOCAL}")
else
    # Example menu: icons, section headings (no URL), dividers (##) and tooltips.
    CUSTOMMENUITEMS=$(cat << 'EOF'
<i class="fa fa-user"></i> <span class="custom-menu-text">Student Links</span>
-STUDENT LINKS
-##
-Student Services|https://example.com/services
-Student Support|https://example.com/support|Help with study, wellbeing and more
-##
-Enrolment Information|https://example.com/enrolment
-Transcripts & Awards|https://example.com/transcripts
<img src="/theme/snap/pix/library.svg" class="svg-icon icon library invert" alt="library icon"><span class="custom-menu-text">Library</span>
-LIBRARY LINKS
-##
-Library catalogue|https://example.com/library|Search books, journals and databases
-##
-REFERENCING
-##
-<i class="fa fa-arrow-right fa-fw"></i> Referencing guide|https://example.com/referencing
<i class="fa fa-question-circle-o"></i> <span class="custom-menu-text">Help</span>
-HELP
-##
-Getting started|https://example.com/help
-Contact support|https://example.com/contact
EOF
)
fi
php ${MOODLE_DIR}/admin/cli/cfg.php --name=custommenuitems --set="${CUSTOMMENUITEMS}"

echo "=== Initialising PHPUnit ==="
XDEBUG_MODE=off php ${MOODLE_DIR}/public/admin/tool/phpunit/cli/init.php

echo "=== Initialising Behat ==="
# -a also runs core feature files under Snap, to catch theme regressions on core pages.
XDEBUG_MODE=off php ${MOODLE_DIR}/public/admin/tool/behat/cli/init.php --add-core-features-to-theme
# Note: behat config path is /var/www/behatdata/behatrun/behat/behat.yml

if [ "${INSTALL_GRUNT}" = "true" ]; then
    echo "=== Installing Node.js and Grunt for JS/CSS builds ==="
    # Moodle 5.3's .nvmrc is lts/jod (Node 22).
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y nodejs
    npm install -g grunt-cli
    cd ${MOODLE_DIR} && npm install
fi

if [ "${FRESH_INSTALL}" = "true" ] && [ -n "${TEST_SITE_SIZE}" ]; then
    echo "=== Generating ${TEST_SITE_SIZE} test site content ==="
    XDEBUG_MODE=off php ${MOODLE_DIR}/public/admin/tool/generator/cli/maketestsite.php \
        --size=${TEST_SITE_SIZE} --fixeddataset --quiet \
        || echo "WARNING: test site generation failed" >&2
fi

echo "=== Fixing ownership for Apache (www-data) ==="
# The setup steps above ran as root. Skip the bind-mounted theme repo.
find ${MOODLE_DIR} -path ${PLUGIN_DIR} -prune -o -exec chown www-data:www-data {} +
chown -R www-data:www-data /var/www/moodledata /var/www/phpunitdata /var/www/behatdata /var/www/behatfaildumps

echo "=== Setup complete! Moodle is at ${WWWROOT} (admin / Admin1234!) ==="
echo "    Test users: tool_generator_000001... / Test1234!  Cron log: /var/log/moodle-cron.log"
