# HRMS (Human Resource Management System)

This is a Human Resource Management System built with Laravel. It is designed to manage employee information, attendance, leaves, and more.

## Features

*   **Employee Management:** Add, edit, and view employee information.
*   **Attendance Tracking:** Import attendance data from ZKTeco devices and generate daily attendance reports.
*   **Leave Management:** Manage employee leave requests.
*   **Shift Management:** Define and manage employee shifts.
*   **Holiday Management:** Manage a list of holidays.

## Installation and Setup

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/ahsan-ul-alam/attendance_automation.git
    cd hrms
    ```

2.  **Install Composer dependencies:**
    ```bash
    composer install
    ```

3.  **Install NPM dependencies:**
    ```bash
    npm install
    ```

4.  **Create your environment file:**
    ```bash
    cp .env.example .env
    ```

5.  **Generate an application key:**
    ```bash
    php artisan key:generate
    ```

6.  **Configure your database:**
    Update the `DB_*` variables in your `.env` file with your database credentials.

7.  **Run database migrations:**
    ```bash
    php artisan migrate
    ```

8.  **(Optional) Seed the database:**
    ```bash
    php artisan db:seed
    ```

9.  **Run the development server:**
    ```bash
    php artisan serve
    ```

10. **Run the Vite development server:**
    ```bash
    npm run dev
    ```

## Usage

### Artisan Commands

*   **Import attendance data:**
    ```bash
    php artisan attendance:import
    ```

*   **Rollup attendance data:**
    ```bash
    php artisan attendance:rollup
    ```

### Web Interface

You can access the application in your web browser at the address provided by `php artisan serve` (usually `http://127.0.0.1:8000`).

## Dependencies

*   [Laravel](https://laravel.com/)
*   [ZKTeco Library](https://github.com/racsowebsolutions/laravel-zkteco) (or similar - based on `ZKTecoImporter.php`)
*   [Vite](https://vitejs.dev/)
