# Tablet/Kiosk Mode for TaskForge

## Overview

Tablet Mode is a fullscreen, touch-optimized interface designed for Windows 10 tablets and other touchscreen devices. It provides a simplified, card-based UI with large buttons and text for easy interaction without a mouse and keyboard.

## Features

- **Fullscreen Kiosk Mode**: Runs in fullscreen with browser controls hidden
- **Touch-Optimized UI**: Large buttons, cards, and form controls for easy touch interaction
- **PIN-Protected Exit**: Requires a 4-digit PIN (configured in config.php) to exit tablet mode
- **No Admin Features**: Tablet mode excludes all admin functionality and registration
- **Same Functionality**: All core features work the same as the main site:
  - Dashboard with user stats and XP progress
  - Barcode scanner (keyboard and camera modes)
  - Task management (view, create, print)
  - Rewards display
  
## Setup

### 1. Configure Exit PIN

Edit `config/config.php` and set your 4-digit PIN:

```php
define('TABLET_EXIT_PIN', '1234'); // Change this!
```

### 2. Launch Tablet Mode

From the main TaskForge interface:
1. Go to Admin dropdown menu
2. Click "Launch Tablet Mode"
3. A new window/tab opens in kiosk mode
4. Sign in with your regular credentials

### 3. Using Tablet Mode

- The interface automatically requests fullscreen
- Use the large touch-friendly cards to navigate
- All pages are optimized for touch with huge buttons and inputs
- Scanner works with both USB scanner and camera
- Tasks can be created and printed directly

### 4. Exiting Tablet Mode

1. Click "Exit Tablet Mode" button in the top-right
2. Enter the 4-digit PIN from config.php
3. You'll be redirected back to the main TaskForge interface

## Design Principles

- **Huge Touch Targets**: All buttons are minimum 80px tall
- **Large Text**: Font sizes 24px-48px for easy reading
- **Simple Navigation**: Card-based layout with clear visual hierarchy
- **Minimal Distractions**: No admin features, no registration, focused on core tasks
- **Visual Feedback**: Hover effects, transitions, and confetti celebrations
- **Fullscreen Focus**: Prevents accidental exits with PIN protection

## File Structure

```
public/tablet/
├── index.php           # Main dashboard
├── login.php           # Tablet mode login (no signup)
├── header.php          # Tablet-specific header with exit button
├── footer.php          # Simple footer
├── scanner.php         # Touch-optimized scanner interface
├── tasks.php           # Task list view
├── create-task.php     # Create new task form
├── print-task.php      # Print confirmation page
├── rewards.php         # Rewards display
└── exit-verify.php     # PIN verification API
```

## Browser Compatibility

Works best in:
- Microsoft Edge (Windows 10/11)
- Google Chrome
- Firefox

## Tips

- Use in landscape orientation for best experience
- Connect USB barcode scanner before launching
- Printer should be configured in main settings before using tablet mode
- Keep the tablet plugged in during extended use
- Consider mounting the tablet near your workspace for easy access

## Security

- Exit PIN prevents unauthorized users from leaving kiosk mode
- No registration available in tablet mode
- No admin features accessible
- Regular session timeouts still apply
- All authentication goes through the main system
