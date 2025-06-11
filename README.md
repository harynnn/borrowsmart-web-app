
Built by https://www.blackbox.ai

---

# BorrowSmart

## Project Overview
**BorrowSmart** is a web application designed for students and staff to manage the borrowing and returning of musical instruments. The application allows users to browse available instruments, check their borrowing history, and handle returns seamlessly. It also includes an admin dashboard for managing users and instruments.

## Installation
To set up the BorrowSmart application, follow these steps:

1. Clone the repository to your local machine:
   ```bash
   git clone <repository_url>
   cd borrowsmart
   ```

2. Install the necessary database management system (MySQL or MariaDB).

3. Create a database called `borrowsmart` and import the SQL schema if provided.

4. Set up the database connection in `database/db_connect.php`:
   ```php
   <?php
   $pdo = new PDO('mysql:host=localhost;dbname=borrowsmart;charset=utf8', 'username', 'password');
   $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   ?>
   ```

5. Ensure the server has PHP and the necessary PHP extensions installed.

6. Launch a PHP server (for development):
   ```bash
   php -S localhost:8000
   ```

7. Open a web browser and navigate to `http://localhost:8000`.

## Usage
- **Homepage**: Users can see an overview of the application and links to login or register.
- **Login / Register**: New users can register for an account, and existing users can log in.
- **Dashboard**: Users will see their borrowing statistics and recent activities.
- **Borrowing**: Users can view available instruments and borrow them.
- **Returning Instruments**: Users can return instruments they have borrowed.
- **History**: Users can view their borrowing history.

Admin users can manage users and instruments, view reports, and track borrowing status.

## Features
- User authentication (login/register)
- Instrument borrowing and returning functionality
- Role-based access (Admin, Staff, Student)
- Borrowing history and current borrowing status tracking
- User dashboard with statistics
- Responsive design using Tailwind CSS

## Dependencies
Even though there is no explicit `package.json` file in the provided content, the application utilizes:
- **Tailwind CSS** for styling, linked directly via CDN.
- **PHP PDO** for database interactions, requiring a PHP-enabled server with PDO extensions.

## Project Structure
The project is structured as follows:
```
/borrowsmart
│
├── database/
│   └── db_connect.php                 # Database connection script
│
├── admin/
│   ├── users.php                       # Admin user management
│   ├── instruments.php                 # Admin instrument management
│   └── reports.php                     # Admin reports
│
├── staff/
│   ├── staff_dashboard.php             # Staff dashboard
│   └── instruments.php                 # Staff instrument management
│
├── includes/
│   ├── otp.php                         # OTP handling for secure login
│   └── csfr.php                        # CSRF token handling function
│
├── footer.php                          # Common footer file included in templates
├── homepage.php                        # Homepage - welcome and navigation
├── login.php                           # User login page with OTP support
├── logout.php                          # Logout user and destroy session
├── profile.php                         # User profile management
├── register.php                        # User registration page
├── return.php                          # Return instruments page
├── history.php                         # User borrowing history
├── dashboard.php                       # User dashboard showing stats and activities
├── borrow.php                          # Borrow instruments page
└── index.php                           # Redirects to the homepage
```
## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.