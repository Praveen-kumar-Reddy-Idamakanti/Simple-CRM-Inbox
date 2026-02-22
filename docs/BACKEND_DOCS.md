# Backend Documentation - CRM Inbox

## Overview
The CRM Inbox backend is a robust Laravel application configured to work with **MongoDB**. It serves as a central hub for message processing, contact enrichment, and AI-driven automation.

## Tech Stack
- **Framework**: Laravel 10+
- **Database**: MongoDB (via `mongodb/laravel-mongodb`)
- **Language**: PHP 8.1+
- **Testing**: PHPUnit

## Architecture & Design Patterns

### 1. Data Models (Document-Oriented)
The system uses three primary models stored in MongoDB:
- **Contact**: Stores sender identifiers, profile details (name, avatar, channel), and tags.
- **Conversation**: Tracks the state of a thread, last message preview, unread counts, and status (active/archived).
- **Message**: Individual message records with `sender_type` (contact/agent/system), text, attachments, and raw platform metadata.

### 2. Service Layer
- **WebhookService**: The heart of the inbound message pipeline. It handles:
  - Payload extraction.
  - Ensuring Contact/Conversation existence.
  - Perspective-aware message creation.
  - Triggering AI auto-responses.
- **ProfileService**: Connects to the external Mock Server to fetch real user profiles (name, profile picture) using the `sender_id`.
- **AiService**: A rule-based intelligence engine that analyzes message content to suggest replies or send automated answers based on keywords (Pricing, Shipping, Greetings, etc.).
- **MockServerService**: Encapsulates external API calls to the simulation server (`/send` and `/profile` endpoints).

### 3. API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST   | `/api/webhook` | Receives messages from the external poller (requires `X-Webhook-Token`). |
| GET    | `/api/conversations` | Lists all conversations with search support. |
| GET    | `/api/conversations/{id}/messages` | Fetches history for a conversation. |
| POST   | `/api/reply` | Sends an agent reply and forwards it to the mock server. |
| GET    | `/api/contacts/{id}` | Gets detailed contact information. |
| POST   | `/api/contacts/{id}/tags/add` | Adds a tag to a contact. |
| POST   | `/api/contacts/{id}/tags/remove` | Removes a tag from a contact. |
| GET    | `/api/conversations/{id}/suggest` | Generates an AI reply suggestion. |

## External Integration: The Poller
Since the project simulates a real-world messaging environment, a Python-based **Poller** (`scripts/poller.py`) is used.
- It polls the Mock Simulator every 5 seconds.
- It transforms the raw simulation data into a standardized Webhook payload.
- It forwards the payload to the Laravel `/api/webhook` endpoint with the required security headers.

## Testing Strategy
The backend includes a comprehensive testing suite:
- **Unit Tests**: Test individual services (AI suggestions, Webhook parsing) in isolation.
- **API Tests**: Verify endpoint responses, status codes, and database persistence.
- **Reports**: Detailed reports are available in `UNIT_TESTING_REPORT.md` and `api_test_report.md`.

## Setup & Installation

### Prerequisites
- PHP 8.1+ & Composer
- MongoDB Server
- PHP MongoDB Extension (`php_mongodb.dll` or `mongodb.so`)

### Installation Steps
1. Navigate to the `backend` directory.
2. Install dependencies: `composer install`
3. Configure `.env`:
   ```env
   DB_CONNECTION=mongodb
   DB_HOST=127.0.0.1
   DB_PORT=27017
   DB_DATABASE=crm_inbox
   
   MOCK_SERVER_URL=https://mock-simulation.omts.in
   MOCK_API_KEY=your_key_here
   ```
4. Run migrations/seeders (if applicable, though MongoDB is schema-less):
   - `php artisan db:seed --class=MongoDBIndexSeeder` (to ensure performance).
5. Start the server: `php artisan serve`
