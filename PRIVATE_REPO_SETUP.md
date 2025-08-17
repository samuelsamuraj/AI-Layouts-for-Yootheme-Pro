# Private Repository Setup Guide

This guide explains how to set up automatic updates for your private GitHub repository.

## Prerequisites

1. **Private GitHub Repository**: `samuelsamuraj/ai-layout-for-yootheme`
2. **GitHub Personal Access Token** with `repo` scope
3. **WordPress Admin Access** to configure the plugin

## Step 1: Create GitHub Personal Access Token

1. Go to [GitHub Settings > Developer settings > Personal access tokens](https://github.com/settings/tokens)
2. Click **"Generate new token (classic)"**
3. Give it a descriptive name: `AI Layout Plugin Updates`
4. Set expiration: Choose appropriate duration (recommend 90 days)
5. Select scopes:
   - ✅ `repo` (Full control of private repositories)
6. Click **"Generate token"**
7. **Copy the token immediately** - you won't see it again!

## Step 2: Configure Plugin Settings

1. In WordPress admin, go to **AI Layout**
2. Scroll to the **API** section
3. Paste your GitHub token in **"GitHub Personal Access Token"**
4. Click **"Save API Settings"**

## Step 3: Test Update Check

1. After saving the token, the plugin will automatically check for updates
2. Check WordPress admin → **Plugins** page
3. You should see update notifications if there are newer versions

## Step 4: Create Your First Release

### Option A: Manual Release
1. **Tag your release**:
   ```bash
   git tag v0.2.4
   git push origin v0.2.4
   ```
2. Go to **GitHub → Releases**
3. Click **"Create a new release"**
4. Select the tag: `v0.2.4`
5. Add release title and description
6. **Attach ZIP file** (recommended)
7. Click **"Publish release"**

### Option B: Automated Release (Recommended)
1. **Push a tag**:
   ```bash
   git tag v0.2.4
   git push origin v0.2.4
   ```
2. GitHub Actions will automatically:
   - Build the plugin
   - Create a ZIP file
   - Publish the release
   - Attach the ZIP file

## Step 5: Verify Update

1. Wait a few minutes for WordPress to check for updates
2. Go to **WordPress Admin → Plugins**
3. You should see an update notification
4. Click **"Update Now"** to install the new version

## Troubleshooting

### "No updates available"
- Check if your GitHub token has `repo` scope
- Verify the repository is private
- Ensure the release tag starts with `v` (e.g., `v0.2.4`)
- Check WordPress error logs for GitHub API errors

### "Download failed"
- Verify the release has a ZIP file attached
- Check if the ZIP file structure is correct
- Ensure the token has proper permissions

### "Authentication failed"
- Regenerate your GitHub token
- Ensure the token hasn't expired
- Check if the token has `repo` scope

## Security Best Practices

1. **Token Expiration**: Set reasonable expiration dates
2. **Scope Limitation**: Only grant `repo` scope (not `repo:status` or others)
3. **Regular Rotation**: Regenerate tokens every 90 days
4. **Secure Storage**: Never commit tokens to version control
5. **Access Logging**: Monitor GitHub API usage

## File Structure for Releases

Your release ZIP should have this structure:
```
ai-layout-for-yootheme/
├── ai-layout-for-yootheme.php
├── assets/
│   ├── admin.css
│   └── admin.js
├── inc/
│   ├── rest.php
│   └── plugin-update-checker.php
├── prompts/
├── schema/
├── composer.json
├── README.md
└── readme.txt
```

## GitHub Actions Automation

The included `.github/workflows/release.yml` will:
- Trigger on tag pushes (`v*`)
- Install dependencies
- Create properly structured ZIP
- Publish release with ZIP attachment
- Generate release notes

## Support

If you encounter issues:
1. Check WordPress error logs
2. Verify GitHub token permissions
3. Test GitHub API access manually
4. Create an issue in the repository

---

**Remember**: Keep your GitHub token secure and never share it publicly!
