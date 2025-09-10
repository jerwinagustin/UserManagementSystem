# User Management System (Users Table Integration)

This project is a minimal PHP + MySQL user management example focusing on the core **Users** table.

## Users Table Fields

| Field        | Type                     | Notes                           |
| ------------ | ------------------------ | ------------------------------- |
| UserID       | INT (PK, AUTO_INCREMENT) | Primary key                     |
| Username     | VARCHAR(255)             | Unique (enforce via DB or code) |
| Email        | VARCHAR(255)             | Unique                          |
| PasswordHash | VARCHAR(255)             | Password hash (bcrypt/argon)    |
| CreatedAt    | DATETIME                 | Default CURRENT_TIMESTAMP       |
| Status       | VARCHAR(50)              | 'Active' or 'Inactive'          |

## Setup

1. Create database:

```sql
CREATE DATABASE usersystem CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

2. Import `usersystem.sql` (phpMyAdmin or CLI).
3. Ensure `config.php` credentials match your MySQL (default XAMPP: root / empty password).
4. Place project inside your web root (e.g. `htdocs/UserManagementSystem`).
5. Open: `http://localhost/UserManagementSystem/index.php`.

## Core Endpoints

- `get_users.php` – List users (JSON)
- `get_user.php?id=ID` – Get single user (JSON)
- `add_user.php` – POST JSON `{username,email,password,status}`
- `update_user.php` – POST JSON (must include `UserID` + changed fields)
- `delete_user.php` – POST JSON `{UserID}`
- `dashboard_stats.php` – Stats JSON

### Roles Endpoints

Role management has been added via the `roles` table (fields: `RoleID`, `RoleName`, `Description`).

- `get_roles.php` – List all roles (JSON)
- `get_role.php?id=ID` – Get single role
- `add_role.php` – POST JSON `{ "roleName": "Admin", "description": "Full system access" }`
- `update_role.php` – POST JSON (must include `RoleID`, optional `roleName`, `description`)
- `delete_role.php` – POST JSON `{ "RoleID": 3 }`

Sample create:

```js
fetch("add_role.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    roleName: "Manager",
    description: "Can manage standard users",
  }),
})
  .then((r) => r.json())
  .then(console.log);
```

Sample update (rename + description change):

```js
fetch("update_role.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    RoleID: 2,
    roleName: "Supervisor",
    description: "Oversees teams",
  }),
})
  .then((r) => r.json())
  .then(console.log);
```

Sample delete:

```js
fetch("delete_role.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ RoleID: 2 }),
})
  .then((r) => r.json())
  .then(console.log);
```

Responses follow the pattern:

```json
{
  "success": true,
  "message": "Role created successfully",
  "data": {
    "RoleID": 5,
    "RoleName": "Manager",
    "Description": "Can manage standard users"
  }
}
```

Errors return `success: false` with an explanatory `message`.

### User ↔ Roles (Join Table)

The many-to-many relationship is handled via `userroles`:

| Field      | Type                     | Notes                     |
| ---------- | ------------------------ | ------------------------- |
| UserRoleID | INT (PK, AUTO_INCREMENT) | Surrogate primary key     |
| UserID     | INT (FK -> users.UserID) | Cascades on delete        |
| RoleID     | INT (FK -> roles.RoleID) | Cascades on delete        |
| AssignedAt | DATETIME                 | Default CURRENT_TIMESTAMP |

Unique constraint on `(UserID, RoleID)` prevents duplicates.

Endpoints:

- `get_user_roles.php?user_id=123` – Returns `{ user: {...}, roles: [ {RoleID, RoleName, Description, assigned} ] }`
- `update_user_roles.php` – POST JSON `{ "UserID": 123, "roleIDs": [1,2,3] }` (replaces all assignments atomically)

Sample fetch to get roles for a user:

```js
fetch("get_user_roles.php?user_id=1")
  .then((r) => r.json())
  .then(console.log);
```

Assign roles 1 and 3 to user 1:

```js
fetch("update_user_roles.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ UserID: 1, roleIDs: [1, 3] }),
})
  .then((r) => r.json())
  .then(console.log);
```

Front-end: In `index.php` a "Roles" button per user opens a modal with checkboxes built from `get_user_roles.php`; saving calls `update_user_roles.php`.

## Front-end Integration

`index.php` renders initial HTML + server-side user list and then uses `fetch()` to:

- Refresh user table (`get_users.php`)
- Add user (`add_user.php`)
- Edit email inline (`update_user.php`)
- Delete user (`delete_user.php`)
- Update dashboard cards (`dashboard_stats.php`)

## Security Notes

- Passwords hashed with `password_hash()` (bcrypt by default).
- ALWAYS serve over HTTPS in production.
- Add unique indexes:

```sql
ALTER TABLE users ADD UNIQUE KEY uq_users_username (Username);
ALTER TABLE users ADD UNIQUE KEY uq_users_email (Email);
```

- Validate & escape all inputs (see `sanitize_input`).
- Regenerate session IDs after login (login not yet implemented here).

## Schema Fixes Applied

The original dump had:

- `PasswordHash` as `int(12)` (incorrect) – changed to `varchar(255)`.
- `CreatedAt` as `date` – upgraded to `datetime` for precise auditing.
- Added default `'Active'` to `Status`.

If you already imported the old table, run:

```sql
ALTER TABLE users MODIFY PasswordHash varchar(255) NOT NULL COMMENT 'BCrypt/Argon hash';
ALTER TABLE users MODIFY CreatedAt datetime NOT NULL DEFAULT current_timestamp();
ALTER TABLE users MODIFY Status varchar(50) NOT NULL DEFAULT 'Active';
```

## Quick Test (via browser DevTools Console)

Add user:

```js
fetch("add_user.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    username: "tester1",
    email: "tester1@example.com",
    password: "secret123",
    status: "Active",
  }),
})
  .then((r) => r.json())
  .then(console.log);
```

## Next Extensions

- Role & permissions tables (`roles`, `userroles`).
- Activity log UI.
- Auth (login/logout + sessions + password reset).
- Pagination & sorting for large user sets.
- CSRF protection tokens.

## Profiles (1:1 User Extended Data)

The `profiles` table stores non-auth personal details for each user (optional 1:1). Separating this keeps the `users` table lean for auth & account management.

| Field       | Type         | Notes                                      |
| ----------- | ------------ | ------------------------------------------ |
| ProfileID   | INT PK AI    | Primary key                                |
| UserID      | INT FK       | Unique, references `users.UserID`          |
| FullName    | VARCHAR(255) | Required                                   |
| Address     | VARCHAR(255) | Optional                                   |
| PhoneNumber | VARCHAR(25)  | Optional – stored as string for formatting |
| DateOfBirth | DATE         | Optional (YYYY-MM-DD)                      |
| Avatar      | VARCHAR(255) | Optional (file path or URL)                |
| UpdatedAt   | DATETIME     | Auto-updated timestamp                     |

Constraint: UNIQUE(UserID) enforces 1:1. Foreign key cascades on user delete.

### Profile Endpoints

- `add_profile.php` – POST JSON (creates or updates if already exists)
  - Payload example:
  ```json
  {
    "UserID": 5,
    "FullName": "Jane Doe",
    "Address": "123 Main St",
    "PhoneNumber": "+1-555-0100",
    "DateOfBirth": "1990-04-15",
    "Avatar": "/uploads/avatars/jane.png"
  }
  ```
- `get_profile.php?user_id=5` – Fetch by UserID
- `get_profile.php?profile_id=10` – Fetch by ProfileID
- `update_profile.php` – POST JSON (partial update, supply `ProfileID` or `UserID` + changed fields)
  ```json
  {
    "UserID": 5,
    "Address": "456 Oak Ave",
    "Avatar": "https://cdn/app/jane.jpg"
  }
  ```
- `delete_profile.php` – POST JSON `{ "UserID": 5 }` or `{ "ProfileID": 10 }`

Responses follow the existing `{ success, message, data }` pattern.

### Quick Test (DevTools)

```js
fetch("add_profile.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    UserID: 1,
    FullName: "John Smith",
    Address: "42 Galaxy Way",
    PhoneNumber: "+44 7700 900123",
    DateOfBirth: "1985-12-01",
  }),
})
  .then((r) => r.json())
  .then(console.log);

fetch("get_profile.php?user_id=1")
  .then((r) => r.json())
  .then(console.log);

fetch("update_profile.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ UserID: 1, Address: "99 Updated Blvd" }),
})
  .then((r) => r.json())
  .then(console.log);
```

If you delete a user via `delete_user.php`, their profile is automatically removed (`ON DELETE CASCADE`).

## License

Educational example - adapt freely.
