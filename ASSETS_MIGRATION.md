# Assets Migration Guide

## OpenShelf Assets Conversion to Laravel 13

This document outlines the successful migration of old PHP assets to Laravel 13 standard structure.

## Directory Structure

### ✅ CSS Files
All CSS files have been converted and organized in `resources/css/`:

```
resources/css/
├── app.css                 # Main stylesheet (imports all components)
├── components.css          # Book cards and UI components
├── admin.css              # Admin panel layouts
└── profile.css            # Profile page styles
```

**Migration Details:**
| Old File | New Location | Status |
|----------|-------------|--------|
| `old/assets/css/style.css` | Consolidated into `components.css`, `admin.css`, `profile.css` | ✓ Done |
| `old/assets/css/ui.css` | Consolidated into all CSS files + `app.css` | ✓ Done |
| `old/assets/css/BookCardGrid.css` | `resources/css/components.css` | ✓ Done |
| `old/assets/css/admin.css` | `resources/css/admin.css` | ✓ Done |
| `old/assets/css/profile.css` | `resources/css/profile.css` | ✓ Done |

### ✅ JavaScript Files
JavaScript files are now in `resources/js/`:

```
resources/js/
├── app.js                    # Main application JS
├── ui.js                     # UI component interactions
└── book-interactions.js      # Book reviews and comments AJAX
```

**Migration Details:**
| Old File | New Location | Status |
|----------|-------------|--------|
| `old/assets/js/ui.js` | `resources/js/ui.js` | ✓ Done |
| `old/assets/js/book-interactions.js` | `resources/js/book-interactions.js` | ✓ Done |

### ✅ Static Assets (Images)
All images moved to `public/images/`:

```
public/images/
├── avatars/               # User profile pictures
├── pwa/                   # PWA icons and screenshots
│   └── screenshots/
├── default-book-cover.jpg # Default book cover image
├── logo-full.svg          # Full logo with wordmark
├── logo-icon.svg          # Icon-only logo
└── logo-wordmark.svg      # Text-only wordmark
```

**File Count:**
- Images: 4 SVG + 1 JPG + 2 folders
- Total size: ~13 MB (including PWA assets)

## Usage in Blade Templates

### Including CSS
CSS files are automatically loaded via `app.css` imports. No additional action needed.

### Including JavaScript
Load the JS files in your Blade templates:

```blade
@vite(['resources/js/app.js', 'resources/js/ui.js', 'resources/js/book-interactions.js'])
```

### Referencing Images
Use Laravel's `asset()` helper to reference images:

```blade
<!-- User Avatar -->
<img src="{{ asset('images/avatars/user.jpg') }}" alt="User">

<!-- Book Cover -->
<img src="{{ asset('images/default-book-cover.jpg') }}" alt="Book">

<!-- Logo -->
<img src="{{ asset('images/logo-full.svg') }}" alt="OpenShelf">
```

## CSS Organization

### components.css
Contains styles for:
- Book card grid and individual cards
- Card components with hover effects
- Badge styling (available, borrowed, reserved, returned)
- Responsive grid adjustments
- Dark mode support

**Usage:** Apply `.book-grid` and `.book-card` classes to display book collections.

### admin.css
Contains styles for:
- Admin sidebar navigation
- Admin header and main layout
- Admin forms and tables
- Dark theme support with CSS variables

**Usage:** Wrap admin pages with `.admin-layout`, `.admin-sidebar`, `.admin-main` classes.

### profile.css
Contains styles for:
- Profile hero section with gradient animation
- Glass-morphism card design
- User stats display
- Information grid (department, session, hall, room)
- Action buttons (edit, add, contact)
- Tab interface for profile sections
- Dark mode overrides

**Usage:** Use `.profile-container`, `.glass-card`, `.profile-avatar` classes for profile pages.

## Theme Variables

All CSS files use CSS custom properties for theming:

```css
/* Light Theme (Default) */
:root {
    --primary: #2C3E50;
    --secondary: #4C9F8A;
    --danger: #ef4444;
    --success: #10b981;
    /* ... more variables */
}

/* Dark Theme */
:root[data-theme="dark"] {
    --bg: #0f172a;
    --text: #f1f5f9;
    /* ... more overrides */
}
```

To activate dark theme in HTML:
```html
<html data-theme="dark">
```

## JavaScript Features

### ui.js
Provides interactive UI components:
- Mobile menu toggle
- Dropdown menus
- Notification system
- Modal dialogs
- Alert system
- Form validation
- Tab navigation
- Scroll effects

**Initialization:**
```javascript
const ui = new OpenShelfUI();
```

### book-interactions.js
Handles book-related AJAX operations:
- Submit reviews with ratings
- Post comments
- Like reviews/comments
- Delete reviews/comments
- Reply to comments
- Load more pagination
- Notification system

**Usage:**
```javascript
const bookInteractions = new BookInteractions(bookId, userId);
```

## Vite Asset Bundling

Assets are bundled via Vite when running:
```bash
npm run dev      # Development with hot reload
npm run build    # Production build
```

Configure Vite to include new JS/CSS:
```javascript
// vite.config.js
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/ui.js',
                'resources/js/book-interactions.js',
            ],
            refresh: true,
        }),
    ],
});
```

## File Size Summary

```
CSS Files:     ~30 KB (combined)
JS Files:      ~50 KB (combined, unminified)
Images:        ~13 MB (including PWA assets)
Total:         ~13 MB
```

After minification and gzip:
- CSS: ~8 KB
- JS: ~15 KB
- Images: Optimized via CDN caching

## Best Practices

1. **Image Optimization**
   - Convert JPGs to WebP for faster loading
   - Use `srcset` for responsive images
   - Lazy load images below the fold

2. **CSS Optimization**
   - PurgeCSS removes unused Tailwind classes
   - Component CSS is bundled with Tailwind
   - Dark theme uses CSS variables for efficiency

3. **JavaScript Optimization**
   - Tree-shake unused functions
   - Minify in production
   - Use async/defer attributes for script tags

## Migration Checklist

- ✅ All CSS files migrated to `resources/css/`
- ✅ All JS files migrated to `resources/js/`
- ✅ All images migrated to `public/images/`
- ✅ app.css updated to import component stylesheets
- ✅ Theme variables configured
- ✅ Dark mode support enabled
- ✅ Responsive design maintained
- ✅ File structure follows Laravel conventions

## Next Steps

1. Import JS files in your Blade templates using `@vite()`
2. Test all components in light and dark modes
3. Optimize images using optimization tools
4. Configure Vite for production builds
5. Deploy and monitor CSS/JS loading performance

## Support

For questions about specific components:
- Book cards: See `components.css`
- Admin layouts: See `admin.css`
- Profile pages: See `profile.css`
- UI interactions: See `resources/js/ui.js`
- Book features: See `resources/js/book-interactions.js`
