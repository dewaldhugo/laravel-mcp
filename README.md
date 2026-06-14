# Laravel Model Context Protocol (MCP) Server Core


An enterprise-grade, zero-network-bloat local Model Context Protocol (MCP) Server running natively over standard input/output (`stdio`) streams inside Laravel. This package exposes your application's routing landscape, database structural schemas, and safe diagnostic metrics directly to local LLM-powered IDE tools (like Cursor, Claude Desktop, or Windsurf) with zero HTTP or network footprint.

For comprehensive deep-dives into Laravel-driven AI engineering patterns, architecture, and production practices, check out **[Origin Main](https://origin-main.com/)**.

## ⚡ Key Architectural Features

* **Stream Isolation Guarantee:** Automatically forces Laravel kernel outputs to silence (`VERBOSITY_QUIET`), safely routing any background exceptions or debug traces directly to `STDERR`. This guarantees that `STDOUT` remains unpolluted for exact JSON-RPC 2.0 framing.
* **Strict Security Boundaries:** Implements an unbreachable whitelist model for console actions, preventing unauthorized tool execution or malicious shell argument injection patterns.
* **Scale-Optimized Memory Layer:** Built-in O(1) memory memoization structures optimize route and schema processing inside large-scale codebases containing thousands of endpoints.
* **Native Compliance:** Target-engineered from the ground up matching the core Model Context Protocol Specification.

---

## 📦 Installation & Setup

### 1. Requirements
* PHP ^8.2
* Laravel 10.x or 11.x

### 2. Install the Package via Composer
```bash
composer require dewaldhugo/laravel-mcp --dev
```

*The Service Provider automatically wires itself up using Laravel's native package autodiscovery mechanics.*

---

## 🛠️ Provided LLM Context Tools

Once linked, the package exposes the following capabilities directly into your AI context window:

| Tool Identifier | Input Arguments | Functional Description |
| :--- | :--- | :--- |
| `list_routes` | *None* | Compiles an optimized map of URIs, HTTP verbs, Actions, names, and applied middlewares. |
| `read_model_schema` | `model` *(string)* | Uses runtime Reflection to extract database data types, nullability, and Eloquent relationships. |
| `run_safe_artisan` | `command` *(string)* | Executes safe read-only operations (`about`, `route:list`, `config:show`, `model:show`). |

---

## 🔌 Connecting to Local AI Clients

To attach this server framework to your IDE environment, register the Artisan command within your client configuration block.

### Claude Desktop Configuration
Add the following snippet to your `claude_desktop_config.json` file:

```json
{
  "mcpServers": {
    "laravel-mcp": {
      "command": "php",
      "args": [
        "/path/to/your/laravel-app/artisan",
        "mcp:serve"
      ]
    }
  }
}
```

### Cursor / Windsurf Integration
1. Navigate to your IDE's advanced settings window (**Features > MCP**).
2. Click **+ Add New MCP Server**.
3. Set the Type to `stdio`.
4. Define the command payload array target:
```bash
   php /path/to/your/laravel-app/artisan mcp:serve
   ```

---

## 🛡️ Security Posture

This package enforces a zero-trust model by default. The `run_safe_artisan` tool contains an immutable execution boundary whitelist:
```php
private const WHITELIST = [
    'about',
    'route:list',
    'config:show',
    'model:show',
];
```
Any execution request pointing to destructive commands (e.g., `migrate:fresh`, `db:seed`, or custom application commands) is instantly dropped before reaching the Laravel command bus.

---

## 📜 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.