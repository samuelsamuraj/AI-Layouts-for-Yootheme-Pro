=== AI Layout for YOOtheme ===
Contributors: samuraj
Tags: yootheme, uikit, ai, layout, wireframe
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 0.1.0
License: MIT

Generate -> Review -> Compile YOOtheme JSON inside WordPress with feedback loops.

== How to use ==
1. Install this plugin as a standard WP plugin (zip provided).
2. Go to Admin → AI Layout.
3. (Optional) Enter your OpenAI API key and model; otherwise stub heuristics are used.
4. Enter URL or raw text → click “Generér”.
5. Review Analysis + Wireframe (DSL), then download the compiled YOOtheme JSON.

== Endpoints ==
- POST /wp-json/ai-layout/v1/generate
- POST /wp-json/ai-layout/v1/compile
- POST /wp-json/ai-layout/v1/download

== Schemas ==
- /schema/analysis.schema.json
- /schema/wireframe.schema.json
- /schema/layout.schema.json

== Mapping ==
- /mapping/dsl_to_yootheme.json

== Notes ==
- The generator currently stubs Unsplash images; replace with server-side calls to Unsplash/Pexels if desired.
- To auto-save to YOOtheme “My Layouts”, hook into their storage or keep using the Download JSON flow.

== 0.2.0 ==
- OpenAI integration (Responses API)
- Unsplash/Pexels lookup for images
- Regenerate-unlocked feedback loop
- Apply to current page (best-effort)
- Save to Library (plugin-side)
