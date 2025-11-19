# School Management System

A comprehensive web-based school management system built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

- **Authentication System**: Secure login/signup with role-based access control
- **User Roles**: Admin, DoS Social Affairs, Finance, DoS Exam, Teacher, Student
- **Student Management**: Track admissions, promotions, and graduations (Grades 7-9)
- **Exam Management**: Add, view, edit, sort, and export exam marks
- **Fee Management**: Track payments with percentage calculations and filtering
- **Club System**: Manage clubs with posts, comments, and likes
- **Notes Section**: Upload and download study materials
- **Report Cards**: Generate and manage student report cards
- **Account Settings**: User profile management
- **Dark/Light Theme**: Toggle between themes
- **Responsive Design**: Works on all devices

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP/MAMP (for local development)

### Setup Instructions

1. **Clone or download this project** to your web server directory
   - For XAMPP: `C:\xampp\htdocs\school-management-system`
   - For WAMP: `C:\wamp64\www\school-management-system`

2. **Create the database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `school_management`
   - Import the SQL file: `database/schema.sql`

3. **Configure database connection**
   - Open `config/database.php`
   - Update the database credentials if needed:
     \`\`\`php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'school_management');
     \`\`\`

4. **Set up file permissions**
   - Ensure the `uploads/` directory is writable:
     \`\`\`bash
     chmod -R 777 uploads/
     \`\`\`

5. **Access the application**
   - Open your browser and go to: `http://localhost/school-management-system`

6. **Login with default admin account**
   - Username: `admin`
   - Password: `admin123`

## Project Structure

\`\`\`
school-management-system/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   ├── main.js
│   │   └── theme.js
│   └── images/
├── config/
│   └── database.php
├── database/
│   └── schema.sql
├── includes/
│   ├── auth.php
│   ├── functions.php
│   ├── header.php
│   └── sidebar.php
├── uploads/
│   ├── profiles/
│   ├── notes/
│   ├── reports/
│   └── clubs/
├── index.php
├── login.php
├── signup.php
├── dashboard.php
├── students.php
├── exams.php
├── fees.php
├── clubs.php
├── notes.php
├── reports.php
├── settings.php
├── logout.php
└── README.md
\`\`\`

## Usage

### For Administrators
- Manage all students, exams, fees, and clubs
- Add new users with different roles
- Generate and export reports
- Upload study materials

### For Teachers
- View and manage exams
- Upload notes and study materials
- Participate in club activities

### For Students
- View exam results
- Check fee payment status
- Join clubs and participate in discussions
- Download study materials
- View report cards

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention using prepared statements
- XSS protection with input sanitization
- Session-based authentication
- Role-based access control

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Troubleshooting

### Database connection error
- Check if MySQL service is running
- Verify database credentials in `config/database.php`
- Ensure database exists and schema is imported

### File upload not working
- Check `uploads/` directory permissions
- Verify PHP `upload_max_filesize` and `post_max_size` settings

### Theme not switching
- Clear browser cache
- Check if JavaScript is enabled
- Verify `assets/js/theme.js` is loaded

## Future Enhancements

- Email notifications
- SMS integration
- Attendance tracking
- Timetable management
- Online exam system
- Parent portal
- Mobile app

## License

This project is open-source and available for educational purposes.

## Support

For issues and questions, please create an issue in the project repository.
