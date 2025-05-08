# DesignHIve Learning Platform

DesignHIve is a comprehensive e-learning platform for graphic design education, featuring role-based access, interactive learning materials, assignments, quizzes, and a gamification system.

## Installation

1. Create MySQL database:
```sql
CREATE DATABASE designhive;
```

2. Import the database:
```bash
mysql -u your_username -p designhive < database/designhive.sql
```

3. Set up the project in your web server:
```bash
# Create debug directory in your web root
mkdir /var/www/html/debug

# Copy project files
cp -r * /var/www/html/debug/
```

## Directory Structure

```
debug/
├── admin/                 # Admin interface
├── teacher/              # Teacher interface
├── student/              # Student interface
├── includes/             # Shared components
├── config/              # Configuration files
├── database/            # Database files
└── assets/             # Static resources
```

## Test Accounts

All accounts use the same password: `password123`

```
Role: Administrator
Username: admin
Email: admin@designhive.com
Access: /debug/admin/dashboard.php

Role: Teacher
Username: teacher
Email: teacher@designhive.com
Access: /debug/teacher/dashboard.php

Role: Student
NIS: 2024001
Email: student1@designhive.com
Access: /debug/student/dashboard.php

Role: Student
NIS: 2024002
Email: student2@designhive.com
Access: /debug/student/dashboard.php
```

## Features by Role

### Admin Features (/debug/admin/*)
- User management
- Learning material management (CRUD)
- Platform statistics monitoring
- System configuration

### Teacher Features (/debug/teacher/*)
- Grade student submissions
- Provide feedback
- Monitor student progress
- Access learning materials

### Student Features (/debug/student/*)
- Access learning materials:
  * Text & image content
  * Video tutorials
  * Interactive minigames
- Submit assignments
- Take chapter quizzes
- Take final exam
- Earn points and badges
- Track learning progress
- Download completion certificate

## Technologies Used

- PHP 7.4+
- MySQL 5.7+
- Tailwind CSS
- Font Awesome Icons
- Google Fonts
- TinyMCE Editor
- jQuery & jQuery UI

## Development Setup

1. Clone the repository into debug directory
2. Create database and import designhive.sql
3. Configure database connection in config/config.php
4. Access the platform through: http://your-domain/debug/

## Testing Workflow

1. Access login page: http://your-domain/debug/login.php

2. For Admin/Teacher:
   - Click "Admin & Guru" button
   - Login with username/password
   - Admin: admin/password123
   - Teacher: teacher/password123

3. For Students:
   - Click "Siswa" button
   - Login with NIS/password
   - Example: 2024001/password123

## Production Deployment

Before deploying to production:

1. Change all default passwords
2. Update database credentials
3. Enable HTTPS
4. Configure proper file permissions
5. Set up backup system
6. Enable error logging
7. Optimize for performance

## Security Considerations

- All passwords are hashed using bcrypt
- SQL injection prevention through prepared statements
- XSS protection implemented
- CSRF tokens used for forms
- File upload validation
- Role-based access control

## Support

For issues and questions:
1. Check the documentation
2. Review error logs
3. Contact system administrator

## License

Copyright © 2024 DesignHIve Learning Platform. All rights reserved.
