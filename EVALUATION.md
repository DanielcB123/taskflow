# Self-Evaluation

## What I Built

TaskFlow is a modern, fast, responsive task management board built with Laravel 11, Vue 3, Inertia.js, and Tailwind. It includes teams, custom API-key authentication, task CRUD, drag and drop, inline editing, priority controls, tagging, toasts, skeleton loaders, and micro-interactions inspired by the MediaHaus design system.

## Design System Implementation

### Core UI Patterns

- [x] Hover states on all interactive elements

- [x] Focus states with proper accessibility contrast

- [x] Disabled states for async operations and create/update buttons

- [x] Smooth transitions on buttons, modals, tags, and priority selector

### Delightful Details

- [x] Skeleton loaders for initial task loading

- [x] Toast feedback for create, update, delete

- [x] Confetti animation when a task is moved to “Done”

- [x] Animated column transitions

- [x] Subtle shadow and scale micro-interactions

### Consistency

- [x] Colors, sizes, text styles, and spacing follow a consistent design rhythm

- [x] User avatars and initials auto- styled

- [x] All forms have consistent border radius, colors, and focus rings

## Technical Implementation

### Architecture

- [x] Custom API key authentication (no Sanctum or Passport)

- [x] RESTful endpoints for tasks, tags, teams, and API keys

- [x] Clean separation: /api routes for data, /dashboard for UI

- [x] Composable useTasks() for all task logic on the frontend

### Data + State

- [x] Inline updates for:

    - title

    - status

    - tag creation + deletion

    - drag-and-drop movement

    - assignee

    - priority badge

- [x] All API calls centralized in an ApiService file

- [x] Tasks automatically scoped by team_id

- [x] API key persists in localStorage with fallback regeneration

### Frontend Engineering

- [x] Components: TaskColumn, TaskCard, CreateTaskModal

- [x] Vue 3 script setup syntax everywhere

- [x] Loading states for async actions


## Challenges Faced
1. Inline Updates on Dashboard

Challenge:
Updating tags, title, priority, and assignee inline without refreshing and while keeping the UI reactive.

Solution:
Created a unified updateTaskInline() method inside useTasks() that updates local state immediately and synchronizes with the backend via a single PATCH endpoint.

2. Custom API-Key Authentication (No Sanctum)

Challenge:
Build a secure, minimal auth system using only Laravel and headers.

Solution:
Implemented a custom api_key field on the users table and middleware that validates the key from the X-API-Key header on every API request. A fresh key is generated automatically during login, and a /api-token endpoint exposes the key to the frontend. The key is stored in localStorage and injected into all Axios requests for API access, allowing full separation between session-based web routes and API-key-protected AJAX endpoints.

3. Drag and Drop with Live Status Sync

Challenge:
Move tasks visually and update backend smoothly, with no UI jitter.

Solution:
Used a local reorder → immediate UI update → backend sync pattern.

## Bonus Features Implemented

- [x] (Optional) Command palette with Cmd+K
- [x] (Optional) Demo Video

## Time Spent

| Area                                          | Hours       |
| --------------------------------------------- | ----------- |
| Backend (API + Models + Seeders + Middleware) | 4 hours     |
| Frontend (Components + UX + Logic)            | 4 hours     |
| Design polish & animations                    | 2 hours     |
| Total                                         | **10 hours** |
