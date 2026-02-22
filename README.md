# CRM Inbox - Unified Messaging Platform

A comprehensive CRM Inbox solution that aggregates customer messages from multiple social channels (Facebook, Instagram) into a single workspace. Built with a high-performance Laravel/MongoDB backend and a modern React frontend.

## 🚀 Key Features
- **Unified Inbox**: View and respond to messages from all channels in one place.
- **Contact Enrichment**: Automatically fetches customer profiles (names, avatars) from external sources.
- **AI-Powered Intelligence**: Automated responses and smart reply suggestions based on message context.
- **Tag Management**: Segment customers with custom tags for better organization.
- **Real-time Pipeline**: Efficient polling mechanism using a Python-based background worker.
- **Full Test Coverage**: Extensive unit and API tests to ensure system reliability.

## 📁 Project Structure
- `backend/`: Laravel 10 application with MongoDB integration.
- `frontend/`: React app built with Vite and Tailwind CSS.
- `docs/`: Detailed technical documentation.
  - [Frontend Specs](./docs/FRONTEND_DOCS.md)
  - [Backend Specs](./docs/BACKEND_DOCS.md)
- `poller.py`: Python script for fetching external messages (now in `scripts/`).
- `simulate_webhook.py`: Tool for manual webhook testing (now in `scripts/`).

## 🛠️ Installation & Setup

### 1. Backend (Laravel)
```bash
cd backend
composer install
cp .env.example .env
# Configure your MongoDB credentials in .env
php artisan serve
```
*Note: Ensure the MongoDB PHP extension is installed. See [Installation Plans](./docs/setup/installation_plans.md) for details.*

### 2. Frontend (React)
```bash
cd frontend
npm install
npm run dev
```

### 3. Message Poller (Python)
```bash
# From the root directory
python scripts/poller.py --token abc123 --target http://localhost:8000 --interval 3
```

## 📖 Technical Documentation
- **[Frontend Implementation Details](./docs/FRONTEND_DOCS.md)**: Explore the component architecture and state management.
- **[Backend Architecture](./docs/BACKEND_DOCS.md)**: Details on MongoDB schema, services, and AI engine.
- **[Implementation Roadmap](./docs/project/plan.md)**: The original 10-phase development plan.
- **[Development Steps](./docs/project/Implementation_steps.md)**: A log of the actual steps taken during implementation.

## 🧪 Testing
The project maintains high code quality through rigorous testing:
- **Unit Tests**: `php artisan test --testsuite=Unit`
- **Feature/API Tests**: `php artisan test --testsuite=Feature`
- View the latest [Unit Test Report](./backend/UNIT_TESTING_REPORT.md) and [API Test Report](./backend/api_test_report.md).

---
*Developed as part of the CRM Inbox project.*
