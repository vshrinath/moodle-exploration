# View Your Fellowship Dashboard

## ✅ Status: Dashboard Block Installed!

The SCEH Fellowship Training Dashboard block has been successfully copied into your Moodle container.

## 🌐 Access Moodle

**Open in your browser:** http://localhost:8080

## 🔑 Login Credentials

Try these credentials:
- **Username:** `user` or `admin`
- **Password:** `bitnami`

If that doesn't work, get credentials from logs:
```bash
docker logs moodle-exploration-moodle-1 2>&1 | grep -i "password\|credential" | head -20
```

## 📦 Install the Dashboard Block

### Step 1: Upgrade Database
1. Log in to Moodle as admin
2. You should see a notification about plugin updates
3. Click **"Upgrade Moodle database now"**
4. Wait for completion (should see "Fellowship Training Dashboard" in the list)

**OR** go directly to:
```
http://localhost:8080/admin/index.php
```

### Step 2: Add Block to Homepage

#### Option A: Add to Site Home
1. Go to: http://localhost:8080
2. Click **"Turn editing on"** (top right)
3. Look for **"Add a block"** dropdown (usually in sidebar)
4. Select **"Fellowship Training Dashboard"**
5. The colorful card grid will appear!
6. Click **"Turn editing off"**

#### Option B: Add to Dashboard
1. Go to: http://localhost:8080/my/
2. Click **"Customise this page"** (top right)
3. Click **"Add a block"**
4. Select **"Fellowship Training Dashboard"**
5. Click **"Reset page to default"** when done

## 🎨 What You'll See

The dashboard will show colorful cards based on your role:

### As Admin, you'll see:
- 👥 **Manage Cohorts** (purple gradient)
- 🌳 **Competency Framework** (green gradient)
- 📊 **Attendance Reports** (red gradient)
- 📉 **Training Evaluation** (purple gradient) ← This is your Kirkpatrick dashboard!
- 🏅 **Badge Management** (yellow gradient)
- 🗂️ **Program Structure** (teal gradient)
- 📋 **Custom Reports** (orange gradient)
- ⚙️ **Roster Rules** (indigo gradient)

Each card is clickable and takes you directly to that feature.

## 🔧 If Database Upgrade Doesn't Trigger Automatically

Run this command to force the upgrade:
```bash
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/admin/cli/upgrade.php --non-interactive
```

Then clear cache:
```bash
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/admin/cli/purge_caches.php
```

## ✅ Verify Installation

Check if the block is registered:
```bash
docker exec moodle-exploration-moodle-1 php -r "require('/bitnami/moodle/config.php'); \$block = \$DB->get_record('block', ['name' => 'sceh_dashboard']); echo \$block ? 'Block installed!' : 'Not found'; echo PHP_EOL;"
```

## 🎯 Quick Test

Once you add the block:
1. You should see 8 colorful cards in a grid
2. Click **"Training Evaluation"** - should open your Kirkpatrick dashboard
3. Click **"Competency Framework"** - should show your competencies
4. Click **"Roster Rules"** - should open your SCEH rules engine

## 📸 Expected Look

The dashboard will look like the "10 Essential Moodle Plugins" image you showed me:
- Colorful gradient cards
- Icons on each card
- Responsive grid layout
- Hover effects (cards lift up slightly)
- Clean, modern design

## 🐛 Troubleshooting

### Block doesn't appear in "Add a block" list
```bash
# Clear all caches
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/admin/cli/purge_caches.php

# Force upgrade
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/admin/cli/upgrade.php --non-interactive
```

### Can't find "Add a block"
- Make sure you're on the homepage or dashboard
- Ensure editing is turned on
- Look in the sidebar or at the bottom of the page

### Cards show but styling is wrong
- Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)
- Check browser console for errors (F12)

## 🎉 Next Steps

Once the dashboard is visible:
1. Test clicking each card to verify links work
2. Customize colors if desired (edit `styles.css`)
3. Add/remove cards based on your needs (edit `block_sceh_dashboard.php`)
4. Show it to your fellowship trainees!

---

**Your Moodle is running at:** http://localhost:8080  
**Dashboard block location:** `/bitnami/moodle/blocks/sceh_dashboard`
