#!/bin/bash
# TaskForge Installation Script for cPanel
# Run this after uploading files to your server

echo "================================"
echo "TaskForge Installation Script"
echo "================================"
echo ""

# Check if running as proper user
if [ "$EUID" -eq 0 ]; then 
    echo "⚠️  Do not run this script as root!"
    echo "Run as your cPanel user account"
    exit 1
fi

# Get current directory
INSTALL_DIR=$(pwd)
echo "📁 Installation directory: $INSTALL_DIR"
echo ""

# Check PHP version
echo "🔍 Checking PHP version..."
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "PHP Version: $PHP_VERSION"

if php -r "exit(version_compare(PHP_VERSION, '7.4.0', '>=') ? 0 : 1);"; then
    echo "✅ PHP version is sufficient"
else
    echo "❌ PHP 7.4 or higher is required"
    exit 1
fi
echo ""

# Check MySQL
echo "🔍 Checking MySQL..."
if command -v mysql &> /dev/null; then
    echo "✅ MySQL is available"
else
    echo "⚠️  MySQL command not found. Make sure MySQL/MariaDB is installed."
fi
echo ""

# Create directories
echo "📁 Creating directories..."
mkdir -p public/uploads/icons
chmod 755 public/uploads
chmod 755 public/uploads/icons
echo "✅ Directories created"
echo ""

# Database setup
echo "🗄️  Database Setup"
echo "=================="
echo "Before continuing, make sure you have:"
echo "1. Created database: voxelnodes_taskforge"
echo "2. Created user: voxelnodes_taskforge"
echo "3. Granted all privileges"
echo ""
read -p "Have you completed the database setup? (y/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Please complete database setup first."
    echo "Instructions:"
    echo "1. Log into cPanel"
    echo "2. Go to MySQL Databases"
    echo "3. Create database and user"
    echo "4. Grant all privileges"
    exit 1
fi

# Import database schema
echo ""
echo "📥 Importing database schema..."
read -p "Enter MySQL username [voxelnodes_taskforge]: " DB_USER
DB_USER=${DB_USER:-voxelnodes_taskforge}

read -p "Enter database name [voxelnodes_taskforge]: " DB_NAME
DB_NAME=${DB_NAME:-voxelnodes_taskforge}

read -sp "Enter MySQL password: " DB_PASS
echo ""

if [ -f "database/schema.sql" ]; then
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/schema.sql
    if [ $? -eq 0 ]; then
        echo "✅ Database schema imported successfully"
    else
        echo "❌ Failed to import database schema"
        echo "You may need to import manually using phpMyAdmin"
    fi
else
    echo "⚠️  database/schema.sql not found"
fi
echo ""

# Check config file
echo "⚙️  Checking configuration..."
if [ -f "config/config.php" ]; then
    echo "✅ config/config.php exists"
    echo "Make sure to update the following in config/config.php:"
    echo "  - DB_HOST, DB_NAME, DB_USER, DB_PASS"
    echo "  - APP_URL"
    echo "  - Printer settings (if using hardware)"
else
    echo "❌ config/config.php not found!"
fi
echo ""

# Set up cron job
echo "⏰ Cron Job Setup (Optional)"
echo "============================="
echo "To enable SMS reminders, add this cron job in cPanel:"
echo ""
echo "*/5 * * * * /usr/bin/php $INSTALL_DIR/cron/check-reminders.php"
echo ""
read -p "Would you like instructions to set this up? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo "Instructions:"
    echo "1. Log into cPanel"
    echo "2. Go to 'Cron Jobs'"
    echo "3. Add new cron job:"
    echo "   - Minute: */5"
    echo "   - Hour: *"
    echo "   - Day: *"
    echo "   - Month: *"
    echo "   - Weekday: *"
    echo "   - Command: /usr/bin/php $INSTALL_DIR/cron/check-reminders.php"
fi
echo ""

# Test web access
echo "🌐 Web Access"
echo "============="
echo "Your TaskForge installation should be accessible at:"
echo "http://yourdomain.com"
echo ""
echo "First steps:"
echo "1. Visit your website"
echo "2. Click 'Sign up' to create an account"
echo "3. Log in and start creating tasks!"
echo ""

# Security recommendations
echo "🔒 Security Recommendations"
echo "==========================="
echo "1. Enable SSL certificate (use Let's Encrypt in cPanel)"
echo "2. Keep config/config.php outside public_html if possible"
echo "3. Regularly backup your database"
echo "4. Set strong passwords for all accounts"
echo "5. Keep PHP and MySQL updated"
echo ""

# Summary
echo "✅ Installation Complete!"
echo "========================="
echo ""
echo "Next steps:"
echo "1. Update config/config.php with your settings"
echo "2. Visit your website and register"
echo "3. Configure printer/scanner in Admin > Settings"
echo "4. Create your first task!"
echo ""
echo "📚 For detailed instructions, see INSTALL.md"
echo ""
echo "Need help? Check the README.md file"
echo ""
