# Database Schema Documentation

## Tables

### users
- id (INT, PRIMARY KEY)
- username (VARCHAR)
- email (VARCHAR)
- password (VARCHAR)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

### files
- id (INT, PRIMARY KEY)
- filename (VARCHAR)
- path (VARCHAR)
- size (INT)
- user_id (INT, FOREIGN KEY)
- created_at (TIMESTAMP)

### posts
- id (INT, PRIMARY KEY)
- title (VARCHAR)
- content (TEXT)
- user_id (INT, FOREIGN KEY)
- status (ENUM)
- created_at (TIMESTAMP)
