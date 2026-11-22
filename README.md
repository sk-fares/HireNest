# HireNest – Online Job Portal

HireNest is a web-based job portal developed using PHP and MySQL. It connects job applicants with companies and provides an administration panel to manage the platform.

## Features

### Applicant
- Register and log in.
- Browse and search available jobs.
- View job descriptions and company information.
- Apply for jobs with a resume and cover letter.
- Track application status.
- Withdraw submitted applications.
- Manage applicant profile.

### Company
- Register and log in.
- Create and publish job vacancies.
- Edit, close, and reopen job postings.
- View applications received for posted jobs.
- Shortlist or reject applicants.
- Download applicant resumes.

### Admin
- Secure admin login.
- View dashboard statistics.
- Manage applicant and company accounts.
- Activate or deactivate accounts.
- Manage job postings.
- View all applications.

## Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Local Development Environment:** XAMPP
- **Code Editor:** Visual Studio Code

## Project Structure

```text
HireNest/
├── admin/
├── applicant/
├── company/
├── config/
├── includes/
├── assets/
├── storage/
│   └── resumes/
├── database.sql
├── index.php
├── jobs.php
├── job-details.php
├── register.php
├── login.php
├── logout.php
└── README.md
```

## Requirements

Install the following software before running the project:

- XAMPP (Apache, PHP, and MySQL)
- Web browser
- Git (optional, for cloning the repository)

## Installation and Setup

### 1. Download the Project

Clone the repository:

```bash
git clone https://github.com/sk-fares/HireNest.git
```

Alternatively, download the ZIP file from GitHub and extract it.

### 2. Move the Project to XAMPP

Copy the project folder into:

```text
C:\xampp\htdocs\
```

The resulting path should be:

```text
C:\xampp\htdocs\HireNest
```

### 3. Start XAMPP

Open XAMPP Control Panel and start:

- Apache
- MySQL

### 4. Create the Database

1. Open phpMyAdmin:

   http://localhost/phpmyadmin/

2. Click **New**.
3. Enter the database name:

   `hirenest_db`

4. Select the `utf8mb4` character set/collation if available.
5. Click **Create**.

### 5. Import Database Tables

1. Select the newly created `hirenest_db` database.
2. Click the **Import** tab.
3. Click **Choose File**.
4. Select `database.sql` from the project folder.
5. Keep the format as SQL.
6. Click **Import** or **Go**.

The SQL file creates the required database tables:

- `users`
- `companies`
- `applicant_profiles`
- `jobs`
- `applications`

The file contains the database structure and relationships, not sample user accounts or passwords.

### 6. Configure Database Connection

Open:

```text
config/database.php
```

Configure the database credentials according to your local MySQL setup.

For a default XAMPP installation:

```php
$host = 'localhost';
$dbname = 'hirenest_db';
$username = 'root';
$password = '';
```

Use the corresponding values if your MySQL username or password is different.

### 7. Run the Application

Open your browser and visit:

http://localhost/HireNest/

HireNest should now be running locally.

## Admin Account Setup

Admin accounts cannot be created through public registration.

The project administrator must provision an admin account securely. The one-time development setup script is not included in the public repository.

## Security Notes

- Passwords are stored using PHP password hashing.
- Database operations use PDO prepared statements.
- Role-based access control protects applicant, company, and admin pages.
- CSRF protection is used for important form actions.
- Uploaded resumes are stored separately from public application pages.
- Do not commit database credentials, private files, or uploaded applicant resumes to GitHub.

## Project Status

Developed as an academic project.