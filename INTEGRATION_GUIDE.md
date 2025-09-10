# User Management System - Fixed and Integrated

## Overview

This User Management System provides complete CRUD operations for Users, Roles, UserRoles, and Profiles with proper foreign key relationships and atomic transactions.

## Database Schema

### Tables and Relationships

- **Users**: Core user account information (login credentials, status)
- **Roles**: System roles and permissions
- **UserRoles**: Many-to-many relationship between Users and Roles
- **Profiles**: Extended user information (1:1 with Users)

### Foreign Key Constraints

```sql
-- Profiles.UserID → Users.UserID (1:1 relationship)
ALTER TABLE `profiles`
  ADD CONSTRAINT `fk_profiles_user`
    FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- UserRoles.UserID → Users.UserID
ALTER TABLE `userroles`
  ADD CONSTRAINT `fk_userroles_user`
    FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- UserRoles.RoleID → Roles.RoleID
ALTER TABLE `userroles`
  ADD CONSTRAINT `fk_userroles_role`
    FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`)
    ON DELETE CASCADE ON UPDATE CASCADE;
```

## Fixed Issues

### 1. Database Integrity

- ✅ Added missing foreign key constraints for UserRoles table
- ✅ Ensured proper CASCADE behavior for related data

### 2. Code Quality

- ✅ Removed dependency on non-existent ActivityLogs table
- ✅ Fixed duplicate PHP closing tags in add_user.php
- ✅ Ensured all operations use transactions for data consistency

### 3. Profile Integration

- ✅ User creation now atomically inserts into Users, UserRoles, and Profiles
- ✅ Profile page provides full CRUD operations linked by user_id
- ✅ Proper redirection from user list to profile management

## User Creation Workflow

When adding a new user, the system performs these operations in a single transaction:

1. **Validate Input**: Check required fields, email format, username uniqueness
2. **Insert User**: Add basic login info to Users table
3. **Create Profile**: Add extended info to Profiles table with user_id link
4. **Assign Roles**: Add role assignments to UserRoles table
5. **Commit**: All operations succeed or all are rolled back

### Example JSON Request to `users/add_user.php`:

```json
{
  "username": "john_doe",
  "email": "john@example.com",
  "password": "securepassword123",
  "fullName": "John Doe",
  "address": "123 Main Street, City, State",
  "phoneNumber": "+1-555-0123",
  "dateOfBirth": "1990-01-15",
  "avatar": "https://example.com/avatar.jpg",
  "roles": [1, 2],
  "status": "Active"
}
```

## Profile Management

### View Profile

- Navigate to: `profile_page.php?user_id=123`
- Displays user account info, roles, and profile data
- Handles case where profile doesn't exist yet

### Create/Update Profile

- **Endpoint**: `profiles/add_profile.php` (upsert behavior)
- **Endpoint**: `profiles/update_profile.php` (partial updates)

### Delete Profile

- **Endpoint**: `profiles/delete_profile.php`
- Removes profile data but keeps user account

### Get Profile

- **Endpoint**: `profiles/get_profile.php?user_id=123`
- Returns combined user and profile information

## File Structure

### Core Files

- `config.php` - Database connection and utility functions
- `index.php` - Main dashboard with user management interface
- `usersystem.sql` - Database schema with proper foreign keys

### User Operations

- `users/add_user.php` - Create user with profile and roles
- `users/get_users.php` - Retrieve user list
- `users/update_user.php` - Update user account info
- `users/delete_user.php` - Remove user (cascades to profile/roles)

### Profile Operations

- `profiles/add_profile.php` - Create or update profile (upsert)
- `profiles/get_profile.php` - Retrieve profile by user_id
- `profiles/update_profile.php` - Partial profile updates
- `profiles/delete_profile.php` - Remove profile data

### Role Management

- `roles/add_role.php` - Create system roles
- `roles/get_roles.php` - List available roles
- `userroles/update_user_roles.php` - Manage user role assignments

### Profile Interface

- `profile_page.php` - Dedicated profile management page
- `redirect_to_profile.php` - Helper for profile redirection

## Integration Examples

### Redirect to Profile from User List

```javascript
// Button click handler in index.php
function viewUserProfile(userID) {
  window.location.href = "profile_page.php?user_id=" + userID;
}
```

### Profile Operations via JavaScript

```javascript
// Save profile data
async function saveProfile(userData) {
  const response = await fetch("profiles/update_profile.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(userData),
  });
  return response.json();
}
```

## API Response Format

All endpoints return consistent JSON responses:

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    /* relevant data object */
  }
}
```

Error responses:

```json
{
  "success": false,
  "message": "Error description",
  "data": null
}
```

## Usage Instructions

### 1. Database Setup

1. Import `usersystem.sql` to create tables with proper constraints
2. Verify foreign key relationships are active

### 2. Create Users

1. Use the "Add User" form in `index.php`
2. Fill in basic info (username, email, password) and profile info (full name, etc.)
3. Assign roles as needed
4. System creates all related records atomically

### 3. Manage Profiles

1. Click "View Profile" button next to any user in the list
2. Edit profile information on the dedicated profile page
3. Save changes or delete profile as needed
4. Navigate back to user list

### 4. Role Management

1. Use the Roles section in `index.php` to create system roles
2. Assign roles to users during creation or via the Roles button
3. Update role assignments as needed

## Security Features

- Input sanitization and validation
- SQL injection prevention with prepared statements
- Password hashing with PHP's password_hash()
- Transaction-based operations for data consistency
- Foreign key constraints for referential integrity

## Error Handling

- Comprehensive try-catch blocks in all operations
- Transaction rollback on any failure
- User-friendly error messages
- Proper HTTP status codes and JSON responses
