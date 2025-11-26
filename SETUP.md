# TaskFlow - Delightful Task Management

## Quick Start

- git clone https://github.com/DanielcB123/taskflow.git
- cd taskflow

- cp .env.example .env
- composer install
- npm install

- in .env change | # DB_DATABASE=laravel to -> DB_DATABASE=database/database.sqlite 

- php artisan key:generate
- php artisan migrate:fresh --seed

- npm run dev
- php artisan serve

## Go To

- http://localhost:8000/
- test user:        media1@example.com
- test password:    password