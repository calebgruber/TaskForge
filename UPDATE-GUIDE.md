# TaskForge Auto-Update System

## Overview

TaskForge includes a built-in auto-update system that pulls the latest code from the GitHub repository and automatically updates your installation.

## Features

- 🔄 One-click updates from GitHub
- 🔐 Password-protected access
- 💾 Automatic backup before update
- 📁 Preserves uploads and configuration
- 🗄️ Database check reminders
- 📝 Detailed update logs

## Setup

### 1. Make Repository Public

Your GitHub repository must be public for the auto-updater to work:

1. Go to GitHub repository settings
2. Scroll to "Danger Zone"
3. Click "Change visibility"
4. Select "Public"
5. Confirm the change

### 2. Configure Install Password

Edit `config/config.php` and change the install password:

```php
define('INSTALL_PASSWORD', 'YourSecurePasswordHere');
```

**Important**: Use a strong, unique password!

### 3. Verify Repository Settings

Check that these values in `config/config.php` match your repository:

```php
define('GITHUB_REPO_OWNER', 'calebgruber');
define('GITHUB_REPO_NAME', 'TaskForge');
define('GITHUB_BRANCH', 'copilot/add-barcode-scanning-rewards');
```

## Usage

### Accessing the Updater

1. Navigate to: `https://yourdomain.com/install.php`
2. Enter your install password
3. You'll see the update control panel

### Checking for Updates

1. Click **"Check for Updates"**
2. View the latest commit information
3. See commit date and message

### Performing an Update

1. **Backup your database first!** (via phpMyAdmin)
2. Click **"Perform Update"**
3. Confirm the warning dialog
4. Wait for update to complete (may take 1-2 minutes)
5. Review the update log

### What Happens During Update

1. ✓ Downloads latest code from GitHub as ZIP
2. ✓ Extracts files to temporary directory
3. ✓ Creates automatic backup:
   - config/config.php
   - public/uploads/ folder
4. ✓ Removes old code files
5. ✓ Installs new code files
6. ✓ Restores your configuration
7. ✓ Sets proper permissions
8. ✓ Cleans up temporary files

### Files That Are Preserved

- **Configuration**: `config/config.php`
- **Uploads**: `public/uploads/` and all icons
- **Database**: Not touched by updater
- **Backups**: All backup folders

### Files That Are Replaced

- All PHP code files
- Admin panel files
- Templates and includes
- Documentation files
- Everything except preserved files above

## After Update

### Immediate Steps

1. ✓ Visit your website to verify it works
2. ✓ Log in to your account
3. ✓ Check dashboard loads correctly

### Database Updates

If there are new database tables or columns:

1. Go to cPanel → phpMyAdmin
2. Select your database
3. Click "Import" tab
4. Upload `database/schema.sql`
5. Review for new tables/columns only

### Verify Functionality

- [ ] Tasks can be created
- [ ] Scanner works
- [ ] Printer functions (if connected)
- [ ] Admin panel accessible
- [ ] Settings saved correctly

## Security

### Best Practices

1. **Change Default Password**: Never use the default install password
2. **Restrict Access**: Add IP whitelist in .htaccess if possible:
   ```apache
   <Files "install.php">
       Order Deny,Allow
       Deny from all
       Allow from YOUR.IP.ADDRESS
   </Files>
   ```
3. **Disable After Use**: Comment out or delete install.php when not needed
4. **Regular Backups**: Always backup database before updating

### Disabling the Updater

To completely disable access to install.php:

**Option 1: Via .htaccess**
```apache
<Files "install.php">
    Order allow,deny
    Deny from all
</Files>
```

**Option 2: Rename File**
```bash
mv public/install.php public/install.php.disabled
```

**Option 3: Delete File**
```bash
rm public/install.php
```

## Troubleshooting

### "Failed to download repository"

**Cause**: Repository is private or GitHub is unreachable

**Solutions**:
- Make repository public on GitHub
- Check server has internet access
- Verify repository name and owner are correct
- Try again in a few minutes

### "Could not extract ZIP file"

**Cause**: PHP ZipArchive extension not available or file corrupted

**Solutions**:
- Check PHP has `zip` extension: `php -m | grep zip`
- Enable in php.ini: `extension=zip`
- Restart web server
- Check available disk space

### "Permission denied"

**Cause**: Insufficient file permissions

**Solutions**:
```bash
chmod 755 /path/to/taskforge
chmod 755 /path/to/taskforge/public
chmod 755 /path/to/taskforge/public/uploads
```

### Update Completed But Site Broken

**Immediate Recovery**:

1. Check the backup folder: `/backup_[timestamp]/`
2. Restore config:
   ```bash
   cp backup_[timestamp]/config.php config/config.php
   ```
3. Restore uploads:
   ```bash
   cp -r backup_[timestamp]/uploads/* public/uploads/
   ```
4. Check error logs in cPanel

**Database Issues**:
- New database schema may be needed
- Import `database/schema.sql` in phpMyAdmin
- Check for error messages in logs

### "Invalid password"

**Solutions**:
- Verify `INSTALL_PASSWORD` in `config/config.php`
- Password is case-sensitive
- Check for extra spaces
- If locked out, edit config.php via FTP

## Manual Update Process

If auto-update fails, you can update manually:

1. Download repository ZIP from GitHub
2. Extract locally
3. Upload via FTP (except uploads and config)
4. Restore your config.php
5. Import database changes if any

## Advanced Configuration

### Custom Branch

To use a different branch:

```php
define('GITHUB_BRANCH', 'main'); // or 'develop', etc.
```

### Private Repository

For private repositories, you'll need to:

1. Generate a GitHub Personal Access Token
2. Modify install.php to use authentication
3. Add token to curl requests

## FAQ

**Q: Will update delete my tasks?**
A: No. The database is never touched by the updater.

**Q: Will update delete my uploaded icons?**
A: No. The uploads folder is automatically preserved.

**Q: Can I rollback an update?**
A: Yes. The updater creates automatic backups. Restore from `/backup_[date]/` folder.

**Q: How often should I update?**
A: Check for updates when new features are released or bugs are fixed.

**Q: Do I need to logout users before updating?**
A: Not required, but recommended during low-traffic times.

**Q: Can I test updates on staging first?**
A: Yes! Set up a separate installation with a copy of your database.

## Support

If you encounter issues with the auto-updater:

1. Check this documentation
2. Review the update log output
3. Check server error logs
4. Restore from automatic backup if needed
5. Manual update process as fallback

---

**Remember**: Always backup your database before updating!
