# AWS S3 Configuration Guide

## Required Environment Variables

Add these variables to your `.env` file to enable AWS S3 PDF storage:

```env
AWS_ACCESS_KEY_ID=your_access_key_here
AWS_SECRET_ACCESS_KEY=your_secret_key_here
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=lawn-filament-dev
AWS_USE_PATH_STYLE_ENDPOINT=false
```

## Fallback Behavior

The system is designed to gracefully handle missing AWS credentials:

- **If S3 is configured**: PDFs are stored in S3 with pre-signed URLs
- **If S3 is not configured**: PDFs fall back to local storage (`storage/app/public`)

## Testing

The application will work in both scenarios:
- With AWS S3 configured: Full S3 integration with secure pre-signed URLs
- Without AWS S3: Local storage fallback for development/testing

## S3 Bucket Setup

1. Create an S3 bucket named `lawn-filament-dev`
2. Configure bucket permissions for your AWS credentials
3. Ensure the bucket allows the necessary operations for PDF storage

## Security

- PDFs are stored with `private` visibility in S3
- Pre-signed URLs expire after 1 hour
- Download URLs include proper Content-Disposition headers
