# Native PageSpeed Insights

Issue #188; sitekit-05 under #65/#141. The original Google Site Kit
1.192.0 ZIP (SHA-256 da9c8424b90fc700b09484c20a13aaa3e4ea75edd05a175bf100c6484daee064)
member includes/Modules/PageSpeed_Insights.php matched its pinned
SHA-256 19319acebd09b179ac60fab0aad19d433690e5cbad79435e77faacc564020182.
Inspected get_datapoint_definitions, create_data_request, setup_services:
GET pagespeed, required mobile/desktop strategy, reference site URL
fallback and PageSpeed service runpagespeed. Native skill rea-wp-performance.
No vendor runtime execution or plugin installation.

Google's current public v5 contract:
https://developers.google.com/speed/docs/insights/v5/reference/pagespeedapi/runpagespeed
https://developers.google.com/speed/docs/insights/v5/get-started
Google documents eventual discontinuation of CrUX data within PSI;
missing field data remains explicitly unavailable, never inferred from lab.

## Native contract

The owner-only GET /admin/pagespeed reads a bounded private session cache
and does not call Google. POST /admin/pagespeed/run requires CSRF and an
explicit allowed public page (homepage or About) and mobile/desktop
strategy. The target comes from the configured HTTPS origin, not an
arbitrary user URL or request Host header. The fixed Google HTTPS v5
endpoint receives only this public URL and the selected strategy/category.
No private route, customer information, query string, cookie or arbitrary
provider endpoint is passed. Optional API key uses the existing encrypted
integration record, write-only field and explicit removal workflow.

Bounded curl timeout/body, disabled redirects, matching requested/final
page and device, provider analysis timestamp and numeric performance score
are required for a successful result. Render allowlisted lab metrics;
separate page and origin field percentiles by their verified scope.
Reject origin-fallback as page data. Preserve API's raw CLS percentile
unit explicitly instead of inventing a normalized metric. Missing metrics
remain absent. Fresh, stale, access-denied, quota and unavailable states
are distinct. Cache only normalized results and an origin/key fingerprint
in the owner session; no raw report, key, token or customer data.
Owner action audit is metadata only. No background process or new store.

## Acceptance

tests/pagespeed.test.cjs exercises both strategies, fixed endpoint/target,
cache, wrong page, origin fallback, missing field data, access/quota and
private-route/CSRF denial with synthetic provider and real PHP routes.
tests/browser/pagespeed.cjs exercises the actual owner template at 1440,
375 and 390 px with successful and unavailable fixture results, no overflow
or browser errors. Authenticated production UI and actual Google API data
are separate acceptance from the fixture; do not report fixture metrics as
real measurements.
