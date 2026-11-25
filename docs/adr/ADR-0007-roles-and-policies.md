# ADR-0007: Many-to-many roles with a policy layer, secondary level gated by admin

- **Status:** Accepted (agent decision, pending owner review)
- **Date:** 2026-11-25
- **Deciders:** Architecture owner; security reviewer; product reviewer
- **Related:** REQ-IAM-*, REQ-ADM-006; AMB-IAM-001; ADR-0002; `../architecture/security-architecture.md` §3

## Context

پیوست ۳ defines five independent user levels — store (فروشگاه), marketer (بازاریاب), consumer (مصرف‌کننده), supplier (تأمین‌کننده), admin (ادمین) — each with its own capabilities. پیوست ۷ بند ۶ then says:

> «هر سطح کاربری می‌تواند یک سطح ثانویه داشته باشد (مثلاً سطح فروشگاه بتواند بازاریاب باشد — مطابق تنظیم ادمین)»

and پیوست ۱۲ بند ۱۱ requires someone to be a marketer and a consumer at the same time. So a single `role` column is wrong from the first day: it would force duplicate accounts, which then duplicate orders, points and wallets.

The contract also demands role-based access control and input validation as security controls (پیوست ۹ بند ۳) and restricts marketers to their own network and stores to their own catalogue/orders (پیوست ۵ بند ۳۰; canonical-context §5).

## Decision

1. **Roles are rows in `iam_roles`, joined many-to-many** to `iam_users` through `iam_role_user`. The five contractual roles are seeded; additional roles are possible without a schema change (which also serves پیوست ۱ بند ۸'s extensibility requirement).
2. **Permissions are fine-grained strings** (`orders.suborder.ship`, `finance.settlement.approve`, `geo.track.view`) attached to roles via `iam_permission_role`. Code checks permissions, not role names, except where the contract names a role explicitly (2FA for admin).
3. **Policies are the enforcement point** for entity access, and **scopes** narrow queries so a permitted user cannot list what they may not see. Both are mandatory: a policy that returns true without a scope check is a data leak waiting to happen (a store that can view its own orders still needs `where seller_id = ?`).
4. **A secondary role is a grant, not a toggle.** `iam_role_grants(user_id, role_id, granted_by, approved_at, status)` must be `approved` by an admin before the second role confers anything. Without the grant, the role row exists but is inert. This is the contract's "مطابق تنظیم ادمین".
5. **One active role at a time, chosen by the user.** When a user holds two roles, the session carries an `active_role_id`; a validated switcher (`/switch-role`) changes it, and every price resolution, dashboard and scope uses the active role. Default on login is the highest-privilege granted role. This is what makes "store + marketer" coherent without merging two price lists into one screen.
6. **No super-admin bypass.** An `admin` role holds explicit permissions; there is no `if ($user->isAdmin()) return true;` anywhere. Money-moving permissions require step-up 2FA regardless of role.
7. **Role changes are audited** (`RoleAssigned`, `RoleRevoked`, `SecondaryRoleApproved`) with actor, target, before/after and reason, and they revoke the target's API tokens so a downgrade takes effect immediately.
8. **Negative authorization tests are mandatory** for every scoped resource: a different principal must get 403/404, and a token without the ability must be rejected even when the user has the role.

## Consequences

**Positive**

- One account can be a store and a marketer (and a consumer) without duplicating orders, points, wallets or identity documents — which is what the contract actually describes.
- Adding a level later (a "logistics partner", say) is a role row plus permissions, not a schema migration or a new guard.
- Policies plus scopes give defence in depth: even a missing route middleware is caught by the query scope.
- Permissions per action make the admin's role editor meaningful and make audits readable ("who could approve settlements in March").

**Negative**

- More joins and more code than `$user->role === 'admin'`: every query needs a scope, every controller needs a policy, every policy needs a test.
- Authorization bugs become *possible* in a way that a single role column is not — the complexity is the risk. Mitigated by mandatory negative tests, a small number of well-named permissions, and review discipline.
- The active-role concept adds a state dimension: a user can be "store" in one tab and "marketer" in another. Sessions are per-browser, so this is acceptable, but it must be explained in the UI.
- Permission caching (per user, invalidated on role/permission change) is an extra failure mode: a stale cache grants access that was revoked 30 seconds ago. Mitigated by cache-key versioning on `iam_users.permissions_version`.

**Neutral**

- `spatie/laravel-permission` would provide the tables and helpers, but it also brings a second authorization model (its own gates and middleware) alongside policies. We keep the tables simple and the enforcement ours.
- Role names are contractual, so they are seeded, not invented — but the code never branches on them.

## Alternatives considered

| Alternative | Why it lost |
|---|---|
| Single `role` enum column | Cannot express the contract's secondary level; forces duplicate accounts; a migration every time a level is added |
| `spatie/laravel-permission` | Mature and close, but its role/permission checks live beside policies rather than inside them; two sources of truth for "can this user ship this sub-order" |
| Separate user tables per role (`stores`, `marketers`, `consumers`) | Guarantees duplication of identity, wallet, points and messaging; contradicts "one user, several capabilities" |
| Policy-only (no permission strings) | Policies answer "may this actor act on this entity", not "may this actor use this feature at all"; the admin role editor needs the latter |
| ABAC with attribute rules | More expressive than needed; hard to explain to the client's admin and hard to audit |
| JWT claims carrying roles | Stateless but stale: a revoked role would remain valid until expiry, which is unacceptable for settlement permissions |

## References

- Contract page 5–6 / پیوست ۳ (five levels and their capabilities), page 9 / پیوست ۷ بند ۶ (secondary level), page 10 / پیوست ۹ بند ۳ (RBAC), page 8 / پیوست ۵ بند ۲۹–۳۰ (scoped marketer access).
- `docs/canonical-context.md` §1 (roles and secondary role), §5 (policies + scopes, never UI-only authorization).
- `../architecture/security-architecture.md` §3; `../architecture/domain-boundaries.md` §3.1.
