# File Upload System

A secure file upload system built with API Platform, Symfony, and Doctrine ORM.

## Quick Start

### Upload a File
```bash
curl -X POST \
  http://localhost/api/uploaded_files \
  -H "Content-Type: multipart/form-data" \
  -F "file=@example.csv"
```

### Supported File Types
- CSV (`.csv`)
- JSON (`.json`)
- Excel 2007+ (`.xlsx`)
- OpenDocument Spreadsheet (`.ods`)

## Key Features

✅ **Secure File Storage** - Files stored outside web-accessible directory  
✅ **Comprehensive Validation** - Extension, MIME type, size, and content validation  
✅ **Database Tracking** - Complete file metadata with status management  
✅ **PSR Standards** - Follows PSR-4, PSR-12, and other PHP standards  
✅ **API Platform Integration** - Auto-generated OpenAPI docs and REST endpoints  
✅ **Error Handling** - Detailed error messages for debugging  

## Project Structure

```
src/
├── Controller/
│   └── FileUploadController.php    # Handles file upload requests
├── Entity/
│   └── UploadedFile.php           # Database entity for file metadata
├── Enum/
│   └── FileStatus.php             # File status enumeration
└── Service/
    ├── FileValidationService.php  # File validation logic
    └── FileStorageService.php     # File storage operations
```

## Database Migration

Run the migration to create the uploaded_files table:

```bash
php bin/console doctrine:migrations:migrate
```

## Configuration

The system is configured through Symfony's service container:

- Upload directory: `private/uploads/`
- Max file size: 10MB
- Auto-wiring enabled for all services

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/uploaded_files` | Upload a new file |
| GET | `/api/uploaded_files` | List all uploaded files |
| GET | `/api/uploaded_files/{id}` | Get specific file details |

## File Status Values

- `new` - Just uploaded, awaiting processing
- `processing` - Currently being processed
- `processed` - Successfully processed
- `failed` - Processing failed
- `archived` - Archived file

## Security Features

- Files stored outside web root (`private/uploads/`)
- Unique filename generation prevents conflicts
- Content validation beyond extension checking
- Size limits prevent DoS attacks
- Only safe file types allowed

## Testing

Test with different file types:

```bash
# Test CSV upload
curl -X POST http://localhost/api/uploaded_files -F "file=@test.csv"

# Test JSON upload  
curl -X POST http://localhost/api/uploaded_files -F "file=@test.json"

# Test invalid file type (should fail)
curl -X POST http://localhost/api/uploaded_files -F "file=@test.txt"
```

## Development Notes

- Uses PHP 8.3+ features (enums, readonly properties, named arguments)
- Built for Symfony 7.2+ and API Platform 4.1+
- Follows Domain-Driven Design principles
- Comprehensive error handling and validation
- Ready for horizontal scaling

For complete documentation, see `UPLOAD_API_DOCUMENTATION.md`.
