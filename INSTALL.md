# TaskForge Installation Guide

## Complete Setup Instructions for cPanel

### Step 1: Prepare Your Environment

1. **Access cPanel** at your hosting provider
2. **Note your domain**: e.g., `yourdomain.com` or `subdomain.yourdomain.com`

### Step 2: Create MySQL Database

1. In cPanel, go to **MySQL Databases**
2. Create a new database:
   - Database name: `voxelnodes_taskforge` (or `yourusername_taskforge` if cPanel adds prefix)
3. Create a new user:
   - Username: `voxelnodes_taskforge`
   - Password: `xlLFETJL3$qy` (or generate a strong password)
   - Click **Create User**
4. Add user to database:
   - Select user and database
   - Grant **ALL PRIVILEGES**
   - Click **Make Changes**
5. **Note down** your actual database name, username, and password

### Step 3: Upload Files

#### Option A: File Manager (Easy)

1. In cPanel, open **File Manager**
2. Navigate to `public_html` (or your domain's root folder)
3. Upload the ZIP file containing TaskForge
4. Extract the ZIP file
5. Move contents of `/public` folder to web root
6. Move other folders (`config`, `includes`, `database`) one level up (outside public_html) for security

#### Option B: FTP (Alternative)

1. Use FileZilla or another FTP client
2. Connect to your server
3. Upload files to appropriate directories:
   - `public/*` → `public_html/`
   - `config/` → `config/` (outside web root)
   - `includes/` → `includes/` (outside web root)
   - `database/` → `database/` (outside web root)

### Step 4: Update Configuration

1. Edit `config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'voxelnodes_taskforge'); // Use YOUR actual database name
   define('DB_USER', 'voxelnodes_taskforge'); // Use YOUR actual username
   define('DB_PASS', 'xlLFETJL3$qy'); // Use YOUR actual password
   ```

2. Update the APP_URL:
   ```php
   define('APP_URL', 'https://yourdomain.com');
   ```

### Step 5: Import Database Schema

#### Using phpMyAdmin:

1. In cPanel, open **phpMyAdmin**
2. Select your database (`voxelnodes_taskforge`)
3. Click **Import** tab
4. Choose file: `database/schema.sql`
5. Click **Go**
6. Verify tables are created (should see 14 tables)

#### Using Terminal/SSH:

```bash
mysql -u voxelnodes_taskforge -p voxelnodes_taskforge < database/schema.sql
```

### Step 6: Set File Permissions

In **File Manager** or via SSH:

```bash
chmod 755 public_html/uploads
chmod 755 public_html/uploads/icons
chmod 644 config/config.php
```

### Step 7: Test Installation

1. Visit your website: `https://yourdomain.com`
2. You should see the TaskForge login page
3. Click **Sign up** to create your first account
4. Fill in registration form and create account

**Verify Timezone**: Visit `https://yourdomain.com/timezone-check.php` to verify the system is using US Eastern Time correctly.

### Step 8: Configure Hardware (Optional)

#### Thermal Printer Setup

If using USB printer:
1. Connect printer to server via USB
2. Find device path: `ls -l /dev/usb/lp*`
3. Update `config/config.php`:
   ```php
   define('PRINTER_ENABLED', true);
   define('PRINTER_TYPE', 'usb');
   define('PRINTER_DEVICE', '/dev/usb/lp0');
   ```

If using Network/Ethernet printer:
1. Get printer's IP address (check printer settings)
2. Update `config/config.php`:
   ```php
   define('PRINTER_ENABLED', true);
   define('PRINTER_TYPE', 'ethernet');
   define('PRINTER_IP', '192.168.1.100');
   define('PRINTER_PORT', 9100);
   ```

#### Zebra Scanner Setup

1. Connect Zebra DS81XX-HC scanner to USB
2. Scanner works in keyboard emulation mode (no drivers needed)
3. Test by scanning in any text field - barcode should appear
4. In TaskForge, go to **Scanner** page and test scanning

### Step 9: Initial Configuration

1. Log in to TaskForge
2. Go to **Admin** → **Settings**
3. Configure:
   - Printer settings (if using printer)
   - Scanner settings (prefix/suffix if needed)
   - SMS settings (if using reminders)
4. Go to **Admin** → **Categories** - default categories are pre-loaded
5. Go to **Admin** → **Templates** - default templates are pre-loaded

### Step 10: Create Your First Task

1. Click **Tasks** → **New Task**
2. Fill in details:
   - Title: "Test Task"
   - Category: "Personal"
   - Urgency: "Normal"
   - Description: "My first quest"
3. Click **Save**
4. If printer is enabled, click **Print** to generate receipt
5. Go to **Scanner** page and scan the barcode (or type it manually)
6. Task should complete and award XP!

## Troubleshooting

### "Database connection failed"
- Check database credentials in `config/config.php`
- Verify database exists in phpMyAdmin
- Ensure user has ALL PRIVILEGES on database

### "Headers already sent" error
- Check for whitespace before `<?php` in PHP files
- Ensure files are saved with UTF-8 encoding (no BOM)

### Printer not working
- For USB: Check device permissions - `chmod 666 /dev/usb/lp0`
- For Network: Verify IP and port, test with telnet
- Check error logs: `tail -f /var/log/apache2/error.log`

### Scanner not responding
- Ensure scanner is in keyboard mode (not serial)
- Test in notepad - barcode should type automatically
- Check USB connection
- Try different USB port

### Can't upload files / Permission denied
- Check folder permissions: `chmod 755 uploads/`
- Check ownership: `chown username:username uploads/`
- Increase upload limit in php.ini if needed

### Session issues / Can't stay logged in
- Check session.save_path in php.ini
- Verify /tmp directory is writable
- Clear browser cookies and try again

### Timezone Issues
- Visit `/timezone-check.php` to verify configuration
- System should show US Eastern Time (America/New_York)
- Both PHP and MySQL should match
- Check config/config.php timezone setting

## Advanced Configuration

### SSL Certificate (Recommended)

1. In cPanel, go to **SSL/TLS**
2. Use **Let's Encrypt** for free SSL
3. Install certificate for your domain
4. Update `APP_URL` in config to use `https://`

### Cron Jobs for Reminders

Set up cron job to check for reminders:

```bash
*/5 * * * * /usr/bin/php /path/to/taskforge/cron/check-reminders.php
```

### Performance Optimization

1. Enable **OPcache** in cPanel → PHP Settings
2. Use **Memcached** if available
3. Enable **gzip compression** in .htaccess

### Backup Strategy

1. Set up automated backups in cPanel
2. Backup database regularly via phpMyAdmin
3. Keep copy of `uploads/` folder

## Support

- Check error logs: cPanel → **Error Log**
- PHP errors: Enable display_errors in development
- Database errors: Check phpMyAdmin SQL tab

## Next Steps

- Customize receipt templates
- Upload custom icons
- Adjust XP rules
- Set up SMS reminders
- Create task categories for your workflow

Enjoy your physical-digital productivity system! 🎯
