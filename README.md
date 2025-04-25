# final-submissions
# 🍨 Iuri's Ice Cream Order Web Application

## 📄 Description

**Iuri's Ice Cream Order Web Application** is a responsive, user-friendly web platform designed to allow customers to browse a delightful selection of ice creams, place orders, and manage their profiles. Built using HTML, CSS, PHP, and MySQL, the application provides a seamless ordering experience while also offering admin tools to manage orders and user information.

## 🚀 Features

- 🧁 Stylish and responsive design for all devices
- 🔐 User authentication (login, registration, logout)
- 🛒 Order placement with a detailed checkout process
- 📦 View past orders and order details
- 👤 Profile management
- ✉️ Contact page for customer support
- 🗄️ MySQL-powered backend with an import-ready database

## 🏗️ Project Structure

```
iuri/
│
├── auth/                  # Login, logout, register scripts
├── css/                   # Main stylesheet
├── images/                # Ice cream and design images
├── about.php              # About page
├── contact.php            # Contact support
├── index.php              # Home page with main offerings
├── checkout.php           # Finalize orders
├── orders.php             # User orders overview
├── order_details.php      # Specific order breakdown
├── profile.php            # User profile info
├── database.sql           # SQL file to set up the database
```

## 🛠️ Technologies Used

- **Frontend:** HTML, CSS
- **Backend:** PHP
- **Database:** MySQL
- **Local Server Environment:** XAMPP / WAMP / LAMP

## ⚙️ Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/YourUsername/iuri.git
   ```

2. **Navigate to the project directory**
   ```bash
   cd iuri
   ```

3. **Set up the database**
   - Import `database.sql` into your MySQL database via phpMyAdmin or CLI.
   - Configure your DB credentials in the PHP files (e.g., a `db.php` or `config.php` if included).

4. **Start your local development server**
   - Place the `iuri` folder inside `htdocs` (if using XAMPP).
   - Launch Apache and MySQL through your control panel.

5. **Open your browser and navigate to**
   ```
   http://localhost/iuri/index.php
   ```

## 🤝 Contribution

Contributions are very welcome! Fork the repository, make changes, and submit a pull request. Feel free to open issues or suggest improvements.

## 📄 License

This project is licensed under the **MIT License** – see the [LICENSE](LICENSE) file for more details.

## 👤 Author

Developed by **Iuri**  
_iuridinushani123@gmail.com_
