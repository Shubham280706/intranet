# Contributing to WorkSpace Intranet

Thank you for your interest in contributing to WorkSpace Intranet! This document provides guidelines and instructions for contributing.

## 🎯 Code of Conduct

- Be respectful and inclusive
- Provide constructive feedback
- Help others learn and grow
- Report issues responsibly

## 🐛 Reporting Bugs

### Before Submitting a Bug Report
- Check if the issue already exists
- Verify you're using the latest version
- Gather diagnostic information

### How to Submit a Bug Report
1. Use a clear, descriptive title
2. Describe the exact steps to reproduce
3. Provide specific examples to demonstrate
4. Describe the observed behavior
5. Describe the expected behavior
6. Include screenshots/error messages

**Include:**
- PHP version
- MySQL version
- Operating system
- Browser (if frontend issue)
- Error logs

## ✨ Suggesting Enhancements

1. Use a clear, descriptive title
2. Provide a detailed description
3. List some examples
4. Describe the current behavior
5. Explain the expected behavior
6. Note any additional context

## 🔧 Development Setup

```bash
# Clone the repository
git clone https://github.com/Shubham280706/intranet.git
cd intranet

# Set up local environment
# Update config.php with your local database credentials
# Import database.sql

# Start development
# Access via http://localhost/intranet
```

## 📝 Commit Guidelines

### Commit Message Format
```
<type>(<scope>): <subject>

<body>

<footer>
```

### Type
- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation
- **style**: Formatting
- **refactor**: Code restructuring
- **perf**: Performance improvement
- **test**: Adding tests
- **chore**: Maintenance

### Example
```
feat(auth): add two-factor authentication

Add TOTP-based 2FA for enhanced security.
Implements 30-second time window validation.
Includes backup codes for account recovery.

Closes #123
```

## 🌳 Branch Naming

```
feature/description
bugfix/issue-description
docs/update-readme
```

## 📦 Pull Request Process

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Make** your changes
4. **Test** thoroughly
5. **Commit** with clear messages
6. **Push** to your fork
7. **Open** a Pull Request

### PR Requirements
- Clear description of changes
- Reference related issues (#123)
- Screenshots for UI changes
- Updated documentation if needed
- No breaking changes (unless discussed)

### PR Title Format
```
[type] Brief description of changes
```

## 🧪 Testing

Before submitting:
1. Test in multiple browsers
2. Test on mobile devices
3. Verify database transactions
4. Check for console errors
5. Test with different user roles

## 📚 Documentation

- Update README.md if changing features
- Add comments for complex logic
- Document API changes
- Update CHANGELOG.md

## 🎨 Code Style

### PHP
```php
<?php
// Use PSR-12 coding standard
// Use meaningful variable names
// Add comments for complex logic

function getUserProfile($userId) {
    // Implementation
}
?>
```

### JavaScript
```javascript
// Use camelCase for variables
// Use clear, descriptive names
// Add comments for complex logic

async function loadUserData(id) {
    // Implementation
}
```

### CSS
```css
/* Use kebab-case for classes */
/* Group related properties */
/* Add comments for complex layouts */

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}
```

## 🔒 Security Considerations

- Never commit sensitive data (passwords, API keys)
- Use PDO prepared statements
- Sanitize user input
- Validate file uploads
- Use HTTPS in production

## 📋 Checklist

Before submitting a PR:
- [ ] Code follows style guidelines
- [ ] No console errors/warnings
- [ ] Tests pass
- [ ] Documentation updated
- [ ] No sensitive data in commits
- [ ] Commit messages are clear

## 🚀 Release Process

1. Update version in relevant files
2. Update CHANGELOG.md
3. Create release tag
4. Publish release notes

## ❓ Questions?

- Create an issue with `[QUESTION]` tag
- Check existing issues for answers
- Use discussions for general questions

## 📄 License

By contributing, you agree your contributions are licensed under the MIT License.

---

**Thank you for making WorkSpace Intranet better! 🎉**
