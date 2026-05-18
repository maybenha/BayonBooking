<img width="200" height="200" alt="num" src="https://github.com/user-attachments/assets/4198553e-aeaa-443b-9544-c1157bfecd18" />

<img width="1044" height="665" alt="image" src="https://github.com/user-attachments/assets/7306c7dd-818e-476a-8c68-c31284b5531c" />
Hotel Booking Management System Description

The Hotel Booking Management System is a modern web-based application developed using PHP, MySQL, HTML, CSS, JavaScript, and Bootstrap. The system follows the MVC (Model View Controller) architecture to ensure clean code structure, maintainability, scalability, and proper separation between business logic, database operations, and user interfaces.

The system is designed for two main roles: Admin and Customer. Both users must authenticate through the login system before accessing the platform. After successful login, users are redirected to the main Landing Page which displays all available hotel rooms in a modern responsive layout.

The design theme uses only the following colors:

White
#e2e0d1
Black

Text colors are limited to white and black only. All buttons use black backgrounds with white text to maintain a clean and professional hotel booking appearance.

System Architecture

The application uses MVC architecture:

Models handle database operations.
Views handle user interface and frontend display.
Controllers handle application logic and communication between models and views.

All pages, assets, and modules are separated properly into different folders for maintainability.

Authentication System

The system includes a secure authentication module with:

Customer registration
Login and logout functionality
Session management
Password hashing using PHP password hashing functions
Role-based authentication for Admin and Customer

After login:

Admin users access admin management features
Customers access booking features
Both are redirected to the same landing page/home page
Customer Features

Customers can perform the following actions:

View Landing Page

Customers can browse all available hotel rooms displayed as modern room cards containing:

Room image
Room type
Room price
Room status
Booking button
Search and Filter Rooms

Customers can search hotel rooms by:

Room type
Price range
Room availability

Pagination is implemented for room listings to improve performance and user experience.

View Room Details

Customers can open detailed room pages displaying:

Room images
Equipment and facilities
Capacity
Description
Availability status
Price per night
Book Rooms

Customers can:

Select check-in and check-out dates
Choose room
Confirm booking
Receive booking status

Booking validation is handled using JavaScript and backend validation.

Manage Booking History

Customers can:

View all previous bookings
Check booking status
Cancel bookings if allowed
Profile Management

Customers can:

Update profile information
Upload profile image
Change account details
Admin Features

The Admin Panel provides complete hotel management functionality.

Dashboard

The dashboard contains summary cards and statistics including:

Total bookings
Total customers
Total rooms
Available rooms

Charts and tables may also be included for better visualization.

Room Management

Admins can:

Add new rooms
Edit room information
Delete rooms
Upload room images
Update room availability and room status

Each room contains:

Room number
Room type
Price per night
Capacity
Room image
Equipments
Description
Booking Management

Admins can:

View all bookings
Approve bookings
Reject bookings
Cancel bookings
Monitor booking statuses
Customer Management

Admins can:

View customer list
Delete customer accounts
Monitor user activity
Reports

Admins can generate and view reports for:

Total bookings
Total customers
Total rooms
Available rooms
Landing Page Design

The landing page serves as the main homepage after login for both Admin and Customer users.

The page includes:

Responsive navigation bar
Hero/banner section
Modern room card layout
Room images
Room pricing
Room status
Booking button
Footer section

The interface is fully responsive for desktop, tablet, and mobile devices.

Database Structure

The system uses the MySQL database named:

book_hotel

The database contains the following tables:

user
room_types
rooms
bookings
payments
invoices

The database uses:

Primary keys
Foreign keys
Relationships
Constraints

to maintain data integrity and proper relational structure.

Technical Implementation

The system uses:

PHP PDO for secure database connection
Prepared Statements to prevent SQL Injection
Bootstrap for responsive UI design
JavaScript validation for forms
SweetAlert for modern alert dialogs
AJAX for dynamic operations
Pagination for large room listings
CSS Design Requirements

All CSS styling must be stored in external CSS files only.

Each page has its own CSS file for better organization.

Examples:

login.css
register.css
landing.css
dashboard.css
manage-room.css
booking.css

Inline CSS and internal CSS should be avoided unless absolutely necessary.

Folder Structure
hotel-booking-system/
│
├── admin/
├── customer/
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│
├── config/
├── database/
├── includes/
├── uploads/
├── index.php
├── login.php
├── register.php
└── logout.php
User Interface Design

The system uses a modern hotel booking interface with:

Clean card layouts
Soft neutral colors
Responsive components
Modern typography
Black buttons with white text
White and #e2e0d1 background sections
Admin sidebar navigation
Professional hotel-style appearance

The UI focuses on simplicity, readability, elegance, and user-friendly navigation for both administrators and customers.
