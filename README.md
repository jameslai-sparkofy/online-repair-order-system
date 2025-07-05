# 線上維修單 (Online Repair Order System)

A comprehensive WordPress plugin for managing repair orders with mobile/desktop support, file uploads, digital signatures, and GitHub auto-sync.

## Quick Start

1. **Read CLAUDE.md first** - Contains essential rules for Claude Code
2. Follow the pre-task compliance checklist before starting any work
3. Use proper module structure under `src/main/php/` and `src/main/js/`
4. Commit after every completed task

## Features

### 🔧 **Core Functionality**
- **Mobile & Desktop Interface**: Responsive design for field workers and office staff
- **Repair Order Management**: Complete lifecycle management with auto-generated IDs
- **Multi-Photo Upload**: Upload multiple photos per repair order
- **Digital Signatures**: Construction site confirmation workflow
- **Advanced Filtering**: Date and site-based filtering with shareable URLs
- **GitHub Auto-Sync**: Automated deployment via webhooks

### 📋 **Repair Order Fields**
- Auto-generated repair order number
- Date (defaults to today)
- Site name (auto-fills from previous entries)
- Building/Floor/Unit information
- Repair reason
- Worker assignment
- Cost amount
- Multiple photo uploads
- Digital signature field
- Shareable URL for site confirmation

### 🔍 **Management Features**
- Repair order listing with filtering
- Date-based filtering
- Site-based filtering
- URL-based filtering (shareable links)
- Professional, clean interface design

## Project Structure

```
線上維修單/
├── CLAUDE.md                    # Essential Claude Code rules
├── README.md                    # Project documentation
├── src/
│   ├── main/
│   │   ├── php/                 # WordPress plugin PHP code
│   │   │   ├── core/            # Core plugin functionality
│   │   │   ├── api/             # REST API endpoints
│   │   │   ├── models/          # Data models
│   │   │   ├── services/        # Business logic services
│   │   │   └── utils/           # Utility functions
│   │   ├── js/                  # Frontend JavaScript
│   │   │   ├── components/      # UI components
│   │   │   ├── services/        # API services
│   │   │   └── utils/           # JS utilities
│   │   ├── css/                 # Stylesheets
│   │   └── resources/
│   │       ├── config/          # Configuration files
│   │       ├── assets/          # Static assets
│   │       └── templates/       # WordPress templates
│   └── test/                    # Test files
├── docs/                        # Documentation
├── tools/                       # Development tools
├── examples/                    # Usage examples
└── output/                      # Generated output files
```

## Development Guidelines

- **Always search first** before creating new files
- **Extend existing** functionality rather than duplicating  
- **Use Task agents** for operations >30 seconds
- **Single source of truth** for all functionality
- **WordPress standards** - Follow WordPress coding conventions
- **Security first** - Sanitize inputs, escape outputs
- **GitHub backup** - Push after every commit

## WordPress Plugin Architecture

This plugin follows WordPress best practices:

- **Plugin Header**: Proper WordPress plugin metadata
- **Hook Integration**: WordPress actions and filters
- **REST API**: Custom endpoints for frontend interaction
- **Database Schema**: Custom tables for repair order data
- **Security**: Capability checks and data validation
- **Auto-Updates**: GitHub webhook integration

## Installation

1. Upload plugin files to `/wp-content/plugins/repair-orders/`
2. Activate the plugin through WordPress admin
3. Configure GitHub webhook for auto-updates
4. Set up user permissions for repair order management

## GitHub Integration

This plugin includes automatic GitHub synchronization:

- **Webhook URL**: `/wp-json/repair-orders/v1/github-webhook`
- **Auto-Deploy**: Automatic updates when code is pushed to GitHub
- **Security**: Webhook signature verification
- **Rollback**: Automatic backup before updates

## Development Commands

```bash
# WordPress development
wp plugin activate repair-orders
wp plugin deactivate repair-orders

# Database operations
wp db create
wp db reset --yes

# GitHub operations
git add . && git commit -m "feat: description" && git push origin main

# Testing
phpunit tests/
npm test
```

## Technical Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+
- Modern web browser (Chrome, Firefox, Safari, Edge)
- GitHub account (for auto-sync feature)

## License

[Add your license information here]

---

**Template by Chang Ho Chien | HC AI 說人話channel | v1.0.0**  
📺 Tutorial: https://youtu.be/8Q1bRZaHH24