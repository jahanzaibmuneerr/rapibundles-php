# Medical Appointment Booking API

A robust backend API system for managing medical appointments with **critical concurrency control** to prevent double-booking. Built with Laravel (PHP 8.1+) and PostgreSQL, featuring a minimal React frontend for demonstration.

## 🎯 Key Features

- **Concurrency Control**: Implements pessimistic locking (`SELECT FOR UPDATE`) to prevent race conditions
- **Database-Level Safeguards**: Unique constraints ensure data integrity at the database level
- **RESTful API**: Clean, well-structured endpoints for doctors and appointments
- **Modern React Frontend**: Beautiful, responsive UI with gradients, animations, and excellent UX
- **Docker Support**: Easy setup with Docker Compose
- **Manual Setup**: Full support for local development without Docker

## 🏗️ Architecture

### Backend (Laravel + PostgreSQL)

- **Framework**: Laravel 10.x
- **PHP Version**: 8.1+
- **Database**: PostgreSQL 15
- **Concurrency Strategy**: Pessimistic locking with database transactions

### Frontend (React)

- **Framework**: React 18
- **Purpose**: Modern, user-friendly client application
- **Styling**: Custom CSS with modern design, gradients, animations, and responsive layout

## 📋 Prerequisites

- Docker and Docker Compose installed
- OR: PHP 8.1+, Composer, PostgreSQL 15, Node.js 18+

## 🚀 Quick Start with Docker Compose (Recommended)

### Step 1: Clone and Navigate

```bash
cd royal-app
```

### Step 2: Start Services

```bash
docker-compose up -d
```

This will:

- Start PostgreSQL database
- Build and start Laravel backend
- Build and start React frontend
- Run database migrations
- Seed sample doctors

### Step 3: Access the Application

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000 (Docker) or http://localhost:5001 (Manual)
- **API Docs**: http://localhost:8000/api/doctors (Docker) or http://localhost:5001/api/doctors (Manual)

### Step 4: Test Concurrency Control

1. Open the frontend in **two browser tabs**
2. Select the same doctor and date in both tabs
3. Try to book the **same time slot** simultaneously
4. One request will succeed (201 Created), the other will receive a **409 Conflict** error

## 🔧 Manual Setup (Without Docker)

### Backend Setup

1. **Install Dependencies**

   ```bash
   cd backend
   composer install
   ```

2. **Configure Environment**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Update `.env` with your PostgreSQL credentials:

   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=royal_app
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

3. **Run Migrations and Seeders**

   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Start Server**

   **Note**: If `php artisan serve` fails to bind to ports, use PHP's built-in server instead:

   ```bash
   # Option 1: Use the provided batch file (Windows)
   start-server.bat

   # Option 2: Manual command
   php -S localhost:5001 -t public server.php
   ```

   The backend will be available at: http://localhost:5001

### Frontend Setup

1. **Install Dependencies**

   ```bash
   cd frontend
   npm install
   ```

2. **Configure API URL** (if needed)

   The frontend is pre-configured to use `http://localhost:5001` by default.
   If your backend runs on a different port, create `.env` file:

   ```env
   REACT_APP_API_URL=http://localhost:5001
   ```

3. **Start Development Server**
   ```bash
   npm start
   ```

## 📡 API Endpoints

### 1. List Doctors

```http
GET /api/doctors
```

**Response:**

```json
[
  {
    "id": 1,
    "name": "Dr. Sarah Johnson",
    "specialization": "Cardiology",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

### 2. Get Doctor Availability

```http
GET /api/doctors/{id}/availability?date=2024-01-15
```

**Response:**

```json
{
  "doctor_id": 1,
  "doctor_name": "Dr. Sarah Johnson",
  "date": "2024-01-15",
  "available_slots": ["09:00", "09:30", "10:00", "10:30", ...]
}
```

### 3. Book Appointment

```http
POST /api/appointments
Content-Type: application/json

{
  "doctor_id": 1,
  "patient_name": "John Doe",
  "start_time": "2024-01-15 09:00"
}
```

**Success Response (201 Created):**

```json
{
  "message": "Appointment booked successfully",
  "appointment": {
    "id": 1,
    "doctor_id": 1,
    "doctor_name": "Dr. Sarah Johnson",
    "patient_name": "John Doe",
    "start_time": "2024-01-15 09:00:00",
    "end_time": "2024-01-15 09:30:00",
    "status": "scheduled"
  }
}
```

**Conflict Response (409 Conflict):**

```json
{
  "message": "Slot was just booked. Please choose another time.",
  "error": "CONFLICT"
}
```

## 🔒 Concurrency Control Implementation

### Pessimistic Locking Strategy

The booking endpoint (`POST /api/appointments`) uses **pessimistic locking** to prevent double-booking:

```php
DB::transaction(function () use ($doctorId, $startTime, $endTime) {
    // Lock rows for update - prevents other transactions from reading/modifying
    $existingAppointment = Appointment::where('doctor_id', $doctorId)
        ->where('status', 'scheduled')
        ->where(/* overlapping time conditions */)
        ->lockForUpdate() // PESSIMISTIC LOCK
        ->first();

    if ($existingAppointment) {
        throw new \Exception('Slot was just booked...');
    }

    // Create appointment atomically
    return Appointment::create([...]);
});
```

### Database-Level Safeguards

The `appointments` table includes a unique constraint:

```sql
UNIQUE (doctor_id, start_time, end_time)
```

This ensures that even if application-level logic fails, the database will reject duplicate bookings.

### How It Works

1. **Transaction Start**: Begin database transaction
2. **Lock Acquisition**: `SELECT ... FOR UPDATE` locks matching rows
3. **Conflict Check**: Verify no overlapping appointments exist
4. **Atomic Creation**: Create appointment within the same transaction
5. **Commit/Rollback**: Transaction commits or rolls back atomically

## 📊 Database Schema

### Doctors Table

```sql
- id (primary key)
- name
- specialization
- created_at
- updated_at
```

### Appointments Table

```sql
- id (primary key)
- doctor_id (foreign key)
- patient_name
- start_time (timestamp)
- end_time (timestamp)
- status (enum: scheduled, cancelled, completed)
- created_at
- updated_at
- UNIQUE (doctor_id, start_time, end_time) -- Prevents double-booking
```

## 🧪 Testing Concurrency

### Method 1: Multiple Browser Tabs

1. Open frontend in 2+ tabs
2. Select same doctor and date
3. Click the same time slot simultaneously
4. Observe: One succeeds, others get 409 Conflict

### Method 2: cURL Script

```bash
# Run this script multiple times simultaneously
# Replace 8000 with 5001 if using manual setup
for i in {1..5}; do
  curl -X POST http://localhost:5001/api/appointments \
    -H "Content-Type: application/json" \
    -d '{
      "doctor_id": 1,
      "patient_name": "Patient '$i'",
      "start_time": "2024-01-15 09:00"
    }' &
done
wait
```

Only one request should succeed (201), others should return 409.

## 📁 Project Structure

```
royal-app/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   │       └── Api/
│   │   │           ├── DoctorController.php
│   │   │           └── AppointmentController.php
│   │   └── Models/
│   │       ├── Doctor.php
│   │       └── Appointment.php
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   │   └── api.php
│   └── config/
├── frontend/               # React Client
│   ├── src/
│   │   ├── App.js
│   │   ├── index.js
│   │   └── index.css
│   └── public/
├── backend/
│   ├── start-server.bat   # Windows batch file to start server
│   └── server.php          # Router for PHP built-in server
├── docker-compose.yml
└── README.md
```

## 🛠️ Troubleshooting

### Database Connection Issues

- Ensure PostgreSQL is running
- Check `.env` database credentials
- Verify Docker containers are healthy: `docker-compose ps`

### CORS Errors

- Backend CORS is configured for `localhost:3000`
- Update `backend/config/cors.php` if using different ports

### Port Conflicts

**Backend Port Issues (Windows):**

- If `php artisan serve` fails on ports 8000-8010, use PHP's built-in server:
  ```bash
  php -S localhost:5001 -t public server.php
  ```
- Or use the provided `start-server.bat` file
- Update frontend `package.json` proxy and `App.js` API_URL to match your port

**Docker:**

- Backend: Change `8000:8000` in `docker-compose.yml`
- Frontend: Change `3000:3000` in `docker-compose.yml`

### Migration Errors

```bash
# Reset database (WARNING: Deletes all data)
docker-compose exec backend php artisan migrate:fresh --seed

# Manual setup
php artisan migrate:fresh --seed
```

### Common Setup Issues

**Missing Controller Class:**

- If you see "Class App\Http\Controllers\Controller not found", ensure `backend/app/Http/Controllers/Controller.php` exists

**View Configuration Error:**

- If you see "Argument #2 ($paths) must be of type array, null given", ensure `backend/config/view.php` exists

**Auth Guard Error:**

- If you see "Auth guard [] is not defined", check `RouteServiceProvider.php` - it should use `$request->ip()` instead of `$request->user()`

**Database Connection:**

- Verify PostgreSQL is running
- Check `.env` file has correct credentials
- Ensure database exists: `CREATE DATABASE royal_app;`

## 📝 Business Rules

- **Operating Hours**: 09:00 - 17:00 (Monday to Friday)
- **Slot Duration**: 30 minutes (e.g., 09:00, 09:30, 10:00)
- **Weekend Bookings**: Not allowed
- **Double-Booking**: Prevented by pessimistic locking + unique constraints

## 🎨 UI Features

The frontend includes a modern, polished design with:

- **Beautiful Gradient Design**: Purple gradient theme with smooth color transitions
- **Smooth Animations**: Fade-in, slide-in, and hover effects throughout
- **Responsive Layout**: Works perfectly on desktop, tablet, and mobile devices
- **Interactive Elements**: Hover effects, button animations, and visual feedback
- **Clear Visual Hierarchy**: Well-organized sections with icons and clear typography
- **User-Friendly Forms**: Enhanced input styling with focus states and Enter key support
- **Loading States**: Animated loading indicators for better UX
- **Error Handling**: Beautiful error and success messages with icons

## 🎓 Key Learning Points

1. **Concurrency Control**: Pessimistic locking ensures atomic operations
2. **Database Transactions**: All-or-nothing operations prevent partial states
3. **Defense in Depth**: Application-level locks + database constraints
4. **Error Handling**: Proper HTTP status codes (409 Conflict) for race conditions
5. **Modern UI/UX**: Beautiful, responsive design enhances user experience

## 📄 License

This project is created for assessment purposes.

## 👤 Author

Backend Developer Assessment - Medical Appointment API

---

**Note**: This implementation prioritizes **correctness and robustness** with a modern, polished UI. The React frontend features a beautiful gradient design, smooth animations, and excellent user experience while demonstrating the API's concurrency control capabilities.
