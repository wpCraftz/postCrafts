# PostCrafts - Technical Documentation

## Plugin Overview

**PostCrafts** is a WordPress Gutenberg block plugin that provides advanced post grid and list layout capabilities with sophisticated query building, AJAX pagination, and extensive styling options.
---

## System Requirements

### Minimum Requirements
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or greater

---

## Core Features
1. **Post Grid Block** - Display posts in customizable grid layouts
2. **Post List Block** - Display posts in list view layouts

---

## Technical Architecture

### File Structure

```
postCrafts/
├── post-crafts.php              # Main plugin entry point
├── package.json                 # NPM dependencies & scripts
├── readme.txt                   # WordPress.org readme
├── composer.json                # PHP dependencies
│
├── inc/                         # PHP Backend
│   ├── classes/                 # Core PHP classes
│   │   ├── class-plugin.php     # Main plugin manifest (singleton)
│   │   ├── class-blocks.php     # Block registration & management
│   │   ├── class-api.php        # REST API endpoints
│   │   ├── class-assets.php     # Asset enqueue & management
│   │   ├── class-admin.php      # Admin functionality
│   │   ├── class-media.php      # Media handling
│   │   └── class-style-loader.php  # Dynamic CSS loading
│   ├── helpers/                 # Helper functions
│   │   ├── autoloader.php       # PSR-4 autoloader
│   │   └── custom-functions.php # Utility functions
│   ├── templates/               # PHP rendering templates
│   │   └── block-templates/     # Block-specific templates
│   └── traits/                  # PHP traits
│       └── trait-singleton.php  # Singleton pattern implementation
│
├── src/                         # React/JavaScript Source
│   ├── index.js                 # Main entry point
│   ├── blocks/                  # Gutenberg block definitions
│   │   ├── post-grid/           # Post Grid block
│   │   │   ├── index.js
│   │   │   ├── edit.js
│   │   │   ├── attributes.js
│   │   │   └── style.scss
│   │   └── post-list/           # Post List block
│   │       ├── index.js
│   │       ├── edit.js
│   │       ├── attributes.js
│   │       └── style.scss
│   ├── components/              # Reusable React components
│   │   ├── color-control/       # Color & gradient picker
│   │   ├── query-builder/       # Post query builder UI
│   │   ├── pagination-settings/ # Pagination configuration
│   │   ├── grid-settings.js     # Grid layout controls
│   │   ├── range.js             # Custom range input
│   │   ├── excerpt.js           # Excerpt settings
│   │   └── alignment.js         # Alignment utilities
│   ├── editor/                  # Editor-specific logic
│   │   ├── style-generator/     # Dynamic CSS generation
│   │   ├── dynamic-attributes/  # Block attribute management
│   │   └── css-manager.js       # CSS optimization & caching
│   ├── hooks/                   # Custom React hooks
│   ├── libs/                    # Utility libraries
│   └── scripts/                 # Frontend JavaScript
│       └── pagination.js        # AJAX pagination handler
│
├── build/                       # Compiled output (webpack)
├── assets/                      # Static assets
│   ├── icons/                   # Block icons
│   └── images/                  # Plugin images
├── languages/                   # Translation files
│   └── post-crafts.pot          # POT template
└── vendor/                      # PHP dependencies (Composer)
```

### PHP Backend Architecture

#### Constants Defined (post-crafts.php:23-28)
```php
POST_CRAFTS_PATH          # Plugin directory path
POST_CRAFTS_URL           # Plugin URL
POST_CRAFTS_BUILD         # Build directory path
POST_CRAFTS_REST_NAMESPACE # 'post-crafts/v1'
POST_CRAFTS_VERSION       # '1.0.0'
```

#### Class Structure

**Namespace:** `PostCrafts\Blocks\Inc`

**Core Classes:**
1. **Plugin** (`class-plugin.php`) - Main manifest using singleton pattern
2. **Blocks** (`class-blocks.php`) - Block registration and server-side rendering
3. **Api** (`class-api.php`) - REST API endpoints for style management
4. **Assets** (`class-assets.php`) - Script/style enqueuing
5. **Admin** (`class-admin.php`) - Admin interface and settings
6. **Media** (`class-media.php`) - Featured image and media handling
7. **StyleLoader** (`class-style-loader.php`) - Dynamic CSS injection

**Design Patterns:**
- **Singleton Pattern** - Plugin, API, Assets classes
- **PSR-4 Autoloading** - Automatic class loading
- **Hook System** - WordPress action/filter integration
