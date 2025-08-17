# AI Layout for YOOtheme

Generate, review, and compile AI-driven layouts to YOOtheme Pro JSON inside WordPress.

## Features

- 🤖 AI-powered layout generation using OpenAI
- 📱 Responsive wireframe creation
- 🎨 YOOtheme Pro JSON compilation
- 🖼️ Image integration (Unsplash & Pexels)
- 📚 Layout library management
- 🔒 Enterprise-grade security
- 🔄 Automatic updates via GitHub Releases

## Installation

### Method 1: Manual Installation
1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure API keys in the admin panel

### Method 2: Composer Installation
```bash
composer require samuelsamuraj/ai-layout-for-yootheme
```

## Configuration

1. Go to **AI Layout** in your WordPress admin menu
2. Enter your **OpenAI API key** (required)
3. Optionally add **Unsplash** and **Pexels** API keys for image integration
4. Save settings

## Usage

1. Navigate to **AI Layout** in your admin menu
2. Enter a URL or paste text describing your desired layout
3. Add a title and optional comments
4. Click **Generate** to create your layout
5. Review the analysis, wireframe, and compiled JSON
6. Download, apply to a page, or save to your library

## API Keys

### OpenAI (Required)
- Get your API key from [OpenAI Platform](https://platform.openai.com/api-keys)
- Supports GPT-4 and other models

### GitHub Personal Access Token (Required for Private Repo)
- Go to [GitHub Settings > Developer settings > Personal access tokens](https://github.com/settings/tokens)
- Click "Generate new token (classic)"
- Select `repo` scope for full repository access
- Copy the token and paste it in the plugin settings

### Unsplash (Optional)
- Get your access key from [Unsplash Developers](https://unsplash.com/developers)
- Provides high-quality stock photos

### Pexels (Optional)
- Get your API key from [Pexels API](https://www.pexels.com/api/)
- Alternative stock photo source

## Automatic Updates

This plugin automatically checks for updates from GitHub Releases:

1. **Create a private GitHub repository**: `samuelsamuraj/ai-layout-for-yootheme`
2. **Generate a Personal Access Token** with `repo` scope
3. **Add the token** in the plugin settings
4. **Push your code** to the repository
5. **Create a release**:
   - Tag: `v0.2.4` (semantic versioning)
   - Release title: `Version 0.2.4`
   - Description: Include changelog and features
6. **Attach ZIP file** (optional but recommended)

### Release ZIP Structure
```
ai-layout-for-yootheme/
├── ai-layout-for-yootheme.php
├── assets/
├── inc/
├── prompts/
├── schema/
└── readme.txt
```

## Development

### Local Development
```bash
# Clone the repository
git clone https://github.com/samuelsamuraj/ai-layout-for-yootheme.git

# Install dependencies
composer install

# Make changes and test
# Create a new release when ready
```

### File Structure
```
ai-layout-for-yootheme/
├── ai-layout-for-yootheme.php    # Main plugin file
├── assets/                        # CSS and JavaScript
│   ├── admin.css
│   └── admin.js
├── inc/                          # PHP includes
│   ├── rest.php                  # REST API endpoints
│   └── plugin-update-checker.php # Update mechanism
├── prompts/                      # AI prompts
│   ├── analysis_prompt.txt
│   ├── compile_prompt.txt
├── schema/                       # JSON schemas
│   ├── analysis.schema.json
│   ├── layout.schema.json
│   └── wireframe.schema.json
├── composer.json                 # Dependencies
├── README.md                     # This file
└── readme.txt                    # WordPress plugin header
```

## Security Features

- ✅ Nonce validation on all forms
- ✅ Input sanitization and validation
- ✅ Capability checks
- ✅ Rate limiting (10 requests/hour)
- ✅ Secure file uploads
- ✅ Security headers
- ✅ Error logging and monitoring

## Requirements

- WordPress 6.0+
- PHP 7.4+
- OpenAI API key
- `edit_theme_options` capability

## Changelog

### Version 0.2.3
- 🔒 Enhanced security with nonce validation
- 🛡️ Input sanitization and validation
- 🚫 Rate limiting to prevent abuse
- 📝 Improved error handling and logging
- 🎨 Better UI with loading states
- 🔄 GitHub Releases update system

### Version 0.2.0
- 🚀 Initial release
- 🤖 AI-powered layout generation
- 📱 Wireframe creation
- 🎨 YOOtheme Pro JSON compilation

## Support

- **Issues**: [GitHub Issues](https://github.com/samuelsamuraj/ai-layout-for-yootheme/issues)
- **Documentation**: [GitHub Wiki](https://github.com/samuelsamuraj/ai-layout-for-yootheme/wiki)
- **Releases**: [GitHub Releases](https://github.com/samuelsamuraj/ai-layout-for-yootheme/releases)

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

---

**Made with ❤️ by Samuraj ApS**
