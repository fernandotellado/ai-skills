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

#### [WordPress Plugin Security](https://github.com/fernandotellado/ai-skills/blob/main/wp-plugin-security/SKILL.md)
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

#### [WordPress Plugin Performance](https://github.com/fernandotellado/ai-skills/blob/main/wp-plugin-performance/SKILL.md)
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

#### [WordPress Plugin Development](https://github.com/fernandotellado/ai-skills/blob/main/wp-plugin-development/SKILL.md)
Comprehensive architecture and development guidelines for WordPress plugins published on wordpress.org, covering file structure, plugin header, lifecycle hooks, Settings API, admin UI, custom post types, custom database tables, internationalization, plugin dependencies, and submission requirements. Based on the official WordPress Plugin Developer Handbook and Plugin Review Team guidelines.

**Key topics:**
- Plugin file structure and main file bootstrap
- Plugin header requirements for wordpress.org
- Lifecycle hooks (activation, deactivation, uninstall)
- Main plugin class with singleton pattern
- Actions and filters system
- Settings API: sections, fields, sanitization
- Admin menu and settings page
- Custom post types and taxonomies
- Custom database tables with dbDelta
- Internationalization and translation readiness
- Plugin dependencies management
- Asset loading rules (no inline scripts/styles)
- Prefixing rules and naming conventions
- wordpress.org submission requirements and common rejection reasons
- readme.txt structure and rules
- Debugging tools and best practices

**Compatibility:** WordPress 6.0+ / PHP 7.4+

---

*More WordPress skills coming soon: Interactivity API, Block Development, Block Themes, REST API, and more.*

### Writing & Content

#### [Humanize Text — English](https://github.com/fernandotellado/ai-skills/blob/main/humanize-text-en/SKILL.md)
Removes predictable AI writing patterns from English text to make it sound natural and human-written. Based on Wikipedia's "Signs of AI writing" guide, the stop-slop and humanizer community projects, and extensive research on AI-generated text patterns.

**Key topics:**
- AI vocabulary detection and replacement (delve, tapestry, leverage, utilize, robust, seamless...)
- Throat-clearing openers and emphasis crutches removal
- Structural pattern breaking (negative parallelism, rule of three, rhetorical Q&A, em dash overuse)
- Trailing -ing clause detection (significance inflation)
- Copula avoidance correction ("serves as" → "is")
- Emoji bullet removal and formatting cleanup
- Hedging phrase elimination
- Scoring system for natural writing quality (5 dimensions, 50-point scale)
- Adaptation guidelines by text type (blog posts, marketing, docs, emails, social media)
- 7 complete before/after transformation examples

**Includes reference files:**
- `references/phrases.md` — Full list of banned words and phrases with alternatives
- `references/structures.md` — 16 structural patterns with AI examples and corrected versions
- `references/examples.md` — Complete before/after text transformations

**Compatibility:** Any AI assistant generating English text

---

#### [Humanizar texto — Español](https://github.com/fernandotellado/ai-skills/blob/main/humanizar-texto-es/SKILL.md)
Elimina patrones de escritura predecibles de la IA en textos en español de España para que suenen naturales y humanos. Basada en la guía "Signs of AI writing" de Wikipedia, los proyectos comunitarios stop-slop y humanizer, y una extensa investigación sobre patrones de texto generado por IA adaptados al español peninsular.

**Key topics:**
- Detección y sustitución de vocabulario IA en español (panorama, ecosistema, paradigma, implementar, optimizar, potenciar...)
- Eliminación de muletillas de apertura y cierre (En el mundo actual, Cabe destacar, En definitiva...)
- Rotura de patrones estructurales (contraste negativo, regla de tres, pregunta retórica, gerundio colgante)
- Detección de metáforas gastadas (pilar fundamental, piedra angular, motor de cambio, hoja de ruta)
- Eliminación de inflación de importancia y conectores formales excesivos
- Sistema de puntuación para calidad de escritura natural (5 dimensiones, escala de 50 puntos)
- Guías de adaptación por tipo de texto (artículos, marketing, documentación, emails, redes sociales)
- 6 transformaciones completas de antes/después con patrones señalados

**Includes reference files:**
- `references/palabras.md` — Lista completa de palabras y frases a evitar con alternativas
- `references/estructuras.md` — 15 patrones estructurales con ejemplos IA y versiones corregidas
- `references/ejemplos.md` — Transformaciones completas de texto antes/después

**Compatibility:** Any AI assistant generating Spanish (Spain) text

---

## How to Use These Skills

### With Claude (via Projects)

1. Create a new Project in Claude
2. Add the skill markdown file to your project knowledge
3. Claude will automatically reference the skill when relevant to your questions

### With Claude Code

1. Clone or download the skill folder to `~/.claude/skills/`
2. Claude Code will detect and use the skill automatically
3. For multi-file skills (like the writing skills), add the entire folder

### With ChatGPT (via Custom GPTs)

1. Create a Custom GPT or edit an existing one
2. Upload the skill markdown file(s) to the GPT's knowledge base
3. The GPT will use the skill content to provide specialized assistance

### With Other AI Assistants

Most modern AI assistants support knowledge documents:

1. Look for "Knowledge Base", "Custom Instructions", or "Files" features
2. Upload the skill markdown files
3. Reference the skill in your prompts if needed

### As Human Reference

All skills are written in clear markdown and serve as excellent reference documentation for developers and writers. You can:

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
- **Functions/APIs**: Detailed reference tables (for development skills)
- **Code examples**: Real-world, copy-paste ready code (for development skills)
- **Best practices**: Do's and don'ts
- **Checklists**: Review and validation lists
- **References**: Official documentation links

Some skills use a multi-file structure with a main `SKILL.md` and additional reference files in a `references/` directory, following the agentskills.io progressive disclosure pattern.

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
- Wikipedia's "Signs of AI writing" guide (WikiProject AI Cleanup)
- Community projects: [stop-slop](https://github.com/hardikpandya/stop-slop), [humanizer](https://github.com/blader/humanizer)
- Real-world professional development experience
- Community feedback and contributions

---

**Star this repo** if you find these skills useful! It helps others discover them.
