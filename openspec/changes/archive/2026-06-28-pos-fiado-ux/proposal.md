# Proposal: POS Fiado UX Improvements

## Intent

The POS sidebar has fragmented UX: only the fiado toggle reflects its active state visually, panels lose data when toggled, fiado auto-activates from received-amount logic without user consent, and the customer `<select>` doesn't scale beyond ~100 clients. These issues cause confusion during checkout and don't scale for growing customer bases.

## Scope

### In Scope
- Consistent visual state for all four sidebar toggle buttons (Alpine.js reactive binding)
- Panel data persistence across open/close cycles (no data loss on toggle)
- Configurable fiado auto-activation (on by default, toggle in app settings)
- Typeahead client selector with debounced server-side search (~300ms)
- Smart accordion sidebar layout (one panel open by default, pin capability)

### Out of Scope
- Livewire component decomposition of the full POS (deferred)
- Left-column product search/line management changes
- Backend validation changes (already correct)
- Mobile/responsive sidebar redesign

## Capabilities

### New Capabilities
- `pos-sidebar-state`: Alpine.js reactive state for button consistency, panel data persistence, and accordion layout with pin support
- `fiado-auto-config`: Configurable fiado auto-activation setting (default on, user-toggleable)
- `client-typeahead`: Debounced server-side search client selector replacing the current `<select>`

### Modified Capabilities
None (no existing specs).

## Approach

Hybrid Alpine.js migration (exploration Approach 4): wrap the sidebar `<aside>` in an `x-data` component managing `creditActive`, `receivedAmount`, `customerSelected`, `paymentMethod`, `activePanel`, and `pinnedPanels[]`. Button classes bind reactively via `x-bind:class`. Panel visibility decouples from feature state — closing a panel preserves its data. Fiado auto-activation reads a user setting before triggering. Client selector uses a debounced fetch to a new `PosController@searchCustomers` endpoint. Accordion logic: clicking a panel header closes others unless pinned.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `backend/resources/views/pos/index.blade.php` | Modified | Alpine.js sidebar component, button bindings, accordion layout, typeahead UI |
| `backend/app/Http/Controllers/PosController.php` | Modified | Add `searchCustomers` endpoint for typeahead |
| `backend/resources/views/pos/partials/` | New | Extract sidebar panels into partials for maintainability |
| `backend/routes/web.php` | Modified | Add customer search route |
| App settings (TBD) | New | Fiado auto-activation toggle storage |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Alpine + vanilla JS coexistence breaks `refreshSummary()` | Medium | Keep Alpine state as source of truth; sync to hidden inputs on change |
| Typeahead UX feels sluggish on slow networks | Low | 300ms debounce + loading indicator; fallback to cached results |
| Accordion pin adds interaction complexity | Low | Pin is opt-in; default behavior is simple single-open |
| Total diff exceeds 400-line review budget | High | Split into chained PRs: (1) Alpine state + buttons, (2) typeahead, (3) accordion + pin |

## Rollback Plan

Each PR is independently revertable. Alpine sidebar changes are additive (existing hidden inputs remain). Typeahead adds a new endpoint — removing it reverts to `<select>`. Accordion is CSS/JS-only — revert restores current stack layout.

## Dependencies

- Alpine.js (already available via CDN or bundled — verify)
- Customer search endpoint requires `PosController` route addition

## Success Criteria

- [ ] All four toggle buttons reflect active/inactive state visually
- [ ] Closing and reopening a panel preserves entered values
- [ ] Fiado auto-activation respects user setting toggle
- [ ] Client selector handles 100+ clients with <500ms perceived latency
- [ ] Sidebar uses accordion layout; pin allows multiple open panels
- [ ] Existing `PosFlowTest.php` tests continue to pass
