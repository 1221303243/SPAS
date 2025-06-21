# SPAS Database Setup Guide

This guide will help you recreate your SPAS (Student Performance Assessment System) database after XAMPP corruption.

## Files Created

1. **`SPAS_database_schema.sql`** - Contains all table structures, relationships, triggers, and views
2. **`SPAS_sample_data.sql`** - Contains sample data for testing the system
3. **`DATABASE_SETUP_README.md`** - This setup guide

## Database Structure Overview

The SPAS system uses the following main tables:

### Core Tables
- **`users`** - Main authentication table with role-based access
- **`students`** - Student information linked to users
- **`lecturers`** - Lecturer information linked to users
- **`admin`** - Admin information linked to users

### Academic Tables
- **`current_semester`** - Manages trimester/semester periods
- **`subjects`** - Course information with assessment types
- **`classes`** - Class instances linked to subjects and lecturers
- **`student_classes`** - Many-to-many relationship between students and classes

### Assessment Tables
- **`assessment_plans`** - Assessment structure with weightage
- **`grades`** - Student grades with calculated totals
- **`feedback`** - Detailed feedback from lecturers

### System Tables
- **`calendar_events`** - Academic calendar events
- **`feedback_summary`** - View for easy feedback querying

## Setup Instructions

### Step 1: Access phpMyAdmin
1. Start your XAMPP server
2. Open your browser and go to `http://localhost/phpmyadmin`
3. Login with your MySQL credentials (usually root with no password)

### Step 2: Create Database
1. Click on "New" in the left sidebar
2. Enter "SPAS" as the database name
3. Click "Create"

### Step 3: Import Schema
1. Select the "SPAS" database from the left sidebar
2. Click on the "Import" tab
3. Click "Choose File" and select `SPAS_database_schema.sql`
4. Click "Go" to execute the schema

### Step 4: Import Sample Data (Optional)
1. Stay in the "SPAS" database
2. Click on the "Import" tab again
3. Click "Choose File" and select `SPAS_sample_data.sql`
4. Click "Go" to insert sample data

## Database Relationships

### User Management
```
users (1) ←→ (1) students
users (1) ←→ (1) lecturers  
users (1) ←→ (1) admin
```

### Academic Structure
```
subjects (1) ←→ (many) classes
classes (many) ←→ (many) students (via student_classes)
subjects (many) ←→ (1) lecturers
subjects (many) ←→ (1) current_semester
```

### Assessment System
```
subjects (1) ←→ (many) assessment_plans
assessment_plans (1) ←→ (many) grades
grades (many) ←→ (1) students
grades (many) ←→ (1) classes
grades (1) ←→ (many) feedback
```

## Sample Data Included

### Users
- **Admin**: admin@spas.com (password: password)
- **Lecturers**: 
  - lecturer1@spas.com (Dr. John Smith)
  - lecturer2@spas.com (Prof. Sarah Johnson)
- **Students**:
  - student1@spas.com (Alice Brown)
  - student2@spas.com (Bob Wilson)
  - student3@spas.com (Carol Davis)

### Subjects
- MATH101 - Mathematics I (coursework + final exam)
- PHY101 - Physics I (coursework + final exam)
- ENG101 - English Composition (coursework only)
- CS101 - Computer Science I (coursework + final exam)

### Assessment Structure
- **Coursework**: Assignments, lab reports, essays, projects
- **Final Exams**: End-of-semester examinations
- **Weightage**: Automatically calculated based on assessment plans

## Key Features

### Assessment Types
- **coursework_final_exam**: Subjects with both coursework and final exam
- **coursework_only**: Subjects with only coursework assessments

### Grade Calculation
- Automatic calculation of weighted marks
- Letter grade assignment based on total marks
- Support for both coursework-only and mixed assessment types

### Academic Calendar
- Event management for assignments and exams
- Subject-specific calendar entries
- Due date tracking

### Feedback System
- Structured feedback with strengths, areas for improvement
- Grade justification and recommendations
- Status tracking (draft, published, archived)

## Database Constraints

### Foreign Key Relationships
- All relationships are properly defined with CASCADE delete
- Ensures data integrity across the system

### Unique Constraints
- One assessment plan per subject per assessment type
- One grade per student per assessment per class
- One calendar event per date per subject

### Triggers
- Weightage validation for assessment plans
- Ensures total weightage per category doesn't exceed 100%

## Troubleshooting

### Common Issues

1. **Foreign Key Errors**
   - Ensure you run the schema file first
   - Check that all referenced tables exist

2. **Duplicate Entry Errors**
   - The sample data includes unique constraints
   - Remove existing data before importing if needed

3. **Permission Errors**
   - Ensure your MySQL user has CREATE, INSERT, UPDATE privileges
   - Check XAMPP MySQL service is running

### Verification Queries

After setup, you can verify the database with these queries:

```sql
-- Check table counts
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as total_students FROM students;
SELECT COUNT(*) as total_subjects FROM subjects;
SELECT COUNT(*) as total_classes FROM classes;

-- Check relationships
SELECT s.subject_name, l.name as lecturer_name 
FROM subjects s 
JOIN lecturers l ON s.lecturer_id = l.lecturer_id;

-- Check assessment structure
SELECT s.subject_name, ap.assessment_type, ap.category, ap.weightage
FROM subjects s 
JOIN assessment_plans ap ON s.subject_id = ap.subject_id;
```

## Next Steps

1. **Test the Application**: Access your SPAS application to ensure it connects properly
2. **Update Configuration**: Check that `auth/db_connection.php` points to the correct database
3. **Add Real Data**: Replace sample data with your actual academic data
4. **Backup Regularly**: Set up regular database backups to prevent future data loss

## Support

If you encounter any issues during setup:
1. Check the MySQL error logs in XAMPP
2. Verify all SQL files are complete and properly formatted
3. Ensure your XAMPP MySQL version is compatible (5.7+ recommended)

The database structure is designed to be scalable and maintainable, supporting the full functionality of your SPAS system. 