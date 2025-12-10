# Laravel Queueing System - Ubuntu LAMP Deployment Guide

This guide covers deploying the Laravel queueing system on Ubuntu Server with LAMP stack in `/var/www/html/`.

## Prerequisites

- Ubuntu Server with LAMP stack installed
- PHP 8.1+ with required extensions
- Composer installed
- Node.js and npm installed
- MySQL/MariaDB running
- Git installed
- Root or sudo access

## Step 1: Clone the Repository

```bash
cd /var/www/html/
sudo git clone https://github.com/morales-lc/queueing_system.git
cd queueing_system
```

## Step 2: Set Proper Permissions

```bash
# Set ownership to web server user (usually www-data on Ubuntu)
sudo chown -R www-data:www-data /var/www/html/queueing_system

# Set directory permissions
sudo find /var/www/html/queueing_system -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/html/queueing_system -type f -exec chmod 644 {} \;

# Set specific permissions for storage and bootstrap/cache
sudo chmod -R 775 /var/www/html/queueing_system/storage
sudo chmod -R 775 /var/www/html/queueing_system/bootstrap/cache
```

## Step 3: Install PHP Dependencies

```bash
cd /var/www/html/queueing_system
sudo -u www-data composer install --optimize-autoloader --no-dev
```

If you encounter permission issues, you can run:
```bash
sudo composer install --optimize-autoloader --no-dev
sudo chown -R www-data:www-data vendor/
```

## Step 4: Install Node.js Dependencies and Build Assets

```bash
npm install
npm run build
```

## Step 5: Configure Environment File

```bash
# Copy the example environment file
sudo cp .env.example .env

# Edit the environment file
sudo nano .env
```

Update the following settings in `.env`:

```ini
APP_NAME="Lourdes College Queueing System"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server-ip-or-domain

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=queueing_system
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Broadcasting with Reverb
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=524686
REVERB_APP_KEY=gvsglhliylmqajmutw3z
REVERB_APP_SECRET=fnntu0hx7ndqkexpybfd
REVERB_HOST="0.0.0.0"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="your-server-ip-or-domain"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Printer settings (set to false for production unless you have receipt printer)
PRINTER_ENABLED=false

# If printer enabled, set print server URL (Windows PC with printer)
# PRINT_SERVER_URL=http://192.168.0.95:3000
```

**Important**: For receipt printing setup, see [PRINTING_SETUP.md](PRINTING_SETUP.md) for complete instructions on configuring the Windows print server.

## Step 6: Generate Application Key

```bash
sudo php artisan key:generate
```

## Step 7: Create Database

```bash
# Login to MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE queueing_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'queueing_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON queueing_system.* TO 'queueing_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Update your `.env` file with the database credentials you just created.

## Step 8: Run Migrations and Seeders

```bash
sudo php artisan migrate --force
sudo php artisan db:seed --force
```

## Step 9: Create Storage Link

```bash
sudo php artisan storage:link
```

## Step 10: Configure Apache Virtual Host

Create a new Apache configuration file:

```bash
sudo nano /etc/apache2/sites-available/queueing_system.conf
```

Add the following configuration:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    ServerAdmin admin@your-domain.com
    
    DocumentRoot /var/www/html/queueing_system/public
    
    <Directory /var/www/html/queueing_system/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    <Directory /var/www/html/queueing_system>
        Options -Indexes
        Require all denied
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/queueing_system-error.log
    CustomLog ${APACHE_LOG_DIR}/queueing_system-access.log combined
</VirtualHost>
```

## Step 11: Enable Apache Modules and Site

```bash
# Enable required Apache modules
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod ssl

# Disable default site (optional)
sudo a2dissite 000-default.conf

# Enable your site
sudo a2ensite queueing_system.conf

# Test Apache configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

## Step 12: Set Up Laravel Reverb WebSocket Server

Laravel Reverb needs to run as a background service for real-time broadcasting.

Create a systemd service file:

```bash
sudo nano /etc/systemd/system/reverb.service
```

Add the following content:

```ini
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html/queueing_system
ExecStart=/usr/bin/php /var/www/html/queueing_system/artisan reverb:start --host=0.0.0.0 --port=8080
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable reverb
sudo systemctl start reverb
sudo systemctl status reverb
```

## Step 13: Set Up Laravel Queue Worker (Optional)

If your application uses queued jobs, set up the queue worker:

```bash
sudo nano /etc/systemd/system/laravel-worker.service
```

Add:

```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html/queueing_system
ExecStart=/usr/bin/php /var/www/html/queueing_system/artisan queue:work --tries=3
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-worker
sudo systemctl start laravel-worker
```

## Step 14: Configure Firewall

```bash
# Allow Apache
sudo ufw allow 'Apache Full'

# Allow Reverb WebSocket port
sudo ufw allow 8080/tcp

# Enable firewall if not already enabled
sudo ufw enable

# Check status
sudo ufw status
```

## Step 15: Optimize Laravel for Production

```bash
cd /var/www/html/queueing_system

# Cache configuration
sudo php artisan config:cache

# Cache routes
sudo php artisan route:cache

# Cache views
sudo php artisan view:cache

# Optimize autoloader
sudo composer dump-autoload --optimize
```

## Step 16: Create Default Admin User (Optional)

If you need to create an admin/operator user manually:

```bash
sudo php artisan tinker
```

Then in the tinker shell:

```php
use App\Models\User;
use App\Models\Counter;
use Illuminate\Support\Facades\Hash;

// Create a counter first
$counter = Counter::create([
    'name' => 'Window 1',
    'type' => 'registrar',
    'claimed' => false
]);

// Create user
User::create([
    'name' => 'Admin User',
    'email' => 'admin@lourdes.edu',
    'password' => Hash::make('password123'),
    'role' => 'registrar',
    'counter_id' => $counter->id
]);

exit
```

## Step 17: Access Your Application

Open your web browser and navigate to:
- **Kiosk**: `http://your-server-ip/`
- **Monitor**: `http://your-server-ip/monitor`
- **Operator Login**: `http://your-server-ip/login`

## Troubleshooting

### Check Apache Error Logs
```bash
sudo tail -f /var/log/apache2/queueing_system-error.log
```

### Check Laravel Logs
```bash
sudo tail -f /var/www/html/queueing_system/storage/logs/laravel.log
```

### Check Reverb Service Status
```bash
sudo systemctl status reverb
sudo journalctl -u reverb -f
```

### Permission Issues
If you encounter permission errors:
```bash
sudo chown -R www-data:www-data /var/www/html/queueing_system
sudo chmod -R 775 /var/www/html/queueing_system/storage
sudo chmod -R 775 /var/www/html/queueing_system/bootstrap/cache
```

### Clear All Caches
If you make changes and they don't reflect:
```bash
sudo php artisan cache:clear
sudo php artisan config:clear
sudo php artisan route:clear
sudo php artisan view:clear
```

### Reverb Not Connecting
1. Ensure port 8080 is open in firewall
2. Check if Reverb service is running: `sudo systemctl status reverb`
3. Verify VITE_REVERB_HOST in `.env` matches your server IP/domain
4. Check browser console for WebSocket connection errors

## Updating the Application

When you need to update the application from GitHub:

```bash
cd /var/www/html/queueing_system

# Pull latest changes
sudo git pull origin main

# Install/update dependencies
sudo -u www-data composer install --optimize-autoloader --no-dev
npm install
npm run build

# Run migrations
sudo php artisan migrate --force

# Clear and recache
sudo php artisan config:clear
sudo php artisan cache:clear
sudo php artisan config:cache
sudo php artisan route:cache
sudo php artisan view:cache

# Restart services
sudo systemctl restart apache2
sudo systemctl restart reverb
```

## Security Recommendations

1. **Change default credentials** - Update all default passwords
2. **Enable HTTPS** - Install Let's Encrypt SSL certificate:
   ```bash
   sudo apt install certbot python3-certbot-apache
   sudo certbot --apache -d your-domain.com
   ```
3. **Disable directory listing** - Already configured in Apache virtual host
4. **Keep system updated**:
   ```bash
   sudo apt update
   sudo apt upgrade
   ```
5. **Regular backups** - Set up automated database and file backups
6. **Monitor logs** - Regularly check Apache and Laravel logs for suspicious activity

## Performance Optimization

1. **Enable OPcache** - Ensure PHP OPcache is enabled
2. **Use Redis** (optional) - For better caching and session management
3. **Enable HTTP/2** in Apache
4. **Configure MySQL** - Optimize for production workload

## Support

For issues specific to this deployment, check:
- Laravel logs: `/var/www/html/queueing_system/storage/logs/`
- Apache logs: `/var/log/apache2/`
- System logs: `sudo journalctl -xe`

---

**Note**: Replace `your-server-ip-or-domain`, `your_db_user`, and `your_secure_password` with your actual values throughout this guide.
