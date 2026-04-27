# dp-forum

Categories, topics, posts, reports, moderation audit, and user warnings module for `daems-platform`.

Spec: `daems-platform/docs/superpowers/specs/2026-04-27-modular-architecture-phase1-forum-design.md`

## Layout

```
backend/
├── bindings.php          Production DI bindings (uses SqlForumRepository etc.)
├── bindings.test.php     Test DI bindings (uses InMemory fakes)
├── routes.php            HTTP route registrations
├── migrations/           Schema + data migrations (applied by core runner)
├── src/                  PSR-4 root for DaemsModule\Forum\
└── tests/                PHPUnit tests (Unit/Integration/Isolation)

frontend/
├── public/               Public-facing pages (mounted at /forum/*)
├── backstage/            Admin pages (mounted at /backstage/forum/*)
└── assets/               Per-module CSS/JS/images (mounted at /modules/forum/assets/*)

module.json               Manifest read by core's ModuleRegistry at boot
```

## Loading

Sites consume this module by symlinking or cloning into
`C:\laragon\www\modules\forum\` next to `daems-platform`. The platform's
`ModuleRegistry` scans `modules/*/module.json` at boot — no per-module
config needed in the consuming platform.

## Verification commands (from `daems-platform/`)

```
composer analyse
composer test
composer test:e2e
```

## License

Internal use, daems-platform tenants.
