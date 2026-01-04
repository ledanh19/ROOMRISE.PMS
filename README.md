# ROOMRISE.PMS

**ROOMRISE.PMS** - Property Management System for hotel and accommodation management

## Overview

ROOMRISE.PMS is a comprehensive property management system designed for hotels, resorts, and accommodation businesses. Built with Laravel and Vue.js, it provides a modern, intuitive interface for managing bookings, rooms, customers, and operations.

## Technology Stack

### Backend
- **PHP 8.2** with Laravel Framework
- **MySQL/PostgreSQL** Database
- RESTful API Architecture
- Inertia.js for seamless SPA experience

### Frontend
- **Vue 3** with Composition API
- **Vuetify 3** Material Design Components
- **Inertia.js** for server-driven single page applications
- **Vite** for fast development and optimized builds
- **TypeScript** support

### Key Features
- 🏨 **Property Management**: Manage multiple properties, rooms, and room types
- 📅 **Booking System**: Complete booking lifecycle management
- 👥 **Customer Management**: Track customer information and booking history
- 💰 **Financial Management**: Income, expenses, payments, and invoicing
- 📊 **Reporting & Analytics**: Comprehensive reports and dashboards
- 🔗 **Channel Manager Integration**: Channex integration for OTA distribution
- 🔐 **Role-Based Access Control**: Granular permissions and user management
- 📱 **Responsive Design**: Works seamlessly on desktop, tablet, and mobile
- 🌐 **Multi-language Support**: i18n ready (Vietnamese, English)

## System Requirements

- PHP **8.2** or higher
- Composer
- Node.js **>= 20.10.0**
- MySQL **5.7+** / PostgreSQL **12+**
- NPM or PNPM

## Installation

### Step 1: Clone the Repository

```bash
git clone https://github.com/ledanh19/ROOMRISE.PMS.git
cd ROOMRISE.PMS
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

### Step 3: Set Up Environment File

```bash
cp .env.example .env
```

Update the `.env` file with your database credentials, app name, and other configurations.

### Step 4: Generate Application Key

```bash
php artisan key:generate
```

### Step 5: Set Up Database

1. Create a new database for the project
2. Import the database schema from `pms(2).sql`:

```bash
mysql -u your_username -p your_database_name < "pms(2).sql"
```

Or use your preferred database management tool to import the SQL file.

### Step 6: Install Frontend Dependencies

```bash
npm install
# or
pnpm install
```

### Step 7: Build Frontend Assets

For development:
```bash
npm run dev
```

For production:
```bash
npm run build
```

### Step 8: Start the Laravel Development Server

```bash
php artisan serve
```

The application will be available at `http://127.0.0.1:8000`

## Default Login Credentials

After database setup, you can log in using:

- **Email:** admin@angiapms.com
- **Password:** Password123

⚠️ **Important:** Change these credentials immediately after first login for security purposes.

## Project Structure

```
ROOMRISE.PMS/
├── app/                    # Laravel application code
├── bootstrap/              # Laravel bootstrap files
├── config/                 # Configuration files
├── database/               # Migrations, seeders, factories
├── public/                 # Public assets
├── resources/              # Frontend resources
│   ├── js/                 # Vue.js components and logic
│   ├── styles/             # SCSS stylesheets
│   └── views/              # Blade templates
├── routes/                 # Application routes
│   ├── Web/                # Web routes (organized by feature)
│   └── Api/                # API routes
├── storage/                # Application storage
├── tests/                  # Test files
├── vendor/                 # PHP dependencies
├── node_modules/           # Node.js dependencies
├── pms(2).sql             # Database schema and data
├── composer.json           # PHP dependencies
├── package.json            # Node.js dependencies
├── vite.config.js          # Vite configuration
└── themeConfig.js          # Theme configuration
```

## Key Modules

- **Dashboard**: Overview of bookings, occupancy, and revenue
- **Bookings**: Create, manage, and track reservations
- **Rooms**: Room inventory and availability management
- **Customers**: Customer database and history
- **Payments**: Payment processing and tracking
- **Income/Expense**: Financial transaction management
- **Reports**: Various business intelligence reports
- **Channel Manager**: OTA integration via Channex
- **User Management**: Users, roles, and permissions
- **Settings**: Property configuration and system settings

## API Documentation

API documentation is available via Swagger UI:

```bash
php artisan l5-swagger:generate
```

Then visit: `http://127.0.0.1:8000/api/documentation`

## Configuration

### Theme Customization

Edit `themeConfig.js` to customize:
- Application title and logo
- Layout settings
- Theme colors
- Navigation behavior
- Language settings

### Color Scheme

Edit `resources/js/plugins/vuetify/theme.js` to customize primary colors and theme palettes.

## Development

### Running Tests

```bash
php artisan test
```

### Code Linting

```bash
npm run lint
```

### Building for Production

```bash
npm run build
php artisan optimize
```

## Deployment

For production deployment:

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false` in `.env`
3. Configure proper database credentials
4. Run `composer install --optimize-autoloader --no-dev`
5. Run `npm run build`
6. Run `php artisan config:cache`
7. Run `php artisan route:cache`
8. Run `php artisan view:cache`
9. Set proper file permissions for `storage/` and `bootstrap/cache/`

## Support

For issues, questions, or contributions, please create an issue on the GitHub repository.

## License

This project is proprietary software. All rights reserved.

## Credits

Built with:
- [Laravel](https://laravel.com/)
- [Vue.js](https://vuejs.org/)
- [Vuetify](https://vuetifyjs.com/)
- [Inertia.js](https://inertiajs.com/)
- [Vite](https://vitejs.dev/)

---

**ROOMRISE.PMS** - Empowering hospitality businesses with modern technology.
