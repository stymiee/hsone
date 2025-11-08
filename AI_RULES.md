# AI Agent Rules for Photo Email Uploader Project

## Commit Message Guidelines

### When User Requests a Commit Message:
- **ONLY provide the commit message text**
- **DO NOT execute the actual git commit command**
- **DO NOT include emojis in commit messages**
- Use conventional commit format: `type: description`
- Keep messages concise but descriptive
- Focus on what was changed, not how it was changed

### Examples:
✅ **Correct:**
```
feat: Add Google Photos integration with authentication flow

- Implement GooglePhotosService class with OAuth 2.0 support
- Add GooglePhotosUploader for batch photo processing
- Create google_auth_tokens table for token storage
- Add comprehensive error handling and logging
```

❌ **Incorrect:**
```
🚀 feat: Add Google Photos integration with authentication flow

- Implement GooglePhotosService class with OAuth 2.0 support
- Add GooglePhotosUploader for batch photo processing
- Create google_auth_tokens table for token storage
- Add comprehensive error handling and logging

[AI would then execute: git commit -m "..."]
```

## General Project Guidelines

### Code Quality:
- Follow existing code patterns and conventions
- Add proper error handling and logging
- Include comprehensive comments for complex logic
- Test changes thoroughly before suggesting commits

### Database Changes:
- Always update both schema.sql and simple-init.php
- Ensure foreign key constraints are properly defined
- Test database initialization scripts after changes

### Testing:
- Run relevant test scripts after making changes
- Fix any failing tests before suggesting commits
- Document any skipped tests and reasons

### Documentation:
- Update README.md for significant feature additions
- Document configuration changes in config.php.example
- Keep requirements.md up to date with progress

## Communication Style:
- Be direct and technical
- Provide clear explanations for complex changes
- Ask clarifying questions when requirements are unclear
- Focus on practical solutions over theoretical approaches

## File Management:
- Clean up temporary files after use
- Don't create unnecessary documentation files
- Follow the existing project structure
- Use appropriate file extensions and naming conventions

---

**Last Updated:** 2024-01-01
**Version:** 1.0
