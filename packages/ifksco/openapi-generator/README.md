
# Open Api scheme generator
Generator json scheme based on Laravel Requests and Resources 

## Installation

publish config:

```bash
php artisan vendor:publish --tag=ifksco-openapi-generator
```
    

## Resource properties

The scheme of a response is built from the private typed properties of the resource.

- A property typed with a union of resources (`OtaResource|DirectResource`) becomes `oneOf` of their schemes;
  `null` in the union only drops the property from `required`: `nullable` is not rendered, as for any nested
  nullable resource.
- A `stdClass` property becomes an empty object (`type: object`, `maxProperties: 0`): it renders as `{}`, while an
  empty resource would render as `[]`.

## Request bodies from rules

`OaRulesParser::makeSchemeByRules()` builds the scheme of a request body from validation rules.

- Rules with the root `*` (`*`, `*.date_utc`, `*.event.id`) describe a list body: the scheme becomes `type: array`
  with the item from the rules after `*.`. Named root fields can not be mixed with them.
- `PathObject::$requestExample` goes to the example of the body (`requestBody.content.<type>.example`).

## SLogger integration

`config/oa-generator.php` registers the admin API source. `oa:generate private` and
`oa:generate public` select configured disk aliases; `make oa-generate` runs both and
regenerates the frontend client. The Go receiver is not a Laravel schema source.

The generator has no application-specific collection imports or static configuration/extension
caches. It preserves SLogger's `OaListItemTypeAttribute` on streamed list endpoints,
as well as Laravel's resource wrapping. `present` and nested `required` rules now
produce required fields matching Laravel validation. Response status attributes,
resource unions, empty objects and root list bodies are supported by the parser.
