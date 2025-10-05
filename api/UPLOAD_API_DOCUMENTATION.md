# File Upload API Documentation

## Overview

This API provides a secure file upload endpoint for handling CSV, JSON, XLSX, and ODS files. Files are validated, stored in a private directory, and tracked in the database with comprehensive metadata.

## Features

- **File Type Validation**: Only CSV, JSON, XLSX, and ODS files are accepted
- **Security**: Files are stored in a private directory outside the web root
- **Database Tracking**: Complete file metadata stored in database
- **Status Management**: File processing status tracking with enum-based states
- **PSR Standards**: Code follows PSR-4, PSR-12, and other PHP standards
- **API Platform Integration**: Full OpenAPI documentation and REST endpoints

## File Upload Endpoint

### POST /api/uploaded_files

Upload a new file to the system.

**Request:**
- Method: `POST`
- Content-Type: `multipart/form-data`
- Body: File field named `file`

**Allowed File Types:**
- CSV (`.csv`)
- JSON (`.json`) 
- Excel 2007+ (`.xlsx`)
- OpenDocument Spreadsheet (`.ods`)

**File Size Limit:** 10MB

**Example Request:**
```bash
curl -X POST \
  http://localhost/api/uploaded_files \
  -H "Content-Type: multipart/form-data" \
  -F "file=@example.csv"
```

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": 1,
    "originalName": "example.csv",
    "extension": "csv",
    "size": 1024,
    "mimeType": "text/csv",
    "status": "new",
    "createdAt": "2025-01-03T12:00:00+00:00",
    "updatedAt": "2025-01-03T12:00:00+00:00"
  }
}
```

**Error Response (400 Bad Request):**
```json
{
  "success": false,
  "message": "File validation failed",
  "errors": [
    "File extension \"txt\" is not allowed. Allowed extensions: csv, json, xlsx, ods"
  ]
}
```

## File Management Endpoints

### GET /api/uploaded_files

List all uploaded files with pagination.

**Response:**
```json
{
  "@context": "/api/contexts/UploadedFile",
  "@id": "/api/uploaded_files",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/uploaded_files/1",
      "@type": "UploadedFile",
      "id": 1,
      "originalName": "example.csv",
      "extension": "csv",
      "size": 1024,
      "mimeType": "text/csv",
      "status": "new",
      "createdAt": "2025-01-03T12:00:00+00:00",
      "updatedAt": "2025-01-03T12:00:00+00:00"
    }
  ]
}
```

### GET /api/uploaded_files/{id}

Get details of a specific uploaded file.

**Response:**
```json
{
  "@context": "/api/contexts/UploadedFile",
  "@id": "/api/uploaded_files/1",
  "@type": "UploadedFile",
  "id": 1,
  "originalName": "example.csv",
  "extension": "csv",
  "size": 1024,
  "mimeType": "text/csv",
  "status": "new",
  "createdAt": "2025-01-03T12:00:00+00:00",
  "updatedAt": "2025-01-03T12:00:00+00:00"
}
```

## File Status Enum

Files can have the following statuses:

- `new` - File just uploaded, awaiting processing
- `processing` - File is currently being processed
- `processed` - File has been successfully processed
- `failed` - File processing failed
- `archived` - File has been archived

## Database Schema

### Table: uploaded_files

| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER | Primary key, auto-increment |
| path | VARCHAR(500) | Relative path to stored file |
| original_name | VARCHAR(255) | Original filename from upload |
| extension | VARCHAR(50) | File extension (csv, json, xlsx, ods) |
| size | INTEGER | File size in bytes |
| mime_type | VARCHAR(32) | MIME type of the file |
| status | VARCHAR(255) | Current status (enum value) |
| created_at | TIMESTAMP | When the file was uploaded |
| updated_at | TIMESTAMP | When the record was last modified |

**Indexes:**
- Primary key on `id`
- Index on `status` for filtering
- Index on `created_at` for sorting

## File Storage

Files are stored in the `private/uploads/` directory relative to the project root. This directory:

- Is created automatically if it doesn't exist
- Is outside the web-accessible public directory for security
- Uses unique filenames to prevent conflicts
- Maintains original file extensions for type identification

**Filename Format:**
```
{slugified-original-name}_{timestamp}_{unique-id}.{extension}
```

Example: `my-data-file_2025-01-03_12-00-00_61f5e123abc.csv`

## Validation Rules

### File Extension Validation
- Only CSV, JSON, XLSX, and ODS files are accepted
- Case-insensitive extension checking
- Validation occurs on both extension and MIME type

### File Size Validation
- Maximum file size: 10MB
- Size validation prevents memory exhaustion
- Clear error messages for oversized files

### Content Validation
- **JSON files**: Validated for proper JSON syntax
- **CSV files**: Checked for readable CSV format
- **XLSX/ODS files**: Basic format validation through MIME type

### Security Validation
- Files stored outside web root
- Unique filenames prevent path traversal
- MIME type validation prevents executable uploads

## Error Handling

The API provides comprehensive error handling with descriptive messages:

### Common Error Responses

**File Too Large (400 Bad Request):**
```json
{
  "success": false,
  "message": "File validation failed",
  "errors": [
    "File size (15.50 MB) exceeds maximum allowed size (10.00 MB)"
  ]
}
```

**Invalid File Type (400 Bad Request):**
```json
{
  "success": false,
  "message": "File validation failed",
  "errors": [
    "File extension \"txt\" is not allowed. Allowed extensions: csv, json, xlsx, ods"
  ]
}
```

**Invalid JSON Content (400 Bad Request):**
```json
{
  "success": false,
  "message": "File validation failed",
  "errors": [
    "Invalid JSON format: Syntax error"
  ]
}
```

**Server Error (500 Internal Server Error):**
```json
{
  "success": false,
  "message": "An unexpected error occurred during file upload"
}
```

## Architecture

### Components

1. **UploadedFile Entity** (`src/Entity/UploadedFile.php`)
   - Doctrine ORM entity for database persistence
   - API Platform annotations for REST endpoints
   - Validation constraints and lifecycle callbacks

2. **FileStatus Enum** (`src/Enum/FileStatus.php`)
   - Strongly-typed status values
   - Helper methods for labels and validation

3. **FileUploadController** (`src/Controller/FileUploadController.php`)
   - Handles POST requests for file uploads
   - Coordinates validation, storage, and database operations
   - Returns structured JSON responses

4. **FileValidationService** (`src/Service/FileValidationService.php`)
   - Validates file extensions, MIME types, and sizes
   - Performs content validation for specific file types
   - Returns detailed error messages

5. **FileStorageService** (`src/Service/FileStorageService.php`)
   - Manages file storage operations
   - Generates unique filenames
   - Handles directory creation and permissions

### Service Configuration

Services are auto-configured through Symfony's dependency injection:

```yaml
# config/services.yaml
services:
    App\Service\FileStorageService:
        arguments:
            $projectDir: '%app.project_dir%'
```

## Security Considerations

1. **File Storage**: Files stored outside web-accessible directory
2. **Filename Generation**: Unique filenames prevent conflicts and attacks
3. **Content Validation**: File content validated beyond extension checking
4. **Size Limits**: Prevents memory exhaustion and DoS attacks
5. **Type Restrictions**: Only safe file types allowed
6. **Error Handling**: No sensitive information leaked in error messages

## Development Setup

### Requirements
- PHP 8.3+
- Symfony 7.2+
- PostgreSQL 16+
- API Platform 4.1+

### Installation

1. Install dependencies:
```bash
composer install
```

2. Run database migrations:
```bash
php bin/console doctrine:migrations:migrate
```

3. Create upload directory:
```bash
mkdir -p private/uploads
chmod 755 private/uploads
```

### Testing

Test the file upload endpoint:

```bash
# Upload a CSV file
curl -X POST \
  http://localhost/api/uploaded_files \
  -H "Content-Type: multipart/form-data" \
  -F "file=@test.csv"

# List uploaded files
curl http://localhost/api/uploaded_files

# Get specific file
curl http://localhost/api/uploaded_files/1
```

## API Documentation

Full interactive API documentation is available at `/api/docs` when the application is running. This includes:

- Complete endpoint documentation
- Request/response schemas
- Interactive testing interface
- OpenAPI 3.0 specification

## Extending the System

### Adding New File Types

1. Update `FileValidationService::ALLOWED_EXTENSIONS`
2. Add MIME types to `FileValidationService::ALLOWED_MIME_TYPES`
3. Add content validation logic if needed
4. Update documentation

### Adding File Processing

1. Create a new status in `FileStatus` enum
2. Implement processing service
3. Add status update endpoints
4. Consider using Symfony Messenger for async processing

### Adding File Download

1. Create download controller
2. Implement security checks
3. Stream file content with proper headers
4. Log download activities

## Troubleshooting

### Common Issues

**Permission Denied on Upload Directory:**
```bash
chmod 755 private/uploads
chown www-data:www-data private/uploads
```

**Database Connection Issues:**
Check `.env` file for correct DATABASE_URL configuration.

**File Size Limits:**
Check PHP settings:
- `upload_max_filesize`
- `post_max_size`
- `memory_limit`

**MIME Type Detection:**
Ensure `fileinfo` PHP extension is installed:
```bash
php -m | grep fileinfo
```
