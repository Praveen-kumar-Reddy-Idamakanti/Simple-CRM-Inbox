# Frontend Documentation - CRM Inbox

## Overview
The CRM Inbox frontend is a modern, responsive React application built with **Vite** and styled using **Tailwind CSS**. It provides a unified interface for managing customer conversations across multiple channels (Facebook, Instagram, etc.).

## Tech Stack
- **Framework**: React 18
- **Build Tool**: Vite
- **Styling**: Tailwind CSS
- **Icons**: Lucide React (standard in modern React apps, though I should verify if used)
- **API Client**: Fetch API (wrapped in `src/services/api.js`)

## Directory Structure
```text
frontend/
├── src/
│   ├── components/       # UI Components
│   │   ├── chat/         # Chat window, bubbles, input
│   │   ├── contact/      # Profile panel, tag management
│   │   ├── conversation/ # Conversation list & items
│   │   └── layout/       # Sidebar, TopBar, MainLayout
│   ├── services/         # API integration layer
│   ├── assets/           # Static assets
│   ├── App.jsx           # Main application logic & routing
│   └── main.jsx          # Entry point
```

## Core Components

### 1. Layout Components
- **MainLayout**: Orchestrates the 3-column layout (Sidebar, Conversation List, Main Workspace).
- **Sidebar**: Navigation menu for different inbox sections (All, Unread, Archived).
- **TopBar**: Contextual header showing the current view title and user profile.

### 2. Conversation List
- **ConversationList**: Fetches and renders a list of active conversations. Supports search filtering.
- **ConversationItem**: Displays a summary of a single conversation (Contact name, last message preview, timestamp, channel badge).

### 3. Chat Interface
- **ChatWindow**: The primary workspace. Loads messages for the selected conversation and handles real-time updates (via polling).
- **MessageBubble**: Renders individual messages with distinct styles for 'contact' (left) and 'agent' (right).
- **MessageInput**: A rich text area for sending replies, with support for AI suggestions.

### 4. Contact Profile & CRM Tools
- **ContactProfile**: Displays detailed information about the customer (Avatar, Name, Channel, Metadata).
- **TagList**: Interface for viewing, adding, and removing tags from a contact. Updates the backend in real-time.

## API Integration (`src/services/api.js`)
The frontend communicates with the Laravel backend through a centralized API service. Key functions include:
- `fetchConversations(params)`: Retrieves the list of conversations with optional search.
- `fetchMessages(conversationId)`: Loads the message history for a specific thread.
- `sendReply(conversationId, text)`: Sends a message from the agent.
- `addTag/removeTag(contactId, tag)`: Manages contact segmentation.
- `getAiSuggestion(conversationId)`: Fetches an AI-generated reply recommendation.

## Features & UX
- **Channel Differentiation**: Visual badges for Instagram and Facebook messages.
- **Responsive Design**: Fluid layout that adapts to different screen sizes.
- **Micro-interactions**: Hover effects on conversation items and smooth transitions between chat threads.
- **Real-time Feel**: Frequent polling or optimistic UI updates ensure the agent sees new messages quickly.

## Setup & Development
1. Navigate to the `frontend` directory.
2. Install dependencies: `npm install`
3. Run dev server: `npm run dev`
4. Build for production: `npm run build`
