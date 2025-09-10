# User Management System - Profiles Feature Implementation

## Overview

This User Management System now includes a complete Profiles feature with transaction-based user creation, profile management, and CRUD operations. The system ensures data consistency through MySQL transactions and foreign key constraints.

## Features Implemented

### 1. Transaction-Based User Creation

When adding a new user, the system performs all operations within a single MySQL transaction:

```php
// Start transaction for atomicity
$conn->autocommit(FALSE);

try {
    // 1. Insert into Users table
    // 2. Insert into Profiles table (1:1 relationship)
    // 3. Insert into UserRoles table (if roles assigned)

    $conn->commit(); // All succeed
} catch (Exception $e) {
    $conn->rollback(); // All fail
}
```

**Files involved:**

- `users/add_user.php` - Handles user creation with profiles and roles
- `add_user_form.php` - User-friendly form for creating users

### 2. Profile Display and Management

The system provides multiple ways to view and manage user profiles:

#### Main Profile Page: `profile.php?id=USERID`

- Fetches data by joining Users and Profiles tables
- Shows FullName, Address, PhoneNumber, DateOfBirth, and Avatar
- Includes user account information and assigned roles
- Provides CRUD operations (Create, Read, Update, Delete)

**Key features:**

- **Responsive design** with modern UI/UX
- **Avatar support** with fallback to initials
- **Form validation** with proper error handling
- **Success/error messaging** with auto-hide functionality

#### Alternative Profile Page: `profile_page.php?user_id=USERID`

- Legacy profile page (maintained for compatibility)
- Rich interface with editing capabilities

### 3. Database Schema

The system uses four main tables with proper relationships:

```sql
Users (UserID PK, Username, Email, PasswordHash, CreatedAt, Status)
├── Profiles (ProfileID PK, UserID FK, FullName, Address, PhoneNumber, DateOfBirth, Avatar)
├── UserRoles (UserRoleID PK, UserID FK, RoleID FK)
└── ActivityLogs (ActivityLogID PK, UserID FK, Action, Timestamp, Details)

Roles (RoleID PK, RoleName, Description)
```

**Foreign Key Constraints:**

- `Profiles.UserID` → `Users.UserID` (CASCADE)
- `UserRoles.UserID` → `Users.UserID` (CASCADE)
- `UserRoles.RoleID` → `Roles.RoleID` (CASCADE)
- `ActivityLogs.UserID` → `Users.UserID` (CASCADE)

### 4. CRUD Operations for Profiles

#### Create Profile

```php
POST profile.php
{
    "action": "create_profile",
    "full_name": "John Doe",
    "address": "123 Main St",
    "phone_number": "+1-555-0100",
    "date_of_birth": "1990-01-01",
    "avatar": "https://example.com/avatar.jpg"
}
```

#### Read Profile

```php
GET profile.php?id=123
// or
GET profiles/get_profile.php?user_id=123
```

#### Update Profile

```php
POST profile.php
{
    "action": "update_profile",
    "full_name": "John Smith",
    // ... other fields
}
```

#### Delete Profile

```php
POST profile.php
{
    "action": "delete_profile"
}
```

## File Structure

### Core Files

- `config.php` - Database connection and utility functions
- `index.php` - Main dashboard with user listing
- `profile.php` - New profile page (as requested in requirements)
- `profile_page.php` - Alternative profile page

### User Management

- `users/add_user.php` - Transaction-based user creation API
- `users/get_user.php` - Retrieve user information
- `users/update_user.php` - Update user account details
- `users/delete_user.php` - Delete user (cascade to profiles/roles)
- `add_user_form.php` - User creation form

### Profile Management

- `profiles/get_profile.php` - Get profile by UserID or ProfileID
- `profiles/add_profile.php` - Create new profile
- `profiles/update_profile.php` - Update existing profile
- `profiles/delete_profile.php` - Delete profile (keeps user account)

### Role Management

- `roles/get_roles.php` - List all available roles
- `userroles/get_user_roles.php` - Get roles for specific user
- `userroles/update_user_roles.php` - Update user role assignments

### Testing and Documentation

- `test_user_creation_workflow.php` - Comprehensive test suite
- `test_system.php` - System verification tests
- `database_schema_update.sql` - Foreign key constraints and fixes
- `INTEGRATION_GUIDE.md` - Integration documentation

## Usage Examples

### 1. Create a New User with Profile

```javascript
fetch("users/add_user.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    username: "johndoe",
    email: "john@example.com",
    password: "password123",
    fullName: "John Doe",
    address: "123 Main St",
    phoneNumber: "+1-555-0100",
    dateOfBirth: "1990-01-01",
    avatar: "https://example.com/avatar.jpg",
    roles: [1, 3], // Assign Administrator and User roles
  }),
});
```

### 2. View User Profile

Navigate to: `profile.php?id=123`

### 3. Update Profile via Form

The profile page includes forms for updating profile information with real-time validation.

## Transaction Safety

The system ensures ACID properties:

1. **Atomicity**: All operations (user, profile, roles) succeed or fail together
2. **Consistency**: Foreign key constraints maintain referential integrity
3. **Isolation**: Concurrent operations don't interfere with each other
4. **Durability**: Committed data persists even if system crashes

## Error Handling

- **Input Validation**: Server-side validation for all inputs
- **SQL Error Handling**: Proper error messages and rollback on failure
- **User-Friendly Messages**: Clear success/error feedback
- **Graceful Degradation**: Fallback behavior when profiles don't exist

## Security Features

- **Password Hashing**: Using PHP's `password_hash()` with default algorithm
- **Input Sanitization**: All inputs are sanitized before database operations
- **SQL Injection Prevention**: Prepared statements for all queries
- **XSS Protection**: HTML entity encoding for output

## Testing

Run the test suite:

1. Visit `test_user_creation_workflow.php` to test complete user creation workflow
2. Visit `test_system.php` to verify system components
3. Use `add_user_form.php` to manually test user creation

## Setup Instructions

1. **Database Setup:**

   ```sql
   -- Import the main schema
   SOURCE usersystem.sql;

   -- Add foreign key constraints
   SOURCE database_schema_update.sql;
   ```

2. **Configuration:**

   - Update `config.php` with your database credentials
   - Ensure XAMPP is running (Apache + MySQL)

3. **Testing:**
   - Visit `http://localhost/UserManagementSystem/`
   - Test user creation with `add_user_form.php`
   - Test profile viewing with `profile.php?id=1`

## API Endpoints

| Endpoint                             | Method | Purpose                       |
| ------------------------------------ | ------ | ----------------------------- |
| `users/add_user.php`                 | POST   | Create user + profile + roles |
| `profiles/get_profile.php?user_id=X` | GET    | Get profile data              |
| `profile.php?id=X`                   | GET    | View profile page             |
| `profile.php`                        | POST   | Create/Update/Delete profile  |

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Responsive design works on mobile devices
- Progressive enhancement for older browsers
