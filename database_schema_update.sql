-- Updated User Management System Database Schema with Foreign Key Constraints
-- This file adds proper foreign key constraints to ensure referential integrity

-- Note: Run this after importing the original usersystem.sql file

-- Add foreign key constraints for Profiles table
ALTER TABLE `profiles` 
ADD CONSTRAINT `FK_profiles_users` 
FOREIGN KEY (`UserID`) REFERENCES `users`(`UserID`) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Add foreign key constraints for UserRoles table
ALTER TABLE `userroles` 
ADD CONSTRAINT `FK_userroles_users` 
FOREIGN KEY (`UserID`) REFERENCES `users`(`UserID`) 
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `userroles` 
ADD CONSTRAINT `FK_userroles_roles` 
FOREIGN KEY (`RoleID`) REFERENCES `roles`(`RoleID`) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Add foreign key constraints for ActivityLogs table
ALTER TABLE `activitylogs` 
ADD CONSTRAINT `FK_activitylogs_users` 
FOREIGN KEY (`UserID`) REFERENCES `users`(`UserID`) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Fix PhoneNumber column type in profiles table (should be VARCHAR, not INT)
ALTER TABLE `profiles` 
MODIFY `PhoneNumber` VARCHAR(20) NULL;

-- Add UpdatedAt column to profiles table if it doesn't exist
ALTER TABLE `profiles` 
ADD COLUMN `UpdatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Ensure proper column types and constraints
ALTER TABLE `users` 
MODIFY `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add unique constraints for username and email
ALTER TABLE `users` 
ADD CONSTRAINT `UQ_users_username` UNIQUE (`Username`);

ALTER TABLE `users` 
ADD CONSTRAINT `UQ_users_email` UNIQUE (`Email`);

-- Add unique constraint to prevent duplicate user-role assignments
ALTER TABLE `userroles` 
ADD CONSTRAINT `UQ_userroles_user_role` UNIQUE (`UserID`, `RoleID`);

-- Add unique constraint to ensure one profile per user
ALTER TABLE `profiles` 
ADD CONSTRAINT `UQ_profiles_userid` UNIQUE (`UserID`);

-- Insert sample roles if they don't exist
INSERT IGNORE INTO `roles` (`RoleID`, `RoleName`, `Description`) VALUES
(1, 'Administrator', 'Full system access and user management'),
(2, 'Manager', 'Can manage users and view reports'),
(3, 'User', 'Standard user with basic access'),
(4, 'Guest', 'Limited read-only access');

-- Create some sample data for testing (will be ignored if records already exist)
-- Note: These use IGNORE to prevent errors if data already exists

-- Example: Create admin user if it doesn't exist
INSERT IGNORE INTO `users` (`UserID`, `Username`, `Email`, `PasswordHash`, `Status`) 
VALUES (100, 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active');

-- Example: Create profile for admin user
INSERT IGNORE INTO `profiles` (`UserID`, `FullName`, `Address`, `PhoneNumber`, `DateOfBirth`, `Avatar`) 
VALUES (100, 'System Administrator', '123 Admin Street', '+1-555-0000', '1980-01-01', NULL);

-- Example: Assign admin role to admin user
INSERT IGNORE INTO `userroles` (`UserID`, `RoleID`) VALUES (100, 1);

-- Display success message
SELECT 'Database schema updated successfully with foreign key constraints!' AS Message;
