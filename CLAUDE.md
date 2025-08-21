# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Testing
- Run all tests: `./vendor/bin/phpunit`
- Run tests with coverage: `./vendor/bin/phpunit --coverage-html=out`
- Run specific test: `./vendor/bin/phpunit tests/Path/To/SpecificTest.php`

### Code Quality
- Run PHP CodeSniffer: `./vendor/bin/phpcs`
- Fix coding standards: `./vendor/bin/phpcbf`
- Code follows PSR-12 standards as defined in `phpcs.xml`

### Composer Commands
- Install dependencies: `composer install`
- Update dependencies: `composer update`
- Run autoload dump: `composer dump-autoload`

## Architecture Overview

### Framework Structure
This is the Forest City Labs Framework, a PSR-compliant PHP framework specifically targeted towards GraphQL APIs.

**Core Components:**
- **Kernel** (`src/Kernel.php`): Central request handler implementing middleware pipeline pattern
- **Middleware Pipeline**: Request processing through configurable middleware stack
- **GraphQL Integration**: Built-in GraphQL support via `GraphQLMiddleware`
- **Routing System**: Attribute-based routing with auto-discovery via `MetadataProvider`
- **Security Layer**: OAuth 2.0 and OpenID Connect implementation
- **Event System**: PSR-14 compliant event dispatcher integration

### Key Architectural Patterns

**Middleware Architecture:**
- Kernel processes requests through a sequential middleware pipeline
- Each middleware can modify request/response or delegate to next handler
- Events dispatched before/after each middleware execution

**Attribute-Based Configuration:**
- Routes defined using `#[Route]` and `#[RoutePrefix]` attributes
- GraphQL types/fields defined using framework-specific attributes
- Security requirements defined using `#[RequiresRole]` and `#[RequiresScope]`

**Dependency Injection:**
- Framework expects PSR-11 container for service resolution
- All core services designed for dependency injection
- Middleware and handlers resolved through container

**PSR Compliance:**
- PSR-3 (Logger Interface)
- PSR-6 (Caching Interface) 
- PSR-7 (HTTP Message Interface)
- PSR-11 (Container Interface)
- PSR-14 (Event Dispatcher)
- PSR-15 (HTTP Server Request Handlers)

### Directory Structure
- `src/`: Framework source code organized by feature
- `tests/`: Comprehensive PHPUnit test suite with fixtures
- `src/GraphQL/`: GraphQL type system and schema management
- `src/Security/`: OAuth/OIDC servers and authentication
- `src/Middleware/`: Request processing middleware
- `src/Routing/`: Route discovery and collection management
- `src/Utility/`: Shared utilities and services

### GraphQL Implementation
- Schema-first approach with automatic type discovery
- Field resolvers support method and property-based resolution
- Built-in transformers for common data types
- Schema comparison and diff utilities for migrations
- Code generation for GraphQL types from schema files

### Security Features  
- OAuth 2.0 authorization server with multiple grant types
- OpenID Connect identity layer with JWT tokens
- Role-based and scope-based access control
- Secure session management with multiple storage drivers
- CSRF protection and encryption services

### Caching Strategy
- PSR-6 compliant caching with multiple backends (Filesystem, Database, Redis)
- Route metadata caching for performance
- Built-in cache management commands

### Testing Infrastructure
- PHPUnit 11.0+ with strict configuration
- Snapshot testing for complex outputs
- Mockery for test doubles
- Comprehensive fixture system for GraphQL and routing tests