# 🚀 Quick Start Guide

## Option 1: Using Docker Compose (Easiest - Recommended)

### Prerequisites
- Docker Desktop installed and running
- Docker Compose (usually included with Docker Desktop)

### Steps

1. **Open Terminal/PowerShell in the project directory**
   ```bash
   cd D:\office\royal-app
   ```

2. **Start all services**
   ```bash
   docker-compose up -d
   ```
   
   This will:
   - Download PostgreSQL image (if not already downloaded)
   - Build the Laravel backend
   - Build the React frontend
   - Start all services
   - Run database migrations
   - Seed sample doctors

3. **Wait for services to start** (first time may take 2-3 minutes)
   
   Check if services are running:
   ```bash
   docker-compose ps
   ```

4. **Access the application**
   - **Frontend (React App)**: Open http://localhost:3000 in your browser
   - **Backend API**: http://localhost:8000
   - **Test API**: http://localhost:8000/api/doctors

5. **View logs** (if needed)
   ```bash
   # View all logs
   docker-compose logs -f
   
   # View backend logs only
   docker-compose logs -f backend
   
   # View frontend logs only
   docker-compose logs -f frontend
   ```

6. **Stop services** (when done)
   ```bash
   docker-compose down
   ```

7. **Stop and remove all data** (fresh start)
   ```bash
   docker-compose down -v
   ```

---

## Option 2: Manual Setup (Without Docker)

### Prerequisites
- PHP 8.1 or higher
- Composer
- PostgreSQL 15
- Node.js 18+ and npm

### Backend Setup

1. **Navigate to backend directory**
   ```bash
   cd backend
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Create environment file**
   ```bash
   copy .env.example .env
   ```
   (On Linux/Mac: `cp .env.example .env`)

4. **Generate application key**
   ```bash
   php artisan key:generate
   ```

5. **Configure database in `.env` file**
   Open `backend/.env` and update:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=royal_app
   DB_USERNAME=your_postgres_username
   DB_PASSWORD=your_postgres_password
   ```

6. **Create PostgreSQL database**
   ```sql
   CREATE DATABASE royal_app;
   ```

7. **Run migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

8. **Start Laravel server**
   ```bash
   php artisan serve
   ```
   Backend will be available at: http://localhost:8000

### Frontend Setup

1. **Open a new terminal and navigate to frontend**
   ```bash
   cd frontend
   ```

2. **Install Node dependencies**
   ```bash
   npm install
   ```

3. **Create `.env` file** (optional, defaults to localhost:8000)
   ```env
   REACT_APP_API_URL=http://localhost:8000
   ```

4. **Start React development server**
   ```bash
   npm start
   ```
   Frontend will be available at: http://localhost:3000

---

## 🧪 Testing the Application

### Test Basic Functionality

1. Open http://localhost:3000
2. You should see a list of doctors
3. Click on a doctor to select it
4. Choose a date (must be a weekday)
5. Click "Check Availability"
6. Select an available time slot
7. Enter a patient name
8. Click "Book Appointment"

### Test Concurrency Control (The Key Feature!)

1. **Open the frontend in TWO browser tabs/windows**
   - Tab 1: http://localhost:3000
   - Tab 2: http://localhost:3000

2. **In both tabs:**
   - Select the same doctor
   - Select the same date
   - Click "Check Availability"

3. **Try to book the SAME time slot in both tabs simultaneously:**
   - In Tab 1: Select a slot (e.g., 09:00), enter patient name, click "Book"
   - In Tab 2: Select the SAME slot (09:00), enter different patient name, click "Book" at the same time

4. **Expected Result:**
   - ✅ One tab will show: "Appointment booked successfully!" (HTTP 201)
   - ❌ The other tab will show: "Slot was just booked. Please choose another time." (HTTP 409 Conflict)
   
   This demonstrates the concurrency control working!

### Test with cURL (Command Line)

```bash
# List doctors
curl http://localhost:8000/api/doctors

# Get availability
curl "http://localhost:8000/api/doctors/1/availability?date=2024-01-15"

# Book appointment
curl -X POST http://localhost:8000/api/appointments \
  -H "Content-Type: application/json" \
  -d "{\"doctor_id\":1,\"patient_name\":\"John Doe\",\"start_time\":\"2024-01-15 09:00\"}"
```

---

## 🐛 Troubleshooting

### Docker Issues

**Port already in use:**
```bash
# Check what's using the port
netstat -ano | findstr :8000
netstat -ano | findstr :3000
netstat -ano | findstr :5432

# Stop the conflicting service or change ports in docker-compose.yml
```

**Services won't start:**
```bash
# Check logs
docker-compose logs

# Rebuild containers
docker-compose up -d --build
```

**Database connection errors:**
```bash
# Make sure PostgreSQL container is healthy
docker-compose ps

# Restart services
docker-compose restart
```

### Manual Setup Issues

**Composer not found:**
- Install Composer from https://getcomposer.org/

**PHP extensions missing:**
```bash
# Install required PHP extensions
# For Ubuntu/Debian:
sudo apt-get install php-pgsql php-mbstring php-xml

# For Windows: Enable extensions in php.ini
```

**Database connection failed:**
- Verify PostgreSQL is running
- Check credentials in `.env`
- Ensure database exists: `CREATE DATABASE royal_app;`

**Frontend can't connect to backend:**
- Check CORS settings in `backend/config/cors.php`
- Verify backend is running on port 8000
- Check `REACT_APP_API_URL` in frontend `.env`

---

## 📝 Next Steps

1. ✅ Application is running
2. ✅ Test basic booking functionality
3. ✅ Test concurrency control (most important!)
4. 📖 Read `README.md` for detailed API documentation
5. 🔍 Check the code to understand the concurrency implementation

---

## 🎯 Key Files to Review

- **Concurrency Control**: `backend/app/Http/Controllers/Api/AppointmentController.php`
- **Database Schema**: `backend/database/migrations/2024_01_01_000002_create_appointments_table.php`
- **API Routes**: `backend/routes/api.php`
- **Frontend**: `frontend/src/App.js`

---

**Need Help?** Check the main `README.md` file for more detailed information.


