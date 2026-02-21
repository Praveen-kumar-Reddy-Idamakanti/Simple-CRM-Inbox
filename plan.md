# CRM Inbox Implementation Plan

## Phase 1 — Project init & basic repo scaffolding (1 hour)

**Objective**: Create the project skeleton for backend + frontend, configure Git and environment files.

### Init

- `git init` and create repository root.

### Create

- **Backend+Frontend**: `php composer.phar create-project laravel/laravel crm-inbox`.
- Add .gitignore entries, basic composer + npm files.
- Add run / dev scripts to README.

### Done criteria

- Repo with `backend/` (Laravel) and `frontend/`.
- Basic README with commands to start backend and frontend (placeholder tokens).
- `.env.example` with MongoDB placeholders and `APP_URL=http://localhost:8000`.

### Notes

- Choose `jenssegers/mongodb` for Laravel MongoDB support or `laravel-mongodb` driver. Add installation plan in README.

---

## Phase 2 — Install dependencies & dev environment (1 hour)

**Objective**: Configure Laravel to talk to MongoDB; install required packages and start a dev server.

### Init

- Install MongoDB PHP library and Laravel MongoDB package, e.g. `composer require jenssegers/mongodb`.
- Add service provider / config for MongoDB (update `config/database.php` with mongodb connection).
- Create docker-compose skeleton or document local MongoDB connection in README.
- Install React dependencies: `npm install` (in frontend).
- Add packages: `axios` (frontend) and `guzzle/http client` (backend) if needed.

### Done criteria

- Laravel connects to MongoDB (able to run `php artisan tinker` and create a sample model).
- Frontend dev server starts (`npm run dev`) and shows placeholder page.

### Notes

- Keep `.env` secrets out of repo. Use `X-Api-Key` env var placeholder for poller token.

---

## Phase 3 — Data models & indexes (1 hour)

**Objective**: Design and implement MongoDB collections and indexes: contacts, conversations, messages.

### Init

- Create Eloquent models (or Mongo models) and describe fields in code comments.
- Sketch simple schema in README.

### Create

- **Contact model**: `sender_id`, `name`, `channel`, `avatar`, `tags`, `created_at`, `updated_at`.
- **Conversation model**: `contact_id`, `last_message_at`, `created_at`, `updated_at`.
- **Message model**: `conversation_id`, `sender_type` (contact/agent), `text`, `raw_payload`, `created_at`.
- Create indexes:
  - `contacts.sender_id`
  - `conversations.contact_id`
  - `messages.conversation_id`
  - `conversations.last_message_at`

### Done criteria

- Models present and migrations / model docs added.
- Able to create a contact and message via tinker and query them.

### Notes

- In Mongo you may not use migrations; instead, ensure code handles missing fields gracefully.

---

## Phase 4 — Webhook endpoint + payload parsing (1 hour)

**Objective**: Implement POST `/api/webhook` to accept poller requests, validate header, persist data, and return 200.

### Init

- Add route: `Route::post('/api/webhook', [WebhookController::class, 'receive']);`
- Add middleware or inline validation for header `X-Webhook-Token: secret123`.

### Create

**WebhookController**:

- Validate header; return 401 if missing/invalid.
- Parse JSON, call service to `ensureContactAndConversation(payload)` which:
  - Extract `sender_id`, `channel`, `text` using same extraction logic as poller.
  - If contact not found, fetch profile (Phase 5).
  - Create or update conversation and append message record.
  - Update `conversation.last_message_at`.
- Return 200 JSON `{"status":"ok"}`.

### Done criteria

- Poller POST to `/api/webhook` with valid token returns 200.
- New message is persisted in messages and conversation created if needed.

### Notes

- Keep controller thin; delegate heavy work to a service class.

---

## Phase 5 — Profile fetch & contact enrichment (1 hour)

**Objective**: On first message from a sender, call mock server `/profile/{sender_id}` to get name/avatar and save to contacts.

### Init

- Add ProfileService with HTTP client that uses `X-Api-Key` (use poller token or server token).

### Create

- GET `https://mock-simulation.omts.in/profile/{sender_id}` call.
- Save name, avatar (if provided), channel into contacts.
- Mark contact created timestamp.

### Done criteria

- On first webhook from unknown `sender_id`, contacts contains enriched profile.

### Notes

- Add caching or upsert semantics to avoid repeated calls during tests.

---

## Phase 6 — Conversation list & message retrieval APIs (1 hour)

**Objective**: Implement REST endpoints for conversation listing and fetching messages.

### Init

Define API routes:

- `GET /api/conversations` — list
- `GET /api/conversations/{id}/messages` — messages

### Create

- `GET /api/conversations` returns:
  - conversation id, contact name, channel, last_message preview, `last_message_at`, tags.
  - Support `?search=` param (basic text search on contact name or last message).
- `GET /api/conversations/{id}/messages` returns paginated messages sorted ascending by time.

### Done criteria

- API returns JSON and can be tested via curl or Postman.
- Search returns filtered results.

### Notes

- Use simple limit/skip pagination. Add indexes on `last_message_at` and `messages.text` if needed.

---

## Phase 7 — Send reply API + mock server /send integration (1 hour)

**Objective**: Implement POST `/api/reply` that records an agent message and calls mock server `/send` to simulate outbound delivery and trigger auto-response.

### Init

- Define route: `POST /api/reply`
- Request payload: `{ "conversation_id": "...", "message": "..." }`

### Create

- Validate input (422 on missing).
- Persist message with `sender_type: agent`.
- Call POST `https://mock-simulation.omts.in/send` with JSON `{ "sender_id": contact.sender_id, "message": "..." }` and `X-Api-Key`.
- Return 200 with saved message and status of external send call.

### Done criteria

- UI or curl POST `/api/reply` results in messages showing agent message.
- Mock server receives send call (assumed), and auto-response will come back via poller later.

### Notes

- Ensure outgoing call is non-blocking if it might slow UI. For the evaluation, simple synchronous call is acceptable.

---

## Phase 8 — Tagging & contact management APIs (1 hour)

**Objective**: Implement tag add/remove endpoints and minimal contact CRUD.

### Init

Routes:

- `POST /api/contacts/{id}/tags` (body: `{ tags: ["vip"] }`)
- `DELETE /api/contacts/{id}/tags/{tag}`
- `GET /api/contacts/{id}` — contact details

### Create

- Implement controller methods to push/pull tags on `contacts.tags` array.
- Validate tag strings (no special chars).

### Done criteria

- Tags can be added/removed and returned via conversation list and contact details.

### Notes

- Make tags idempotent and case-normalized.

---

## Phase 9 — Frontend: Conversation list + conversation view + reply (1 hour)

**Objective**: Build minimal UI that talks to backend APIs and demonstrates end-to-end flow.

### Init

In `frontend/`, create pages/components:

- `InboxList` (left pane)
- `ConversationView` (right pane)
- `ReplyBox` (bottom)
- `TagManager` (small UI in Conversation header)

### Create

- Use axios for API calls.
- `InboxList` fetches `GET /api/conversations`.
- Click conversation -> load `GET /api/conversations/{id}/messages`.
- `ReplyBox` posts to `POST /api/reply` and appends the sent message locally.
- Show channel badge (IG/FB) based on contact channel.
- Implement search input bound to `?search=` on list fetch.

### Done criteria

- UI can list conversations, open a conversation, send reply, and show new agent message immediately.

### Notes

- Keep UI simple (no styling required beyond readability). Use local polling or WebSocket is optional — not required.

---

## Phase 10 — Polish, testing, README & deliverables (1 hour)

**Objective**: Finish small but important items: error handling, README, quick manual tests, and final polish.

### Init

- Add `.env.example` final values: `APP_URL`, `MONGO_URI`, `MOCK_API_KEY`.
- Make sure CORS allows frontend origin.

### Create

- Add simple validation (request validation classes).
- Add basic logging statements in webhook flow.
- Create README.md final with:
  - Setup steps (install composer/npm, .env, run `php artisan serve`, `npm run dev`)
  - How to run poller (command provided in assignment)
  - API endpoints list and example requests
  - Notes on expected auto-response timeline (~5s after replying)

### Run manual smoke tests

- Start backend & frontend
- Start poller and ensure inbound messages show in UI
- Send reply in UI and observe auto-response via poller

### Done criteria

- README complete and working demo verified (at least manually).
- Commit all changes and tag `v1.0-eval`.

### Notes

- If time remains, add small unit tests for controllers or simple Postman collection.
