# PharmaCare Pharmacy Order Tracking System
## Bug Report and Fixes

| Bug ID | Module | Bug / Issue | Fix / Resolution | Status |
|---|---|---|---|---|
| BUG01 | Authentication | User and admin ID fields were not matching the database structure | Updated authentication code to use the correct database ID fields | Fixed |
| BUG02 | Cart | Cart APIs were using incorrect medicine/cart ID references | Corrected database column references in cart APIs | Fixed |
| BUG03 | Profile | Profile API had incorrect user ID references | Updated profile APIs according to the users table | Fixed |
| BUG04 | Orders | Order APIs were using incorrect status and column names | Updated order APIs according to the actual database schema | Fixed |
| BUG05 | Order Items | Order item price field was not matching the database | Updated code to use the price column | Fixed |
| BUG06 | Delivery | Delivery date/status fields were not matching the database | Corrected delivery field and status references | Fixed |
| BUG07 | Payment | Payment date field was incorrect | Updated code to use payment_date | Fixed |
| BUG08 | Order Tracking | Tracking API had incorrect status/field references | Updated tracking API according to the database schema | Fixed |
| BUG09 | Medicine API | Add medicine API had an incorrect bind parameter type | Corrected the parameter binding according to the required data types | Fixed |
| BUG10 | Orders Page | Cancel Order option was not available | Added Cancel Order option for eligible order statuses | Fixed |
| BUG11 | Orders Page | Order progress did not support all backend order statuses | Updated order progress mapping | Fixed |
| BUG12 | Frontend | Frontend product data and backend medicine data were different | Kept frontend demo data separate from backend database data | Resolved |
| BUG13 | Checkout | Checkout was storing order information in localStorage | Current frontend checkout flow stores order information locally | Resolved |