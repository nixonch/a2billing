
============================
A2BILLING INSTALLATION GUIDE
============================


0. Synopsis
-----------

    A2Billing 3.0 (nixonch) is VoIP billing software licensed under the AGPL 3.
    Copyright (C) 2004-2011 - Star2billing S.L. http://www.star2billing.com/

    This guide covers a fresh installation with Apache, PHP, MySQL 8.x and Asterisk on the same server.

    Examples of current distributions include Debian 13 (trixie), Ubuntu 24.04 LTS and 26.04 LTS, and Fedora Linux 44. Package names and paths depend on the distribution.


1. A2Billing installation guide
-------------------------------

1.1 Installation layout
~~~~~~~~~~~~~~~~~~~~~~~

    Run the commands with sudo access. Choose only the commands for your distribution and keep the same shell session for the path variables.
    The examples use /usr/local/src/a2billing for the source tree, billing for source ownership and cron, and asterisk:asterisk for Asterisk and the PHP callback service.
    The default PHP web-service account is apache on Fedora and www-data on Debian/Ubuntu; adjust it for a custom PHP-FPM pool.
    Asterisk's AGI and sound directories come from astagidir and astdatadir/sounds in /etc/asterisk/asterisk.conf.
    Set the Asterisk executable and directory variables once in section 1.4; use the actual binary path if installed from source.
    Replace admin.example.com and customer.example.com with your DNS names. Section 2.6 uses certificates stored under /etc/a2billing/tls/.

1.2 Default passwords and access info
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    Use these values consistently in the database, application and Asterisk configuration:
        * MySQL administrator password: use the credentials configured for your MySQL server
        * The database script defaults to mya2billing / a2billinguser / a2billing; replace its password before import
        * The Asterisk Manager example uses myasterisk; choose one secret and use it in both Asterisk and A2Billing
        * A2Billing admin default password is: user: root password: changepassword


1.3 Pre-required software packages
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    A2Billing requires Apache, PHP with a command-line interpreter and the extensions listed below.
    These commands install the distribution's default PHP version; check compatibility with the application and its Composer dependencies before deployment.

    Debian / Ubuntu (enable the universe repository on Ubuntu for php-mcrypt):
        - sudo apt-get update
        - sudo apt-get install apache2 libapache2-mod-php php-cli php-common php-mysql php-gd php-mbstring php-xml php-curl php-mcrypt git composer ca-certificates cron
        - sudo systemctl enable --now apache2

    Fedora Linux:
        - sudo dnf install httpd mod_ssl php php-fpm php-cli php-common php-mysqlnd php-gd php-mbstring php-xml php-process php-pecl-mcrypt git composer ca-certificates cronie policycoreutils-python-utils
        - sudo systemctl enable --now php-fpm httpd

    Fedora uses PHP-FPM to run PHP through Apache; php-process provides the pcntl and POSIX extensions used by AGI and callback scripts.
    The application still uses mcrypt functions, so the separately packaged MCrypt extension is included above.

    Install MySQL 8.0 or 8.4 LTS and the mysql command-line client using packages appropriate for your operating system.
    The PHP mysqli driver must support the MySQL server's authentication method.
    Enable the installed MySQL service:
        - sudo systemctl enable --now mysql.service  # Debian / Ubuntu
        - sudo systemctl enable --now mysqld.service  # Fedora

    Install Asterisk before section 1.4. Fedora and Ubuntu provide an asterisk package:
        - sudo dnf install asterisk  # Fedora
        - sudo apt-get install asterisk  # Ubuntu, with universe enabled

    Debian 13 requires a separately installed Asterisk build or package source. Use that installation's paths and service name throughout this guide.

    1.3.1 Google Cloud speech services and local IVR monitoring

        Install SoX and LAME if using conversation recognition or audio email attachments:
            - sudo dnf install sox lame  # Fedora
            - sudo apt-get install sox lame  # Debian / Ubuntu

        Configure the following Google settings through the administration interface after completing sections 2.1-2.6.

        Google Cloud Text-to-Speech: generated greetings

        Enable billing and the Cloud Text-to-Speech API in your Google Cloud project, then configure an API key for that API.
        Set global.google_tts_key (Google Cloud Text-to-Speech API Key); its installation default is empty.
        The web-server account must be able to create customer subdirectories and write files under webui.dir_store_audio
        (default /var/lib/asterisk/sounds/a2billing); Asterisk must be able to read the generated greetings.

        Google Cloud Speech-to-Text: recognize conversations and send results by email

        Enable billing and the Cloud Speech-to-Text API; enable Cloud Storage for recordings of 60 seconds or longer.
        Set global.google_cloud_credential (Google Cloud Credentials Path) to the service-account JSON file; the default is /etc/credentials.json.
        Keep this file outside the web document root and readable by the PHP callback service account, with permissions such as 0640.
        Grant the service account access to speech recognition, bucket metadata and object upload, read and deletion.
        Set global.google_storage_bucketname (default wav_folder) to your own globally unique bucket name.
        Set global.bucket_location (default eu) to the desired location. Create the bucket beforehand or grant storage.buckets.create as well.
        Install and start the PHP callback service from section 2.9, even if telephone callbacks are unused.
        Configure email delivery in the global SMTP settings. Set the customer's FORWARD TO EMAIL (speech2mail), enable SEND TEXT (send_text),
        and optionally SEND SOUND (send_sound).

        The current recording code requires Asterisk Monitor/StopMonitor (res_monitor) and WAV support (format_wav).
        Check availability before enabling SEND TEXT:
            - sudo "$A2B_ASTERISK" -rx 'core show application Monitor'
            - sudo "$A2B_ASTERISK" -rx 'core show application StopMonitor'
            - sudo "$A2B_ASTERISK" -rx 'module show like format_wav'

        Asterisk 21 and later removed res_monitor. On those versions, this recording code must first be adapted to MixMonitor
        with separate receive/transmit files before enabling SEND TEXT.
        After setup, test both a short and a longer-than-one-minute conversation and verify email delivery and queue processing.

        Local text-to-speech for IVR system monitoring

        Install the local synthesizer only if using this IVR feature:
            - sudo dnf install espeak-ng sox  # Fedora
            - sudo apt-get install espeak-ng-espeak sox  # Debian / Ubuntu

        On Fedora, set the following in /etc/asterisk/phpagi.conf so PHPAGI finds the espeak-ng executable:
            [espeak]
            espeak=/usr/bin/espeak-ng

        Debian/Ubuntu's espeak-ng-espeak package provides the espeak compatibility command automatically.
        Enable the monitoring extension as described in section 2.10. Google credentials are not needed for local monitoring.

        References:
            - https://docs.cloud.google.com/text-to-speech/docs/get-started
            - https://docs.cloud.google.com/speech-to-text/docs/v1/authentication
            - https://docs.cloud.google.com/storage/docs/buckets
            - https://docs.asterisk.org/Asterisk_21_Documentation/WhatsNew/

1.4 Shared users, directories and permissions
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    Keep the Asterisk package's existing account. For an installation from source, create the group and account if missing:
        - getent group asterisk >/dev/null || sudo groupadd --system asterisk
        - id -u asterisk >/dev/null 2>&1 || sudo useradd --system --gid asterisk --home-dir /var/lib/asterisk --shell /usr/sbin/nologin asterisk

    Create billing once and add both application accounts to the shared group:
        - id -u billing >/dev/null 2>&1 || sudo useradd --system --user-group --create-home --home-dir /var/lib/a2billing --shell /usr/sbin/nologin billing
        - A2B_WEB_USER=apache  # Fedora: choose this assignment only
        - A2B_WEB_USER=www-data  # Debian / Ubuntu: choose this assignment only
        - sudo usermod -aG asterisk billing
        - sudo usermod -aG asterisk "$A2B_WEB_USER"

    Start Asterisk, check its active paths, then set these variables to match your installation:
        - A2B_ASTERISK=/usr/sbin/asterisk
        - sudo systemctl enable --now asterisk.service
        - sudo "$A2B_ASTERISK" -rx 'core show settings'
        - A2B_AGI_DIR=/var/lib/asterisk/agi-bin
        - A2B_SOUND_DIR=/var/lib/asterisk/sounds

    Use /usr/share/asterisk/sounds instead if that is the configured sound directory.
    Prepare shared application directories:
        - sudo install -d -o root -g asterisk -m 0755 "$A2B_AGI_DIR"
        - sudo install -d -o billing -g asterisk -m 2770 /var/log/a2billing
        - sudo install -d -o asterisk -g asterisk -m 2770 /var/spool/asterisk/monitor /var/lib/asterisk/sounds/a2billing /var/lib/asterisk/mohmp3
        - sudo install -d -o asterisk -g asterisk -m 0750 /var/spool/asterisk/tmp
        - sudo install -d -o root -g asterisk -m 0750 /var/lib/a2billing/script
        - sudo install -d -o root -g root -m 0700 /etc/a2billing/tls

    Create /etc/tmpfiles.d/a2billing.conf for the shared PID directory:
        - sudoedit /etc/tmpfiles.d/a2billing.conf

        d /run/a2billing 0770 billing asterisk -

    Apply it now; systemd will recreate the directory after reboot:
        - sudo systemd-tmpfiles --create /etc/tmpfiles.d/a2billing.conf



2. Installation
~~~~~~~~~~~~~~~

    Complete the component and system preparation in sections 1.3 and 1.4 before these steps.
    Sections 2.1-2.7 install the core application; apply optional feature settings after that and enable sections 2.8-2.11 as needed.

    2.1. Step 1: Clone the source code from GitHub

        Clone into an empty directory owned by billing:
            - sudo install -d -o billing -g asterisk -m 0755 /usr/local/src/a2billing
            - sudo -u billing git clone https://github.com/nixonch/a2billing.git /usr/local/src/a2billing

        Validate and install the PHP dependencies before configuring the application:
            - sudo -u billing composer --working-dir=/usr/local/src/a2billing/common/lib validate --no-check-publish
            - sudo -u billing composer --working-dir=/usr/local/src/a2billing/common/lib install --no-dev
            - sudo -u billing composer --working-dir=/usr/local/src/a2billing/common/lib check-platform-reqs --no-dev

        Resolve validation or PHP compatibility errors before continuing. Use a matching dependency set; do not bypass platform requirements.
        Keep the repository's library and asset symbolic links intact.


    2.2. Step 2: Prepare the Database

        This is a fresh database installation for A2Billing 3.0 (nixonch), using MySQL 8.0 or 8.4 LTS.

        Edit the database creation script to set the database name, application username and password:
            - sudoedit /usr/local/src/a2billing/DataBase/mysql-8.x/a2billing-createdb-user.sql

        The supplied defaults are mya2billing, a2billinguser and a2billing; choose your own password before running the file.

        Create the database and application account as a MySQL administrator:

            - sudo mysql -u root -p < /usr/local/src/a2billing/DataBase/mysql-8.x/a2billing-createdb-user.sql

        The application account is created for localhost. Adjust its host in the SQL file if the application connects from another server.

        Install the complete schema and initial data into the empty database:

            - sudo bash /usr/local/src/a2billing/DataBase/mysql-8.x/install-db.sh

        Enter the database name, hostname, MySQL administrator username and password when prompted.
        The installer imports a2billing-install.sql, rejects a nonempty database and stops at the first SQL error.

        Check the installed version, substituting your database name if changed:
            - sudo mysql -u root -p mya2billing --execute='SELECT version FROM cc_version;'

        Expected database version: 3.0 (nixonch).


    2.3. Step 3: Edit the a2billing.conf configuration file

        Install the configuration with billing ownership and read access for the shared asterisk group:
            - sudo install -o billing -g asterisk -m 0640 /usr/local/src/a2billing/a2billing.conf /etc/a2billing.conf
            - sudoedit /etc/a2billing.conf

        Set the database connection information using the application account created in Step 2, an example follows:
            [database]
            hostname = localhost
            port = 3306
            user = a2billinguser
            password = YOUR_DATABASE_PASSWORD
            dbname = mya2billing
            dbtype = mysql

        Use the database name, application username and password chosen in Step 2.
        Set the callback PID path in the same file:
            [daemon-info]
            pidfile = /run/a2billing/a2b-callback-daemon.pid


    2.4. Step 4: Configure Asterisk

        2.4.1. SIP and IAX configuration (optional)

            Configure endpoints and trunks for the channel drivers installed in Asterisk.
            If using A2Billing's file export for chan_sip or chan_iax2, prepare only the corresponding file:
                - sudo touch /etc/asterisk/additional_a2billing_sip.conf  # chan_sip
                - sudo chown "$A2B_WEB_USER":asterisk /etc/asterisk/additional_a2billing_sip.conf
                - sudo chmod 0660 /etc/asterisk/additional_a2billing_sip.conf

            Add this line to /etc/asterisk/sip.conf for chan_sip:
                #include additional_a2billing_sip.conf

            For chan_iax2, use additional_a2billing_iax.conf instead and include it from /etc/asterisk/iax.conf.
            Keep webui.buddy_sip_file and webui.buddy_iax_file consistent with the files you use.
            On Asterisk 21 and later, configure PJSIP separately: the bundled SIP export uses chan_sip syntax and cannot generate pjsip.conf.

        2.4.2. Sound files

            Copy the required prompts to the sound directory selected in section 1.4:
                - sudo install -d -o root -g asterisk -m 0755 "$A2B_SOUND_DIR/en"
                - sudo cp -R /usr/local/src/a2billing/addons/sounds/en/. "$A2B_SOUND_DIR/en/"

            Repeat for each required language and select the matching language in the customer's account or AGI profile.

        2.4.3. Asterisk Manager

            Edit /etc/asterisk/manager.conf, replacing the example secret:
                - sudoedit /etc/asterisk/manager.conf

                [general]
                enabled = yes
                port = 5038
                bindaddr = 127.0.0.1

                [myasterisk]
                secret = CHANGE_THIS_SECRET
                deny = 0.0.0.0/0.0.0.0
                permit = 127.0.0.1/255.255.255.255
                read = system,call,user,dialplan,reporting
                write = system,call,command,originate,reporting

            After opening the web interface in section 2.6, set the same host, username and secret in its global Manager settings
            and the server-manager entry used for callbacks.
            For an application on another host, adjust bindaddr and permit to the required addresses.
            Apply the configuration:
                - sudo "$A2B_ASTERISK" -rx 'manager reload'


    2.5. Step 5: Install the AGI components

        Using A2B_AGI_DIR from section 1.4, make the scripts executable and link them into Asterisk's AGI directory:
            - sudo chmod +x /usr/local/src/a2billing/AGI/a2billing.php /usr/local/src/a2billing/AGI/a2billing_monitoring.php
            - sudo ln -s /usr/local/src/a2billing/AGI/a2billing.php "$A2B_AGI_DIR/a2billing.php"
            - sudo ln -s /usr/local/src/a2billing/AGI/a2billing_monitoring.php "$A2B_AGI_DIR/a2billing_monitoring.php"

        The asterisk account must be able to read the application, its libraries and /etc/a2billing.conf.
        Check the CLI installation:
            - sudo -u asterisk /usr/bin/php "$A2B_AGI_DIR/a2billing.php" --version


    2.6. Step 6: Install the web interfaces

        Keep admin, customer and common in the source tree with their existing symbolic links.
        Give the PHP web-service account write access to the template compilation directories:
            - sudo chown -R "$A2B_WEB_USER":asterisk /usr/local/src/a2billing/admin/templates_c /usr/local/src/a2billing/customer/templates_c
            - sudo chmod 0750 /usr/local/src/a2billing/admin/templates_c /usr/local/src/a2billing/customer/templates_c

        Obtain certificates for both hostnames and place their full chains and private keys at the paths below.
        Keep private keys readable only by root. Configure two HTTPS sites, replacing the example hostnames with your DNS names.
        On Fedora, create /etc/httpd/conf.d/a2billing.conf. On Debian/Ubuntu, create /etc/apache2/sites-available/a2billing.conf:

            <VirtualHost *:443>
                ServerName admin.example.com
                DocumentRoot /usr/local/src/a2billing/admin
                SSLEngine on
                SSLCertificateFile /etc/a2billing/tls/admin-fullchain.pem
                SSLCertificateKeyFile /etc/a2billing/tls/admin-key.pem
            </VirtualHost>

            <VirtualHost *:443>
                ServerName customer.example.com
                DocumentRoot /usr/local/src/a2billing/customer
                SSLEngine on
                SSLCertificateFile /etc/a2billing/tls/customer-fullchain.pem
                SSLCertificateKeyFile /etc/a2billing/tls/customer-key.pem
            </VirtualHost>

            <Directory /usr/local/src/a2billing>
                Options -Indexes +FollowSymLinks -MultiViews
                AllowOverride FileInfo
                DirectoryIndex index.php
                Require all granted
            </Directory>

            <DirectoryMatch "^/usr/local/src/a2billing/(common/lib|admin/(lib|templates_c)|customer/(lib|templates_c))(/|$)">
                AllowOverride None
                Require all denied
            </DirectoryMatch>

        On Fedora with SELinux enabled, label the web files and template compilation directories:
            - sudo semanage fcontext -a -t httpd_sys_content_t '/usr/local/src/a2billing/(admin|customer|common)(/.*)?'
            - sudo semanage fcontext -a -t httpd_sys_rw_content_t '/usr/local/src/a2billing/(admin|customer)/templates_c(/.*)?'
            - sudo semanage fcontext -a -t cert_t '/etc/a2billing/tls(/.*)?'
            - sudo restorecon -R /usr/local/src/a2billing/admin /usr/local/src/a2billing/customer /usr/local/src/a2billing/common /etc/a2billing/tls
            - sudo setsebool -P httpd_can_network_connect on

        Allow the web service to write the configured shared log and upload directories in your SELinux policy as well.
        Enable the configuration and apply the web-service account's group membership:

        Fedora:
            - sudo apachectl configtest
            - sudo systemctl restart php-fpm httpd

        Debian / Ubuntu:
            - sudo a2enmod ssl rewrite
            - sudo a2ensite a2billing.conf
            - sudo apache2ctl configtest
            - sudo systemctl restart apache2

        Open https://admin.example.com/Public/index.php, sign in as root with changepassword and change the password.
        The customer interface is at https://customer.example.com/. Set the application's website URLs to these hostnames and its customer URL paths to /.
        Complete the Manager settings from section 2.4.3. Match webui.monitor_path, webui.dir_store_audio and webui.dir_store_mohmp3
        to the shared directories from section 1.4.


    2.7. Step 7: Configure the dialplan

        In the administration settings, select asterisk_version = 1_16 in global and the active agi-confN profile (the latest available compatibility setting).
        Replace 1 in the examples with that profile's number, and add the required contexts to /etc/asterisk/extensions.conf:

            [a2billing]
            exten => _X.,1,AGI(a2billing.php,1)
            same => n,Hangup()

            [a2billing_did]
            exten => _X.,1,AGI(a2billing.php,1,did)
            same => n,Hangup()

            [a2billing-callback]
            exten => _X.,1,AGI(a2billing.php,1,callback)
            same => n,Hangup()

            [a2billing_voucher]
            exten => 8800,1,Answer()
            same => n,AGI(a2billing.php,1,voucher)
            same => n,Hangup()

        Set customer endpoints to context a2billing and inbound DID trunks to a2billing_did, adjusting the DID pattern to the number delivered by the provider.
        Match callback.context_callback to a2billing-callback when using the PHP daemon.
        For voucher refills, add this extension under [a2billing]:
            exten => 8800,1,Goto(a2billing_voucher,8800,1)

        Enable the separate monitoring context only as described in section 2.10.
        Configure an active account, tariff plan and outgoing trunk in A2Billing, matching the installed Asterisk channel driver.
        Reload and check the dialplan:
            - sudo "$A2B_ASTERISK" -rx 'dialplan reload'
            - sudo "$A2B_ASTERISK" -rx 'dialplan show a2billing'

        Make a test outgoing call and, if applicable, a DID or callback call.


    2.8. Step 8: Configure recurring services

        Enable the cron service installed in section 1.3:

        Fedora:
            - sudo systemctl enable --now crond.service

        Debian / Ubuntu:
            - sudo systemctl enable --now cron.service

        Edit the billing user's crontab:
            - sudo crontab -u billing -e

        Add the required jobs without a username field. Adjust paths and schedules to your installation; uncomment optional jobs only when needed.

            SHELL=/bin/sh
            PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

            MAILTO=""

            # Monthly subscription processing
            0 6 1 * * cd /usr/local/src/a2billing/common/lib && /usr/bin/php /usr/local/src/a2billing/Cronjobs/a2billing_subscription_fee.php >> /var/log/a2billing/cron.log 2>&1
            # Hourly low-balance notifications
            0 * * * * cd /usr/local/src/a2billing/common/lib && /usr/bin/php /usr/local/src/a2billing/Cronjobs/a2billing_notify_account.php >> /var/log/a2billing/cron.log 2>&1
            # Optional DID charges and expiry handling
            # 15 5 * * * cd /usr/local/src/a2billing/common/lib && /usr/bin/php /usr/local/src/a2billing/Cronjobs/a2billing_bill_diduse.php >> /var/log/a2billing/cron.log 2>&1
            # Optional recurring-service processing
            # 0 12 * * * cd /usr/local/src/a2billing/common/lib && /usr/bin/php /usr/local/src/a2billing/Cronjobs/a2billing_batch_process.php >> /var/log/a2billing/cron.log 2>&1
            # Daily invoice processing
            0 6 * * * cd /usr/local/src/a2billing/common/lib && /usr/bin/php /usr/local/src/a2billing/Cronjobs/a2billing_batch_billing.php >> /var/log/a2billing/cron.log 2>&1
            # Hourly alarms
            0 * * * * cd /usr/local/src/a2billing/common/lib && /usr/bin/php /usr/local/src/a2billing/Cronjobs/a2billing_alarm.php >> /var/log/a2billing/cron.log 2>&1
            # Optional campaign autodialer
            # */5 * * * * cd /usr/local/src/a2billing/common/lib && /usr/bin/php /usr/local/src/a2billing/Cronjobs/a2billing_batch_autodialer.php >> /var/log/a2billing/cron.log 2>&1

        Save the crontab and check the installed entries:
            - sudo crontab -u billing -l

        cron applies the saved entries automatically. Schedules use the server's local time; output is written to /var/log/a2billing/cron.log.
        For email notifications, enable notifications.cron_notifications and the customer's notification setting.


    2.9. Step 9: PHP callback daemon with systemd

        Enable this service for callbacks, bulk dialing or conversation recognition after completing sections 1.4 and 2.1-2.7.
        For speech recognition, complete section 1.3.1 and give asterisk read access to the Google JSON credentials.

        Stop any previous callback instance before starting this service.
        Create /etc/systemd/system/a2b-callback-daemon-php.service:
            - sudoedit /etc/systemd/system/a2b-callback-daemon-php.service

            [Unit]
            Description=A2Billing PHP callback daemon
            Wants=network-online.target
            After=network-online.target asterisk.service
            Requires=asterisk.service

            [Service]
            Type=simple
            User=asterisk
            Group=asterisk
            WorkingDirectory=/usr/local/src/a2billing/CallBack/callback-daemon-php
            ExecStart=/usr/bin/php -d include_path=.:/usr/local/src/a2billing/common/lib /usr/local/src/a2billing/CallBack/callback-daemon-php/callback_daemon.php
            Restart=always
            RestartSec=5
            KillMode=control-group
            PrivateTmp=true

            [Install]
            WantedBy=multi-user.target

        Adjust the paths and Asterisk unit name if necessary. If Asterisk is remote or only speech recognition is needed,
        remove Requires=asterisk.service and the asterisk.service entry from After=.

        Validate, enable at boot and start the service (same commands on Fedora and Debian/Ubuntu):
            - sudo systemd-analyze verify /etc/systemd/system/a2b-callback-daemon-php.service
            - sudo systemctl daemon-reload
            - sudo systemctl enable --now a2b-callback-daemon-php.service
            - sudo systemctl status a2b-callback-daemon-php.service
            - sudo journalctl -u a2b-callback-daemon-php.service -n 50 --no-pager

        After changing the application configuration, restart the daemon:
            - sudo systemctl restart a2b-callback-daemon-php.service


    2.10. Step 10: Enable IVR system monitoring (optional)

        Complete local text-to-speech setup in section 1.3.1 and prompt installation in section 2.4.2.

        In the administration interface, open Maintenance -> Monitoring and create an enabled task.
        Set its dial code, introduction text, SQL query or shell script, and the format used to read the result.
        For shell tasks, copy scripts to /var/lib/a2billing/script/, give asterisk read and execute access, and enter only the filename in the task settings.

        Add the following context to /etc/asterisk/extensions.conf, adjusting the extension and configuration number as needed:

            [a2billing_monitoring]
            exten => 8900,1,Answer()
            same => n,AGI(a2billing_monitoring.php,1)
            same => n,Hangup()

        Add this dedicated extension to the authorized internal users' context, including [a2billing] if used:
            exten => 8900,1,Goto(a2billing_monitoring,8900,1)

        Reload the dialplan:
            - sudo "$A2B_ASTERISK" -rx 'dialplan reload'

        Call 8900 and enter the dial code of an enabled monitoring task.

    2.11. Step 11: Enable account information and PIN locking via IVR (optional)

        Use the voice prompts installed in section 2.4.2.
        In the administration interface, open the agi-confN profile used by your a2billing.php dialplan call and enable either or both options:

            ivr_enable_account_information = 1
            ivr_enable_locking_option = 1

        Both options are disabled by default. Save the settings and make a new call through the calling-card IVR.
        After account authentication, press 4 for account information or 5 to set a locking PIN and confirm the lock.
        With PIN locking enabled, subsequent calls using the locked account require that PIN.


3. Support
----------

    Installation, configuration and support for A2Billing 3.0 (nixonch) are provided by this version's maintainer:
    https://github.com/nixonch/a2billing
