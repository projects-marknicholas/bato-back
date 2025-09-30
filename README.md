# Backend Setup and Usage Guide

This guide will walk you through how to set up and run the backend of this project using **XAMPP**, **Composer**, and **VS Code**.  

---

## Prerequisites

Before starting, make sure you have the following installed:

- [XAMPP](https://www.apachefriends.org/index.html) (for Apache & MySQL)
- [Composer](https://getcomposer.org/download/)
- [Visual Studio Code](https://code.visualstudio.com/)

---

## Step 1: Install Composer

1. Go to [Composer Download](https://getcomposer.org/download/).
2. Click **Composer-Setup.exe** to download the installer.
3. Run the installer and follow the prompts to finish the installation.

> ✅ Composer is a PHP dependency manager that will help you install the required packages for the backend.

---

## Step 2: Start XAMPP and Create Database

1. Open **XAMPP Control Panel**.
2. Click **Start** on **Apache** and **MySQL** modules.
3. If MySQL is not installed yet, install it via XAMPP installer.
4. Open **phpMyAdmin** by going to [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
5. Create a new database called: ```batospring```

---

6. Import the database file `batospring.sql`:

   - Click on the database `batospring`.
   - Go to **Import** → Choose File → select `batospring.sql` → Click **Go**.

> ✅ This sets up the required database structure for the backend.

---

## Step 3: Prepare the Backend Folder

1. Locate your backend folder (originally named `bato-back`).
2. Rename it to: ```batospring```
3. Make sure the folder is placed **inside XAMPP’s `htdocs` folder**:

> This ensures that your backend is accessible via `http://localhost/batospring`.

---

## Step 4: Install Backend Dependencies

1. Open **VS Code**.
2. Open the backend folder (`batospring`) in VS Code.
3. Open a terminal in VS Code (`Ctrl+`` / Cmd+`` on Mac).
4. Run the following command to install all dependencies: ```composer install```

> ✅ This will download and install all required PHP packages for the backend.

## Step 5: Verify Backend Setup
- Check that the backend folder is inside ```htdocs```.
- Make sure Apache and MySQL are running.
- Your backend should now be ready to connect with the frontend.
