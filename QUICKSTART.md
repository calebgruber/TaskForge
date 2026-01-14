# TaskForge Quick Start Guide

## Getting Started in 5 Minutes

### 1. First Login
1. Visit your TaskForge URL
2. Click **Sign up**
3. Create your account with username, email, and password
4. Log in with your credentials

### 2. Create Your First Task
1. Click **Tasks** → **New Task**
2. Fill in:
   - **Title**: e.g., "Take out trash"
   - **Category**: Select "Chores"
   - **Urgency**: Choose urgency level
   - **Due Date**: Set when it's due
3. Click **Save & Print** (if printer connected) or **Save Task**

### 3. Print Task Receipt (Optional)
- If printer is connected, receipt prints automatically
- Receipt includes:
  - Task title and details
  - Barcode for scanning
  - XP value
  - Due date
- Stick receipt on monitor or task board

### 4. Complete a Task

**Method 1: Scan Barcode**
1. Go to **Scanner** page
2. Scan the barcode on your task receipt
3. Task completes instantly
4. XP awarded automatically
5. Level up if you earned enough XP!

**Method 2: Manual Completion**
1. Go to **Tasks** page
2. Click the **✓** button next to task
3. Confirm completion
4. XP awarded

### 5. Check Your Progress
- **Dashboard** shows:
  - Active tasks count
  - Tasks completed today
  - Overdue tasks
  - Total XP and current level
- **Rewards** page shows achievements
- **Profile** shows detailed statistics

## Hardware Setup

### Thermal Printer (80mm ESC/POS)

**USB Connection:**
1. Connect printer to USB
2. Note device path (usually `/dev/usb/lp0`)
3. Go to **Admin** → **Settings**
4. Enable printer, select USB
5. Save settings

**Ethernet Connection:**
1. Get printer's IP address from printer settings
2. Go to **Admin** → **Settings**
3. Enable printer, select Ethernet
4. Enter IP address (e.g., 192.168.1.100)
5. Enter port (usually 9100)
6. Save settings

### Zebra DS81XX-HC Scanner

1. Connect scanner to USB
2. Scanner operates in keyboard mode - no setup needed!
3. Go to **Scanner** page
4. Scanner will type barcodes automatically
5. Test by scanning any barcode

## Admin Features

### Categories
**Admin** → **Categories**
- Create categories for different task types
- Assign colors for visual organization
- Examples: Work, Personal, Chores, Habits

### Templates
**Admin** → **Templates**
- Design custom receipt layouts
- Set header/footer text
- Choose what information to display
- Configure barcode type

### XP Rules
**Admin** → **XP Rules**
- Set base XP values
- Configure urgency multipliers
- Add bonuses for early completion
- Set penalties for overdue tasks
- Create category-specific rules

### Icons
**Admin** → **Icons**
- Upload custom icons (PNG, JPEG, GIF)
- Best size: 48x48 pixels
- Assign to tasks, urgency levels, or categories

### Settings
**Admin** → **Settings**
- Configure printer (USB or Ethernet)
- Set up scanner prefix/suffix
- Enable SMS reminders
- Configure Twilio or Nexmo

## Tips & Tricks

### Maximize XP Gains
- Complete tasks early for 10% bonus
- Higher urgency = more XP
- Increase difficulty for bigger rewards
- Avoid late completion (-20% penalty)

### Organize Tasks
- Use categories to group similar tasks
- Set urgency based on importance
- Add due dates to stay on track
- Enable reminders for important tasks

### Physical Workflow
1. **Morning**: Print all tasks for the day
2. **During Day**: Keep receipts visible
3. **Complete Task**: Scan receipt immediately
4. **End of Day**: Check progress on dashboard

### Scanner Station Setup
- Keep scanner connected and powered
- Place scanner within easy reach
- Keep **Scanner** page open in browser
- Scan receipts as you complete tasks

## SMS Reminders

### Enable SMS
1. Sign up for Twilio or Nexmo account
2. Get API credentials
3. Go to **Admin** → **Settings**
4. Enable SMS, enter credentials
5. Add phone number in **Profile**

### Set Up Cron Job
Add this to cPanel Cron Jobs:
```
*/5 * * * * /usr/bin/php /path/to/taskforge/cron/check-reminders.php
```

### How It Works
- Reminders sent before due time
- Overdue alerts sent daily
- Can disable per-user in profile
- Stops when task completed

## Keyboard Shortcuts

- **Scanner Page**: Auto-focuses input
- Press **Enter** after barcode input
- **Esc** to dismiss alerts

## Mobile Access

- Fully responsive design
- Access from any device
- View rewards on phone
- Create tasks on the go
- Check progress anywhere

## Troubleshooting

**Can't log in?**
- Check username/password
- Try email instead of username
- Clear browser cache

**Printer not working?**
- Check USB/network connection
- Verify settings in Admin panel
- Check device permissions (USB)
- Test printer with system tools

**Scanner not detecting?**
- Ensure scanner in keyboard mode
- Check USB connection
- Try different USB port
- Test in notepad - should type barcode

**Tasks not showing?**
- Check filter (Active/Completed/All)
- Verify task was saved
- Refresh browser

**XP not awarded?**
- Task must be marked complete
- Check completion log
- Verify XP rules are active

## Best Practices

### Daily Routine
1. Morning: Review dashboard, create tasks
2. Print receipts for new tasks
3. Complete and scan throughout day
4. Evening: Check progress, plan tomorrow

### Task Creation
- Be specific with titles
- Add descriptions for complex tasks
- Set realistic due dates
- Choose appropriate urgency
- Enable reminders for important items

### Level Progression
- Consistent daily completion > sporadic bursts
- Balance urgent and normal tasks
- Complete tasks early when possible
- Increase difficulty for bigger challenges

### Receipt Management
- Use a task board or wall space
- Arrange by urgency or category
- Keep completed receipts for motivation
- Recycle or archive old receipts

## Support

- **Documentation**: See README.md and INSTALL.md
- **Configuration**: Edit config/config.php
- **Database**: Access via phpMyAdmin in cPanel
- **Logs**: Check cPanel Error Log

## Advanced Features

### Recurring Tasks
- Enable "Is Recurring" when creating task
- Set recurrence pattern (daily, weekly, etc.)
- System will auto-generate new instances

### Custom XP Rules
- Create rules for specific categories
- Higher multipliers for important work
- Bonus XP for completing ahead of schedule
- Penalty for late completions

### Template Design
- Create templates for different task types
- Customize header text (e.g., "URGENT!", "Daily Quest")
- Configure barcode type (CODE128 recommended)
- Set alignment (center/left/right)

---

**Ready to start your productivity quest? Create your first task now!** 🎯
