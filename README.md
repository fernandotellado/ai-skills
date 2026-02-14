# AI Skills Collection

A curated collection of specialized AI skills for WordPress development, content creation, marketing, and more. These skills follow the [agentskills.io](https://agentskills.io) standard and work with Claude, ChatGPT, and other AI assistants.

## What are AI Skills?

AI Skills are structured knowledge documents that provide AI assistants with deep, specialized expertise in specific domains. Instead of relying solely on training data, skills give AI models access to:

- Current best practices and standards
- Detailed implementation guides
- Security and performance considerations
- Real-world code examples and patterns
- Common pitfalls and how to avoid them

Think of skills as expert reference manuals that AI can consult to provide more accurate, up-to-date, and comprehensive assistance.

## Why This Collection?

Most WordPress and web development happens through trial and error, outdated tutorials, or incomplete documentation. This collection provides AI assistants with authoritative, comprehensive knowledge to:

- Generate secure, standards-compliant code
- Follow official guidelines and best practices
- Avoid common vulnerabilities and mistakes
- Provide consistent, reliable guidance

All skills are based on official documentation, coding standards, and real-world professional experience.

## Available Skills

### WordPress Development

#### [WordPress Plugin Security](skills/wp-plugin-security.md)
Comprehensive security guidelines for WordPress plugin development covering sanitization, validation, escaping, nonces, capabilities, SQL injection prevention, XSS protection, and CSRF mitigation. Based on official WordPress Developer Resources and WordPress Coding Standards.

**Key topics:**
- Input sanitization and validation
- Output escaping
- Nonce implementation
- User capabilities and permissions
- SQL injection prevention
- AJAX and REST API security
- File handling security
- Common vulnerabilities (XSS, CSRF, SQLi)

**Compatibility:** WordPress 6.0+ / PHP 7.4+

---

#### [WordPress Plugin Performance](skills/wp-plugin-performance.md)
Comprehensive performance guidelines for WordPress plugin development covering database optimization, object caching, transients, conditional asset loading, efficient hooks, HTTP requests, WP-Cron, AJAX/REST optimization, and common anti-patterns. Based on official WordPress Developer Resources and WP VIP documentation.

**Key topics:**
- Database query optimization (WP_Query, $wpdb)
- Options and autoload management
- Object cache implementation
- Transients best practices
- Conditional asset loading
- Efficient hook usage
- External HTTP requests
- WP-Cron configuration
- AJAX and REST API optimization
- Anti-patterns detection and fixes
- Measurement and profiling

**Compatibility:** WordPress 6.0+ / PHP 7.4+

---

*More skills coming soon: Interactivity API, Block Development, Block Themes, REST API, and more.*

## How to Use These Skills

### With Claude (via Projects)

1. Create a new Project in Claude
2. Add the skill markdown file to your project knowledge
3. Claude will automatically reference the skill when relevant to your questions

### With ChatGPT (via Custom GPTs)

1. Create a Custom GPT or edit an existing one
2. Upload the skill markdown file to the GPT's knowledge base
3. The GPT will use the skill content to provide specialized assistance

### With Other AI Assistants

Most modern AI assistants support knowledge documents:

1. Look for "Knowledge Base", "Custom Instructions", or "Files" features
2. Upload the skill markdown files
3. Reference the skill in your prompts if needed

### As Human Reference

All skills are written in clear markdown and serve as excellent reference documentation for developers. You can:

- Read them directly on GitHub
- Download and use them as personal documentation
- Reference specific sections when needed
- Share them with your team

## Skill Format

All skills follow the agentskills.io standard format with:

```yaml
---
name: skill-name
description: "Brief description"
compatibility: "Version/platform requirements"
license: GPL-2.0-or-later
metadata:
  author: ayudawp
  version: "1.0"
---
```

Each skill includes:

- **When to use**: Triggers and use cases
- **Core concepts**: Fundamental principles
- **Functions/APIs**: Detailed reference tables
- **Code examples**: Real-world, copy-paste ready code
- **Best practices**: Do's and don'ts
- **Checklists**: Review and validation lists
- **References**: Official documentation links

## Contributing

### Request a Skill

Have a topic you'd like to see covered? [Open an issue](https://github.com/fernandotellado/ai-skills/issues) describing:

- The domain/topic
- What problems it would solve
- Any specific aspects to cover

### Suggest Improvements

Found an error or have suggestions for existing skills? Pull requests welcome! Please ensure:

- Information is accurate and sourced from official documentation
- Code examples follow best practices and standards
- Formatting is consistent with existing skills
- Content is clear and actionable

### Contribute a Skill

Want to contribute a new skill? Great! Please:

1. Follow the agentskills.io format
2. Include comprehensive examples
3. Reference official documentation
4. Test with AI assistants before submitting
5. Submit a pull request with your skill

## License

All skills in this repository are licensed under [GPL-2.0-or-later](LICENSE), same as WordPress itself.

## Author

Created and maintained by [Fernando Tellado](https://github.com/fernandotellado) / [AyudaWP](https://ayudawp.com)

## Credits

Skills are based on:

- Official WordPress Developer Resources
- WordPress Coding Standards
- WordPress VIP Documentation
- Real-world professional development experience
- Community feedback and contributions

---

**Star this repo** if you find these skills useful! It helps others discover them.
