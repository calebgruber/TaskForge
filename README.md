# TaskForge

## Physical-Digital Productivity Engine

TaskForge is a tactile, game-style productivity system that combines **physical task receipts** with **digital tracking**. Create tasks in a web app, print them on an 80mm thermal printer, and scan them with a Zebra barcode scanner to complete them and earn XP!

### 🎮 Features

- **Physical Quest System**: Print task receipts with barcodes, icons, and urgency levels
- **Barcode Scanner Integration**: Zebra DS81XX-HC USB scanner for instant task completion
- **Thermal Printer Support**: 80mm ESC/POS printer (USB or Ethernet)
- **XP & Leveling System**: Earn experience points and level up as you complete tasks
- **Rewards System**: Unlock achievements displayed in mobile and desktop app
- **SMS Reminders**: Optional text alerts for upcoming and overdue tasks
- **Admin Dashboard**: Manage templates, icons, categories, and XP rules
- **Beautiful UI**: Built with Tabler for a clean, modern interface
- **Auto-Update System**: Built-in updater pulls latest code from GitHub repository

### 🖥️ Technology Stack

- **Backend**: Pure PHP 7.4+ (cPanel compatible)
- **Database**: MySQL/MariaDB
- **Frontend**: Tabler (Bootstrap-based admin template)
- **Hardware**: 
  - Zebra DS81XX-HC USB Barcode Scanner
  - 80mm ESC/POS Thermal Printer (USB or Ethernet)

### 📦 Installation

#### Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- cPanel hosting or standard LAMP stack
- Zebra DS81XX-HC barcode scanner (optional)
- ESC/POS thermal printer (optional)

#### Setup Instructions

1. **Upload files to cPanel**
   - Upload all files to your `public_html` directory
   - Place files from `/public` folder in your web root
   - Place other files outside web root for security

2. **Create Database**
   - Log into cPanel → MySQL Databases
   - Create database: `voxelnodes_taskforge`
   - Create user: `voxelnodes_taskforge` with password
   - Grant all privileges to the user

3. **Import Database Schema**
   ```bash
   mysql -u voxelnodes_taskforge -p voxelnodes_taskforge < database/schema.sql
   ```

4. **Configure Application**
   - Edit `config/config.php`
   - Update database credentials (already configured)
   - Configure printer settings if using hardware

5. **Set Permissions**
   ```bash
   chmod 755 public/uploads
   chmod 644 config/config.php
   ```

6. **Access Application**
   - Navigate to your domain
   - Register a new account
   - Start creating tasks!

### 🔧 Configuration

#### Auto-Update System

TaskForge includes a built-in auto-update system:

1. **Set Repository Public**: Go to GitHub repo settings and make it public
2. **Access Update Panel**: Visit `https://yourdomain.com/install.php`
3. **Enter Password**: Use the password defined in `config/config.php` as `INSTALL_PASSWORD`
4. **Update**: Click "Perform Update" to pull latest code from GitHub

**What gets updated:**
- All PHP code files
- Admin panel features
- Database schema (run manually if needed)
- UI improvements

**What's preserved:**
- Your database data
- Uploaded icons and files
- Configuration settings
- User accounts and tasks

**Security**: Change `INSTALL_PASSWORD` in config.php before first use!

#### Printer Setup (USB)

```php
define('PRINTER_ENABLED', true);
define('PRINTER_TYPE', 'usb');
define('PRINTER_DEVICE', '/dev/usb/lp0');
```

#### Printer Setup (Ethernet)

```php
define('PRINTER_ENABLED', true);
define('PRINTER_TYPE', 'ethernet');
define('PRINTER_IP', '192.168.1.100');
define('PRINTER_PORT', 9100);
```

#### Scanner Setup

The Zebra DS81XX-HC scanner operates in keyboard emulation mode - it types the barcode automatically. No special configuration needed!

#### SMS Reminders (Optional)

```php
define('SMS_ENABLED', true);
define('SMS_PROVIDER', 'twilio');
define('SMS_API_KEY', 'your_api_key');
define('SMS_API_SECRET', 'your_api_secret');
```

### 📖 Usage Guide

#### Creating a Task

1. Go to **Tasks** → **New Task**
2. Fill in task details:
   - Title and description
   - Category (Work, Personal, Chores, etc.)
   - Urgency level (Low, Normal, High, Critical)
   - Due date and time
   - XP value (auto-calculated based on urgency)
3. Click **Save & Print** to print task receipt

#### Scanning a Task

1. Go to **Scanner** page
2. Scan the barcode on your task receipt
3. Task is instantly completed
4. XP is awarded and level progress updates
5. Optional: Completion receipt prints automatically

#### Admin Features

- **Templates**: Design custom receipt layouts
- **Icons**: Upload and manage task/urgency icons
- **Categories**: Create task categories with colors
- **XP Rules**: Configure experience point calculations
- **Settings**: Adjust printer, scanner, and SMS settings

### 🎯 Workflow

1. **Create** → Add tasks via web interface
2. **Print** → Generate physical task receipts
3. **Place** → Put receipt in visible location
4. **Complete** → Do the task
5. **Scan** → Scan barcode to mark complete
6. **Reward** → Earn XP, level up, unlock rewards!

### 📱 Mobile & Desktop Apps

View your rewards, progress, and stats in the responsive web app. Access from any device:

- Desktop: Full featured dashboard
- Mobile: Responsive interface for on-the-go access
- Tablet: Optimized layout for scanner stations

### 🔒 Security

- Password hashing with PHP `password_hash()`
- CSRF protection on forms
- SQL injection prevention with PDO prepared statements
- Session management with secure cookies
- Input validation and sanitization

## Troubleshooting

### Auto-Update Issues

**"Failed to download repository"**
- Ensure repository is set to PUBLIC on GitHub
- Check internet connection on server
- Verify repository owner/name in config.php

**"Could not extract ZIP file"**
- Check PHP has ZipArchive extension enabled
- Verify write permissions on server
- Check available disk space

**Update completed but site broken**
- Restore from automatic backup in `/backup_[date]` folder
- Check if new database updates are needed
- Verify config.php was restored correctly

### Printer not working?
- Check USB connection or network settings
- Verify printer device path (`/dev/usb/lp0`)
- Test with: `echo "test" > /dev/usb/lp0`

**Scanner not detecting?**
- Ensure scanner is in keyboard emulation mode
- Check USB connection
- Focus on barcode input field

**Database connection failed?**
- Verify credentials in `config/config.php`
- Check database exists and user has permissions
- Test connection: `mysql -u user -p database`

### 📄 License

MIT License - Use freely for personal or commercial projects

### 🤝 Contributing

This is a single-purpose productivity system. Fork and customize for your needs!

### 📧 Support

For issues and questions, please create an issue in the repository.

---

**Built with ❤️ for people who love tactile productivity systems**