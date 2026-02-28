# 🦷 Dental Clinic Management App

A RESTful backend API for managing dental clinic operations, built with **Laravel**.  
This system handles patients, appointments, treatments, invoices, staff, and more.

---

## 📋 Table of Contents
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Project Structure](#project-structure)
- [Usage](#usage)
- [Database](#database)
- [API Examples](#api-examples)
- [Testing](#testing)
- [Contributing](#contributing)
- [Security](#security)
- [Support & Contact](#support--contact)
- [License](#license)

---

## ✨ Features

### Patients Management
- Add, update, and delete patient records
- Store complete medical history, allergies, and sensitivities
- Advanced search and filtering
- Patient contact information and demographics

### Appointments Management
- Schedule, reschedule, edit, and cancel appointments
- Automated reminders for patients
- Attendance tracking
- Staff availability management

### Treatments & Services
- Register and track treatments provided
- Manage available dental services and pricing
- Treatment history and notes

### Invoicing & Payments
- Automatic invoice generation from treatments
- Payment tracking and recording
- Financial reports and summaries
- Multiple payment methods

### Reports & Analytics
- Performance reports and statistics
- Patient and appointment analytics
- Revenue analysis
- Exportable summaries and KPIs

---

## 🛠 Requirements

- **PHP** ^8.1  
- **Composer**  
- **Laravel Framework**  
- **MySQL** or other relational database  
- Git for version control  
- Postman (optional, for API testing)

---

## 📦 Installation

1. **Clone the repository**
```bash
git clone https://github.com/Aya-Mohammad/Dental-Clinic-Management-App.git
cd Dental-Clinic-Management-App/back
```
2. Install dependencies
```bash
composer install
```
3. Copy environment file and configure database
```bash
cp .env.example .env
```
4. Generate application key
```bash
php artisan key:generate
```
5. Run migrations
```bash
php artisan migrate
```
6. Start development server
```bash
php artisan serve
```

🚀 Usage
Authentication
Method	Endpoint	Description
POST	/api/register	Register a new user
POST	/api/login	Login
POST	/api/logout	Logout (requires auth)
Patients
Method	Endpoint	Description
GET	/api/patients	List all patients
GET	/api/patients/{id}	Show patient details
POST	/api/patients	Create new patient
PUT	/api/patients/{id}	Update patient info
DELETE	/api/patients/{id}	Delete patient
Appointments & Treatments

/api/appointments (GET/POST/PUT/DELETE)

/api/treatments (GET/POST/PUT/DELETE)

/api/invoices (GET/POST)

For complete API documentation, see docs/API.md

🗄 Database

Main Tables / Models:

Users (Admin, Dentist, Staff)

Patients

Appointments

Treatments

Invoices

Services

📄 License
This project is licensed under the MIT License. See the LICENSE file.
