# PharmaCare Pharmacy Order Tracking System
## Test Cases

| Test Case ID | Module | Test Case | Expected Result | Status |
|---|---|---|---|---|
| TC01 | Signup | Enter valid user details and create account | User account should be created successfully | Pass |
| TC02 | Signup | Enter already registered email | System should show email already exists message | Pass |
| TC03 | Login | Enter valid email and password | User should login successfully | Pass |
| TC04 | Login | Enter invalid email or password | System should show login error | Pass |
| TC05 | Medicines | Open medicines page | Available medicines should be displayed | Pass |
| TC06 | Medicines | Search for a medicine | Matching medicine should be displayed | Pass |
| TC07 | Product Details | Open a medicine | Product details should be displayed correctly | Pass |
| TC08 | Cart | Add medicine to cart | Medicine should be added to cart | Pass |
| TC09 | Cart | Increase medicine quantity | Cart quantity should be updated | Pass |
| TC10 | Cart | Remove medicine from cart | Medicine should be removed from cart | Pass |
| TC11 | Buy Now | Click Buy Now on a medicine | User should be redirected to checkout | Pass |
| TC12 | Checkout | Enter valid delivery details | Checkout details should be accepted | Pass |
| TC13 | Order | Place an order | Order should be created successfully | Pass |
| TC14 | My Orders | Open My Orders | Previous orders should be displayed | Pass |
| TC15 | Order Tracking | Click Track Order | Order tracking information should be displayed | Pass |
| TC16 | Cancel Order | Cancel an eligible order | Order status should change to Cancelled | Pass |
| TC17 | Profile | Open profile page | User profile information should be displayed | Pass |
| TC18 | Profile | Update profile information | Updated information should be saved | Pass |
| TC19 | Logout | Click Logout | User session should be ended successfully | Pass |
| TC20 | Database | Insert and retrieve medicine data | Correct medicine data should be stored and retrieved | Pass |
| TC21 | Database | Create order with order items | Order and order item records should be stored correctly | Pass |
| TC22 | Database | Update order status | Order status should be updated correctly | Pass |
| TC23 | Responsive UI | Open website on different screen sizes | Layout should remain usable and readable | Pass |