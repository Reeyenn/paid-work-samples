# Workroom: React dashboard sample

Created September 10, 2026 by an OpenAI coding assistant acting for Reeyen Patel. This is an original portfolio demonstration, not a client delivery or evidence of previous employment. All project names, people, work items, and dates are fictional. It does not use Ignisync Lab's unavailable Figma designs.

## What it demonstrates

- React 19 and TypeScript with a Next-compatible Vinext runtime.
- Reusable status labels and task details, with typed fixture data and a pure search/filter function.
- Accessible Radix-backed tabs and modal sheet through bundled Shadcn components. Keyboard focus management is provided by those primitives.
- Responsive CSS layouts, system/manual light and dark themes, reduced-motion support.
- Search by title, project, owner, or ID; status filters; per-item state updates; clear empty state; reset.
- No backend or real customer records. Record changes are in memory and disappear on reload. Only a theme preference is stored locally.

## Run

Node >=22.13.0. `npm run install:ci`, then `npm run dev`. `npm run build` creates the deployable application. This uses Vinext's Next-compatible app-router APIs; it is not claimed to have been tested with the Next.js production server.

## Validation scope

See VALIDATION.md for actual checks and remaining gaps. A build is not a substitute for cross-browser or assistive-technology testing.

The optional WebMCP tool filters the same visible queue. It feature-detects browser support, validates inputs, and unregisters on unmount. Browsers without support retain the normal UI.

Source includes bundled starter code and dependencies under their respective licenses. Original application code is available under the repository's MIT license. No hosted service credentials are included.
