# CSL Laravel Starter Kit

> 💡 **Demo:** You can preview the full version of this project at: [https://vuexy.phuongluong.space](https://vuexy.phuongluong.space)
>
> 📦 **Full Source Code:** View the complete implementation here: [https://bitbucket.org/corsivalabpteltd/vuexy\_full\_version](https://bitbucket.org/corsivalabpteltd/vuexy_full_version)

**Version:** 1.0.1

A Laravel starter kit designed for CSL internal projects. Includes ready-to-use folder structure, permissions, frontend layout (Inertia.js + Vue 3), and common essential packages.

---

## System Requirements

* PHP **8.2**
* Composer
* Node.js **>= 20.10.0**
* MySQL / PostgreSQL
* NPM or Yarn

---

## Installation

Follow the steps below to set up your project from the starter kit:

### Step 1: Clone and Initialize Your Project

> ⚠️ **Important:** Do not commit directly to the original repository. Instead, create your own Git repository using this starter kit as a base.
>
> ⚠️ **Note:** Make sure to replace `your-project-name` with your desired folder name, and `your-repo-url` with the SSH or HTTPS URL of your new Git repository.

```bash
git clone git@bitbucket.org:corsivalabpteltd/csl_laravel_starter_kit.git your-project-name
cd your-project-name
rm -rf .git
git init
git remote add origin git@your-repo-url.git
git add .
git commit -m "Initial commit from starter kit"
git push -u origin main
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

### Step 3: Set Up Environment File

```bash
cp .env.example .env
```

Update the `.env` file with your database credentials, app name, mail configuration, etc.

### Step 4: Generate Application Key

```bash
php artisan key:generate
```

### Step 5: Set Up Database

Make sure your database is created and credentials are correct in `.env`.

```bash
php artisan migrate --seed
```

After running the seeder, you can log in using the default admin credentials:

* **Email:** [admin@angiapms.com](mailto:admin@angiapms.com)
* **Password:** Password123

### Step 6: Install Frontend Dependencies

```bash
npm install
npm run dev     # Use npm run build for production
```

### Step 7: Start the Laravel Development Server

```bash
php artisan serve
```

### Step 8: Set Up Roles and Permissions

After completing the basic setup, you must define roles and permissions appropriate for your new project.

> ⚠️ **Important:** Roles and permissions must be manually created via the Admin Panel.

#### How to Create Roles & Permissions

1. Log in using the default admin account:

    * **Email:** [admin@angiapms.com](mailto:admin@angiapms.com)
    * **Password:** `Password123`

2. Go to the **Admin Panel** > **User Management** > **Roles**.

3. Create necessary roles (e.g., `Admin`, `Manager`, `User`) based on your project requirements.

4. Assign appropriate **permissions** to each role.

5. You can then assign these roles to new or existing users.

---

## API Documentation (Swagger UI)

If your project includes an API and uses [Laravel Swagger](https://github.com/DarkaOnLine/L5-Swagger) or a similar package, you can enable auto-generated documentation as follows:

### Step 1: Install Swagger (if not already installed)

```bash
composer require darkaonline/l5-swagger
```

### Step 2: Publish the config

```bash
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
```

### Step 3: Generate the Swagger documentation

```bash
php artisan l5-swagger:generate
```

### Step 4: Access the documentation

Open your browser and visit:

```
http://127.0.0.1:8000/api/documentation
```

> ⚠️ Make sure to define your routes and controllers with proper OpenAPI annotations.

---

## Frontend Configuration

You can customize the look and feel of the application through the `themeConfig.js` file located at the root of the project.

This file controls layout settings, navigation type, theme behavior, supported languages, icon rendering, and more.

### Key Properties

* **App Title & Logo**: Modify the `title` and `logo` settings
* **Layout Settings**: Adjust `contentWidth`, `contentLayoutNav`, and `overlayNavFromBreakpoint`
* **Theme & Skin**: Toggle between light/dark/system themes and skins using `theme` and `skin`
* **Internationalization (i18n)**: Enable/disable languages and set default locale in the `i18n` section
* **Navbar & Footer**: Set type (`Sticky`, `Static`, etc.) and appearance of `navbar` and `footer`
* **Navigation Sidebar**: Configure collapsed state, icons, and color scheme in `verticalNav`
* **Icons**: Define default icons used across the UI in the `icons` section

Example:

```js
export const { themeConfig, layoutConfig } = defineThemeConfig({
    app: {
        title: 'vuexy',
        logo: h('div', { innerHTML: logo }),
        theme: 'system',
        skin: Skins.Default,
        i18n: {
            enable: false,
            defaultLocale: 'en',
        },
    },
    navbar: {
        type: NavbarType.Sticky,
    },
    footer: {
        type: FooterType.Static,
    },
});
```

For more options and advanced customization, refer to the comments in `themeConfig.js`.

---

## Color Theme Customization

You can modify the application's primary and theme colors in the file:

**`resources/js/plugins/vuetify/theme.js`**

### How to Change Primary Colors

The following constants define the primary colors:

```js
export const staticPrimaryColor = '#7367F0'
export const staticPrimaryDarkenColor = '#675DD8'
```

Update these values to apply your own branding colors.

### How to Customize Light and Dark Theme Palettes

Within the `themes` object, you can customize both `light` and `dark` themes:

```js
export const themes = {
    light: {
        dark: false,
        colors: {
            primary: staticPrimaryColor,
            background: '#F8F7FA',
            surface: '#fff',
            ... // more colors
        },
        variables: {
            border-color: '#2F2B3D',
... // more CSS variables
}
},
dark: {
    dark: true,
        colors: {
        primary: staticPrimaryColor,
            background: '#25293C',
            surface: '#2F3349',
    ... // more colors
    },
    variables: {
        border-color: '#E1DEF5',
    ... // more CSS variables
    }
}
}
```

You can modify any of the `colors` and `variables` fields to match your design system.

After updating, changes will apply across all Vuetify components.

---

## Frontend Setup (Vue 3 + Inertia.js)

```bash
npm install
npm run dev      # or npm run build for production
```

---

## Useful Commands

### Run Laravel Development Server

```bash
php artisan serve
```

---
