# dsgourakis.com

[![CI](https://github.com/dsgourakis/dsgourakis-portfolio/actions/workflows/ci.yml/badge.svg)](https://github.com/dsgourakis/dsgourakis-portfolio/actions/workflows/ci.yml)

This is the source code for my portfolio site, [dsgourakis.com](https://dsgourakis.com).

I'm a WordPress developer focused on accessibility, WooCommerce and performance. I rebuilt my site from scratch as a modern WordPress project: a block theme, a custom plugin with React blocks, automated tests, and a pipeline that deploys on every merge. It's the same way I'd set up a client project.

## What's inside

- **Block theme** (`themes/dsg-theme`): Full Site Editing with `theme.json`, HTML templates, patterns, and a dark style variation. No page builder, no jQuery.
- **Plugin** (`plugins/dsg-core`): a `project` post type, custom Gutenberg blocks written in React, and namespaced PHP loaded through Composer's autoloader.
- **Quality checks**: WordPress Coding Standards (PHPCS), ESLint, Stylelint, PHPUnit and Jest. All of them run on every push.
- **Deployment**: GitHub Actions deploys `develop` to staging and `main` to production. Production only goes out after the checks pass and I approve it.

## Running it locally

You'll need **Docker Desktop** (running) and **Node.js 22**.

```bash
npm install
npm run build
npm run env:start
```

Then open http://localhost:8888. The admin is at `/wp-admin`, with wp-env's default local login `admin` / `password`.

The plugin needs Composer's autoloader. If you have PHP and Composer installed, run `composer install`. If you don't, run it inside the Docker container:

```bash
npx wp-env run cli --env-cwd=wp-content/dsg-repo composer install
```

### Working on the blocks

Use two terminals:

1. `npm run env:start`, which finishes and hands the terminal back while the site keeps running.
2. `npm run start`, which you leave open while you edit blocks. Stop it with Ctrl+C.

### Useful commands

| Command | What it does |
|---|---|
| `npm start` | Rebuilds the blocks as you edit them |
| `npm run lint` | Runs every linter (JS, CSS, PHP) |
| `npm run test:js` | Jest tests |
| `npm run test:php` | PHPUnit tests (inside Docker) |
| `npm run env:stop` | Stops the local site |

## How I work on it

New work happens on a feature branch, which gets a pull request into `develop`. Merging the PR deploys it to staging. When staging looks right, `develop` is merged into `main` and goes live.

Code moves up through Git. Content (the database and uploads) only ever moves down, from production to staging to local.

## Roadmap

- [x] Tooling, CI and the deploy pipeline
- [x] Theme foundations, `project` post type, `project-card` block
- [x] `tech-stack`, `project-grid` and `metrics` blocks
- [x] Contact form backed by a REST endpoint
- [x] Light/dark toggle that respects the system setting (dark as fallback, choice remembered)
- [x] Accessibility statement and automated a11y checks in CI
- [x] Lighthouse 95+ across the board

## License

GPL-2.0-or-later
