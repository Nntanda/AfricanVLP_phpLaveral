# AU VLP Laravel Migration Project

This project involves migrating two existing CakePHP applications to separate Laravel applications while maintaining their shared database relationship.

## Project Structure

The migration creates two separate Laravel applications:

### 1. Admin Laravel Application (`admin-laravel-app/`)
- **Purpose**: Administrative interface for system management
- **Source**: Migrated from the `admin/` CakePHP folder
- **Features**: 
  - User management
  - Organization management
  - Content management (news, events, resources)
  - System administration
  - Analytics and reporting

### 2. Client Laravel Application (`client-laravel-app/`)
- **Purpose**: Client/volunteer-facing interface with organization-specific features
- **Source**: Migrated from the `Well-known/` CakePHP folder
- **Features**:
  - User registration and profiles
  - Organization membership
  - Volunteering opportunities
  - Forum discussions
  - Private messaging
  - Event participation
  - Resource access
  - Multi-language support

## Shared Database

Both applications share the same MySQL database (`hruaif93_au_vlp.sql`) which contains:
- User authentication and profiles
- Organizations and memberships
- Events and news
- Volunteering system
- Communication features
- All existing CakePHP data

## Key Features

### Admin Application Features
- **Dashboard**: System overview with statistics and recent activity
- **User Management**: Create, edit, and manage user accounts
- **Organization Management**: Manage organizations and their settings
- **Content Management**: Manage news, events, and resources
- **Security**: Role-based access control and audit logging
- **Export**: CSV export functionality for all data

### Client Application Features
- **Personalized Dashboard**: Content based on user interests and organizations
- **Organization Portals**: Organization-specific interfaces and features
- **Volunteering System**: Browse and apply for volunteering opportunities
- **Forum System**: Organization-based discussion forums
- **Private Messaging**: User-to-user and organization communication
- **Event Management**: Event discovery and registration
- **Resource Library**: Access to organizational resources
- **Multi-language Support**: Internationalization with multiple languages

## Technology Stack

### Backend
- **Laravel 10.x** (PHP 8.1+)
- **MySQL** database (shared between applications)
- **Laravel Sanctum** for API authentication
- **Laravel Queue** for background jobs
- **Laravel Mail** with SendGrid integration

### Frontend
- **Blade** templating engine
- **Tailwind CSS** for styling
- **Alpine.js** for interactive components
- **Laravel Mix/Vite** for asset compilation

### Third-party Integrations
- **Cloudinary** for image management
- **Google Translate API** for multi-language support
- **SendGrid** for email delivery
- **Mobile Detection** for responsive design

## Database Schema

The applications maintain the existing CakePHP database schema with:
- 30+ tables including users, organizations, events, news, resources
- All foreign key relationships preserved
- CakePHP timestamp conventions (`created`/`modified` columns)
- Complete data integrity from original applications

## Installation & Setup

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL 5.7 or higher
- Node.js and NPM

### Admin Application Setup
```bash
cd admin-laravel-app
composer install
cp .env.example .env
php artisan key:generate
# Configure database settings in .env
php artisan migrate
php artisan serve --port=8001
```

### Client Application Setup
```bash
cd client-laravel-app
composer install
cp .env.example .env
php artisan key:generate
# Configure database settings in .env
php artisan migrate
php artisan serve --port=8002
```

## Environment Configuration

### Admin Application (.env)
```
APP_NAME="AU VLP Admin"
APP_URL=http://admin.au-vlp.local
DB_DATABASE=hruaif93_au_vlp
# ... other configurations
```

### Client Application (.env)
```
APP_NAME="AU VLP Client"
APP_URL=http://client.au-vlp.local
DB_DATABASE=hruaif93_au_vlp
# ... other configurations
```

## Deployment

The applications are designed to be deployed separately:
- **Admin Application**: Typically on a subdomain like `admin.au-vlp.org`
- **Client Application**: On the main domain like `au-vlp.org`

Both applications can be deployed on the same server or different servers, as long as they can access the shared MySQL database.

## Migration Status

### Completed Features
- ✅ Project structure setup
- ✅ Database configuration
- ✅ Basic models and relationships
- ✅ Authentication system foundation
- ✅ Route structure
- ✅ Controller foundations

### In Progress
- 🔄 Complete model relationships
- 🔄 Database migrations
- 🔄 View templates
- 🔄 Security enhancements

### Planned Features
- 📋 Complete UI/UX implementation
- 📋 Third-party service integrations
- 📋 Testing suite
- 📋 Performance optimization
- 📋 Production deployment

## Development Guidelines

### Code Standards
- Follow Laravel best practices
- Use PSR-12 coding standards
- Implement proper error handling
- Write comprehensive tests
- Document all public methods

### Security Considerations
- CSRF protection on all forms
- Input validation and sanitization
- Rate limiting on authentication
- Secure file uploads
- SQL injection prevention

### Performance Optimization
- Database query optimization
- Caching strategies
- Asset optimization
- Queue system for heavy operations

## Contributing

1. Follow the existing code structure
2. Maintain compatibility with shared database
3. Test changes in both applications
4. Update documentation as needed
5. Follow security best practices

## Support

For questions or issues related to this migration project, please refer to the project documentation or contact the development team.