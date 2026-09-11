# FluffyDiscord Sylius Chatbot Bundle

Exposes a small authenticated HTTP tool server (`/chatbot/v1`) for an AI chatbot backend and embeds the chat widget into the Sylius shop layout.

Requires PHP `^8.2` and Sylius `^1.14 || ^2.2 || ^2.3`.

## Requirements

| Package | Constraint |
|---|---|
| `php` | `^8.2` |
| `sylius/sylius` | `^1.14 \|\| ^2.2 \|\| ^2.3@alpha` |
| `doctrine/orm` | `^2.20 \|\| ^3.6 \|\| ^4.0` |
| `doctrine/doctrine-bundle` | `^2.13 \|\| ^3.2 \|\| ^4.0` |
| `doctrine/collections` | `^1.8 \|\| ^2.1` |
| `symfony/*` (framework packages) | `^6.4 \|\| ^7.4 \|\| ^8.0` |
| `symfony/deprecation-contracts`, `symfony/service-contracts` | `^2.5 \|\| ^3.0` — contracts version independently of the framework |
| `twig/twig` | `^3.0` |

Sylius 1.14 is fully supported. The only difference: `sylius_twig_hooks` does not exist there, so the widget is not
injected automatically — the shop includes it with one line (see [Installation → 6](#6-sylius-114-only--include-the-widget)).
Tool server, data sources, catalog notifier and `bin/console fluffydiscord:chatbot:notify-all` work the same on both majors.

The `@alpha` on `sylius/sylius` is only because 2.3 has no stable tag yet (`v2.3.0-ALPHA.1`); it drops once 2.3 ships stable.

## Installation

### 1. Composer

```bash
composer require fluffydiscord/sylius-chatbot-bundle
```

### 2. Register the bundle

`config/bundles.php`:

```php
FluffyDiscord\SyliusChatbotBundle\FluffyDiscordSyliusChatbotBundle::class => ['all' => true],
```

### 3. Routes

`config/routes/fluffydiscord_sylius_chatbot.yaml`:

```yaml
fluffydiscord_sylius_chatbot:
    resource: '@FluffyDiscordSyliusChatbotBundle/config/routes.php'
```

The endpoints live under `/chatbot/v1` on the shop host. Import them with **no `prefix:`** and not behind the shop's
`_locale` prefix: the User-Agent gate and the JSON error envelope both match the literal `/chatbot/v1` path, so a prefixed
import silently disables both — the routes answer, but unguarded and with the shop's HTML error pages.

### 4. Bundle configuration

`config/packages/fluffydiscord_sylius_chatbot.yaml`:

```yaml
fluffy_discord_sylius_chatbot:
    api_secret: '%env(CHATBOT_API_SECRET)%'
    backend_url: '%env(CHATBOT_BACKEND_URL)%'
    ingest_secret: '%env(CHATBOT_INGEST_SECRET)%'
    widget:
        enabled: true
        site_key: '%env(CHATBOT_SITE_KEY)%'
        cdn_url: '%env(CHATBOT_WIDGET_CDN_URL)%'
        channels: []   # channel codes the widget renders on; empty = all of them
```

`backend_url` is used by the `backend-url` attribute (the widget's API origin) and the catalog change notifier. `widget.backend_url` still works as a deprecated alias and is used when the root value is empty.

`widget.cdn_url` is the URL the `<script src>` loads `chat.js` from. Set it to the CDN URL the backend publishes the script to; only the script bytes move to the CDN, every API call still goes to `backend_url`. When empty it falls back to `{backend_url}/widget/v1/chat.js` (backend-served).

`widget.channels` lists the channel codes the widget renders on; `[]` means every channel — and every one of them then
shares the single `widget.site_key`, so they all talk to the same backend tenant.

`widget.enabled`, the site key, the CDN url and the channel gate are all evaluated at **runtime**, so `%env(...)%` values
(including `%env(bool:...)%`) work as written.

`.env`:

```dotenv
CHATBOT_API_SECRET=change-me
CHATBOT_BACKEND_URL=https://chatbot.example.com
CHATBOT_INGEST_SECRET=change-me
CHATBOT_SITE_KEY=site-key
```

`ingest_secret` is the shop half of the catalog notification credential; the backend receives `Authorization: Bearer <site_key>.<ingest_secret>`. Outside the `dev` environment a non-`https` `backend_url` is refused and nothing is sent.

### 5. Security

Add the `chatbot_api` firewall to `config/packages/security.yaml` **before** the Sylius `shop` firewall (firewalls match in order and `shop` matches `^/`):

```yaml
security:
    providers:
        chatbot_backend:
            memory:
                users: []
    firewalls:
        chatbot_api:
            pattern: ^/chatbot/v1
            stateless: true
            provider: chatbot_backend
            entry_point: FluffyDiscord\SyliusChatbotBundle\Security\ApiAuthenticationFailureHandler
            access_token:
                token_handler: FluffyDiscord\SyliusChatbotBundle\Security\ApiSecretAuthenticator
                failure_handler: FluffyDiscord\SyliusChatbotBundle\Security\ApiAuthenticationFailureHandler
        # shop: ...
    access_control:
        - { path: ^/chatbot/v1, roles: ROLE_CHATBOT_BACKEND }
```

The backend authenticates with `Authorization: Bearer <CHATBOT_API_SECRET>`.

`/chatbot/v1` additionally requires `User-Agent: AiChatbot/<version>` — anything else gets
`403 forbidden_user_agent` before authentication even runs. This is **not configurable**: the bundle and the chatbot
backend ship as one protocol. It is identification, not authorization — a header is trivially forged, so the Bearer
secret remains the security boundary; the gate keeps the tool server attributable to one caller. It is also why a
hand-rolled request gets a 403 — a correct secret is not enough:

```bash
# 403 {"error":{"code":"forbidden_user_agent",…}} — curl's own User-Agent
curl -H "Authorization: Bearer $CHATBOT_API_SECRET" https://shop.example/chatbot/v1/tools

# 200
curl -H "Authorization: Bearer $CHATBOT_API_SECRET" -A 'AiChatbot/1.0' https://shop.example/chatbot/v1/tools
```

### 6. Sylius 1.14 only — include the widget

Sylius 1 has no twig hooks, so add the widget yourself, once, inside the layout's `javascripts` block:

```twig
{{ fluffydiscord_chatbot_widget() }}
```

Everything else — whether it renders at all, the script url, the channel gate, the markup — stays in the bundle. The
function is **deprecated on purpose**: on Sylius 2 the widget is injected automatically, and the bundle emits one
deprecation per container build to say so. When you upgrade to Sylius 2, delete this line; leaving it in is harmless
(the widget renders at most once per request) but pointless.

## What the bundle exposes

Three independent channels — do not conflate them:

- **Tools** — live, per-request calls the backend makes *during a conversation* (order status, stock/price right now).
- **Sources** — bulk catalog/content documents the backend *pulls and ingests* into its own retrieval index; never called per chat.
- **Widget** — the chat UI, embedded into the shop layout.

Tools and sources are HTTP endpoints under `/chatbot/v1` on the shop host. Both use the error envelope
`{ "error": { "code", "message", "violations" } }`; `violations` is non-empty only for HTTP 422 (`validation_failed`).

## Tools

The backend calls these *while answering a shopper*: the model decides it needs live data, the backend makes a
server-to-server request to the shop mid-turn, and feeds the result back into the same conversation. Tools answer
"what is **true right now**".

| Method | Path | Description |
|---|---|---|
| GET | `/chatbot/v1/tools` | Tool definitions with generated JSON input schemas; labels translated per `Accept-Language` |
| POST | `/chatbot/v1/tools/{name}` | Executes a tool with `{ arguments, context: { conversationId, locale, channelCode } }` |

**Shipped tools**

- `get_order_status` — order state, payment and shipping state, tracking codes, item count and total; requires the order number and the customer e-mail (uniform "not found" otherwise).
- `get_product_availability` — price, currency and stock for up to 20 variant codes; returns a `products` block for the widget.

**Adding a tool** — one class plus one arguments DTO, no configuration:

```php
readonly class MyArguments
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 64)]
        public string $query = '',
    ) {
    }
}

readonly class MyTool implements ChatbotToolInterface
{
    public function getDefinition(): ToolDefinition
    {
        return new ToolDefinition('my_tool', 'app.chatbot.my_tool.description');
    }

    public function getArgumentsClass(): string
    {
        return MyArguments::class;
    }

    public function execute(object $arguments, ToolCallContext $context): ToolResult
    {
        return new ToolResult([new ContentItem('...')]);
    }
}
```

The interface is autoconfigured; the input schema is generated from the DTO (`Assert\NotBlank` → required, `Assert\Email` → e-mail format, `Assert\Choice` → enum, `Assert\Length` → maxLength, nullable → optional). Descriptions and labels are translation keys resolved with the request locale. `getDefinition()` must not rely on constructor arguments — it is invoked at container compile time to index tools by name (duplicate names fail the container build).

## Sources

Sources are **not** called per chat. The backend pulls them in bulk, chunks and embeds the documents, and stores the
vectors in its own retrieval index; at chat time it searches that index instead of calling the shop. Sources answer
"what the shop **is**" (catalog + content), the counterpart to tools' "what is true right now". Re-ingestion is driven
by the catalog-change notifications below (delta) and by the backend's own full-sync schedule.

| Method | Path | Description |
|---|---|---|
| GET | `/chatbot/v1/sources` | Data source definitions with served locales |
| GET | `/chatbot/v1/sources/{name}?locale=cs_CZ&channel=&cursor=&ids[]=` | Keyset-paginated documents (200 per page); with `ids[]` (max 500) the cursor is ignored and `nextCursor` is `null` |

**Shipped sources**

- `products` — one document per indexable product **variant** per locale, keyed by the variant code, with `ProductMetadata` (`code, productCode, name, url, imageUrl, priceMinor, currency, inStock, taxons, attributes, options`); `taxons` carries taxon **codes**, the taxon names and the main taxon path live in the document text.
- `categories` — enabled taxons of the channel tree per locale (`code, name, path, url, productCount`). `productCount` counts the taxon's whole nested-set subtree and only products passing `ProductIndexabilityInterface`, so it agrees with the category page (`include_all_descendants: true`) and with what the chatbot can return.
- `cms_pages` — enabled Monsieur Biz CMS pages per locale (registered only when `monsieurbiz/sylius-cms-page-plugin` is installed).
- `bitbag_cms_pages` — enabled BitBag CMS pages of the channel per locale (registered only when `bitbag/cms-plugin` is installed); filtered by `enabled` + channel + locale, `publishAt`/`unpublishAt` are deliberately not applied, matching the plugin's own page-show route.

**Adding a data source** — implement `ChatbotDataSourceInterface` the same way a tool is added; `SourceDefinition::$locales = null` means "all locales of the current channel".

**Extension points** (both govern the `products` source):

| Interface | Default | Purpose |
|---|---|---|
| `ProductViewFactoryInterface` | `ProductViewFactory` | price, stock, image and URL of an indexed product |
| `ProductIndexabilityInterface` | `ProductIndexability` | which products the `products` source serves (default: enabled, in the channel, at least one priced enabled variant) |

Both are wired as container aliases; redefine the alias in the shop's `services.yaml` to replace them.

### Catalog change notifications

These keep the backend's ingested sources fresh. Saving a `Product`, a `ProductTranslation`, a `Taxon` or a `TaxonTranslation` collects the changed external ids and one `POST {backend_url}/api/v1/catalog/changes` per `(source, locale)` is sent on `kernel.terminate` and on `ConsoleEvents::TERMINATE` (max 500 ids per request, 2 s timeout, 5 s max duration). The whole flush shares a single 5 s wall-clock budget: the requests are issued together and drained in one `stream()` loop, and whatever has not finished when the budget is spent is abandoned — a dropped notification costs at most one nightly cycle of staleness.

A `ProductTranslation` change announces its own locale only, a `TaxonTranslation` change announces `categories` for its own locale, a `Product` change announces every locale of `sylius_locale`, and a `Taxon` change announces `categories` for every locale without fanning out to its products. Every failure is logged as a warning and swallowed — a notification never breaks a shop request. With `backend_url`, `ingest_secret` or `widget.site_key` empty nothing is sent and a warning names the missing key.

`bin/console fluffydiscord:chatbot:notify-all [--source=products|categories] [--locale=cs_CZ] [--channel=code]` re-announces the whole catalog in 500-id batches, pausing 2 s between batches and honouring `Retry-After` on a 429. Pass `--channel` when no channel can be resolved from the CLI context; without `--locale` the locales are taken from the resolved channel, so each channel's catalog is paired with the locales that channel actually serves.

## Widget

On Sylius 2 the bundle injects this through the `sylius_shop.base#javascripts` twig hook; on Sylius 1 the shop calls
`{{ fluffydiscord_chatbot_widget() }}` itself (Installation → 6). Both render the same markup, at most once per request:

```html
<script src="{widget.cdn_url}" defer></script>
<ai-chat-widget site-key="{site_key}" locale="{app.locale}" backend-url="{backend_url}"></ai-chat-widget>
```

Nothing renders when `widget.enabled` is false, when the site key or `backend_url` is empty, when no channel can be
resolved, or when the current channel is outside `widget.channels` — silently, in every case. A channel code that matches
nothing (typo, renamed channel) therefore looks exactly like "the widget disappeared"; check
`bin/console debug:container --parameters | grep chatbot` first. `widget.cdn_url` defaults to
`{backend_url}/widget/v1/chat.js` when unset. The widget talks only to the backend at `backend_url`; it never calls `/chatbot/v1` itself. Tool calls and source ingestion are the backend's job — the widget only renders what the backend streams back.
