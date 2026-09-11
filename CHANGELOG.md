# Changelog

## 1.0.0

Initial public release.

### Tool server

- Authenticated HTTP tool server under `/chatbot/v1` for an AI chatbot backend: Bearer secret plus a required
  `User-Agent: AiChatbot/<version>` gate, a stateless firewall and a JSON error envelope.
- `GET /chatbot/v1/tools` — tool definitions with JSON input schemas generated from each tool's arguments DTO; labels
  translated per `Accept-Language`.
- `POST /chatbot/v1/tools/{name}` — executes a tool with `{ arguments, context }`.
- Shipped tools: `get_order_status`, `get_product_availability`.
- Add a tool with one class plus one arguments DTO; the interface is autoconfigured.

### Sources

- `GET /chatbot/v1/sources` and `GET /chatbot/v1/sources/{name}` — keyset-paginated documents (200 per page) with an
  `ids[]` fetch mode (max 500).
- Shipped sources: `products` (per variant, per locale), `categories`, `cms_pages` (Monsieur Biz CMS, when installed),
  `bitbag_cms_pages` (BitBag CMS, when installed).
- `ProductViewFactoryInterface` and `ProductIndexabilityInterface` extension seams, wired as container aliases.
- Catalog change notifications: a `Product`, `ProductTranslation`, `Taxon` or `TaxonTranslation` change is announced to
  the backend on `kernel.terminate` / `ConsoleEvents::TERMINATE`, within a single 5 s wall-clock budget.
- `bin/console fluffydiscord:chatbot:notify-all [--source=] [--locale=] [--channel=]` re-announces the whole catalog in
  500-id batches, honouring `Retry-After` on a 429.

### Widget

- Chat widget embedded into the shop layout: injected automatically on Sylius 2 via the `sylius_shop.base#javascripts`
  twig hook; included on Sylius 1 with `{{ fluffydiscord_chatbot_widget() }}`.
- `widget.enabled`, `site_key`, `cdn_url` and the `channels` gate are all resolved at runtime, so `%env(...)%` values
  work as written.

### Compatibility

- PHP `^8.2`, Sylius `^1.14 || ^2.2 || ^2.3@alpha`, Symfony `^6.4 || ^7.4 || ^8.0`.
- Doctrine ORM `^2.20 || ^3.6 || ^4.0`, doctrine-bundle `^2.13 || ^3.2 || ^4.0`, collections `^1.8 || ^2.1`.
- Ships no entities, mappings or migrations; its only Doctrine surface is a lifecycle listener plus read-only
  `QueryBuilder` use.
