# API reference
Base URL `http://localhost:8000/api`. Send `Accept: application/json`. Protected routes need
`Authorization: Bearer <token>`. JSON is camelCase and unwrapped (matches the React types).

Errors: `401 {code:"unauthenticated"}`, `403 {code:"forbidden"|"hotel_suspended"}`, `404 {code:"not_found"}`
(also for another hotel's record), `422 {message, errors:{field:[...]}}` for validation.

## Public
| Method | URL | Body → Result |
|---|---|---|
| POST | /auth/login | `{email,password}` → `{token,user}` (10/min limit) |

## Any logged-in user
| GET | /auth/me | user |
| POST | /auth/logout | 204 |
| GET | /plans | Plan[] |

## Super Admin (`role:super_admin`)
| GET/POST | /hotels | list / create hotel + owner login (needs `ownerPassword`) |
| GET/PATCH/DELETE | /hotels/{id} | PATCH `{status:"suspended"}` suspends. DELETE removes the hotel and ALL its data, only allowed while suspended |
| PATCH | /hotels/{id}/owner | change the owner's `ownerName` / `ownerEmail` (login e-mail) |
| POST | /hotels/{id}/owner/reset-password | new temporary password, returned once `{temporaryPassword}`; owner is logged out |
| GET | /users | all users |
| GET | /platform/stats, /platform/activity | dashboard data |
| POST | /plans | create a plan `{name, priceMonthly, maxUsers}`; the id is made from the name |
| PATCH/DELETE | /plans/{id} | edit a plan / delete it (422 while a hotel uses it or it is the default plan) |
| GET/PATCH | /platform/settings | `platformName, supportEmail, supportPhone, defaultCurrency, defaultPlanId, trialDays` |

## Hotel Owner (`role:hotel_owner`, hotel active) — all rows limited to the owner's hotel
| Resource | URLs | Notes |
|---|---|---|
| Dashboard | GET /dashboard | DashboardService (partly TODO) |
| Own hotel | GET/PATCH /my-hotel | settings |
| Bookings | apiResource /bookings; POST /bookings/{id}/payments, /cancel | filters `status,eventType,shift,hallName,eventDate`; 422 if hall taken |
| Requisition | GET /bookings/{id}/requisition; POST …/requisition/issue | issue is all-or-nothing |
| Halls, Menus | apiResource /halls, /menus | menu = `{name, pricePerPerson, ingredients:[{inventoryItemId, perPerson}]}`, at least 1 ingredient; DELETE refused (422) while a booking uses the menu |
| Inventory | apiResource /inventory-items | quantity only via stock endpoints |
| Stock | GET /stock-movements; POST /stock-movements/in, /out, /spoilage | 422 when stock too low |
| Employees | apiResource /employees | filters `status,department` |
| Salaries | apiResource /salary-payments | unique per employee+month; filters `month,employeeId,status` |
| Partners | apiResource /partners; POST /partners/{id}/transactions | withdrawal ≠ expense |
| Loans | apiResource /loans; POST /loans/{id}/repayments | filter `type` |
| Expenses | apiResource /expenses | filter `category` |
| External income | apiResource /external-income | |
| Reports | GET /reports/profit-loss | 501 stub |

`apiResource` = GET list, POST create, GET one, PUT/PATCH update, DELETE.
