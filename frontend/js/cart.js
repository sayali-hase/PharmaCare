/* =========================================================
   PHARMACARE
   CART JAVASCRIPT
========================================================= */


/* =========================================================
   PAGE LOAD
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    loadCart();

    updateCartCount();


    const searchInput =
        document.getElementById("cartSearch");


    if (searchInput) {

        searchInput.addEventListener(
            "keydown",
            function (event) {

                if (event.key === "Enter") {

                    searchFromCart();

                }

            }
        );

    }

});


/* =========================================================
   GET CART
========================================================= */

function getCartItems() {

    let cart = [];

    try {

        cart =
            JSON.parse(
                localStorage.getItem("pharmacareCart")
            ) || [];

    }

    catch (error) {

        cart = [];

    }

    return cart;

}


/* =========================================================
   SAVE CART
========================================================= */

function saveCartItems(cart) {

    localStorage.setItem(
        "pharmacareCart",
        JSON.stringify(cart)
    );

    updateCartCount();

}


/* =========================================================
   LOAD CART
========================================================= */

function loadCart() {

    const cart = getCartItems();


    const cartItems =
        document.getElementById("cartItems");


    const emptyCart =
        document.getElementById("emptyCart");


    if (!cartItems) {

        return;

    }


    cartItems.innerHTML = "";


    if (cart.length === 0) {

        if (emptyCart) {

            emptyCart.style.display = "block";

        }

        updateSummary([]);

        updateCartCount();

        return;

    }


    if (emptyCart) {

        emptyCart.style.display = "none";

    }


    cart.forEach(function (item, index) {

        const quantity =
            Number(item.quantity) || 1;


        const price =
            Number(item.price) || 0;


        const itemTotal =
            price * quantity;


        const cartItem =
            document.createElement("div");


        cartItem.className =
            "cart-item";


        cartItem.innerHTML = `

            <div class="cart-item-icon">
                ${item.icon || "💊"}
            </div>


            <div class="cart-item-info">

                <h3>
                    ${item.name || "Medicine"}
                </h3>

                <p>
                    ₹${price} per item
                </p>


                <div class="cart-quantity">

                    <button
                        type="button"
                        onclick="decreaseCartQuantity(${index})"
                    >
                        −
                    </button>


                    <span>
                        ${quantity}
                    </span>


                    <button
                        type="button"
                        onclick="increaseCartQuantity(${index})"
                    >
                        +
                    </button>

                </div>

            </div>


            <div class="cart-item-price">

                <strong>
                    ₹${itemTotal}
                </strong>


                <button
                    type="button"
                    class="remove-btn"
                    onclick="removeCartItem(${index})"
                >
                    Remove
                </button>

            </div>

        `;


        cartItems.appendChild(cartItem);

    });


    updateSummary(cart);

    updateCartCount();

}


/* =========================================================
   INCREASE QUANTITY
========================================================= */

function increaseCartQuantity(index) {

    const cart =
        getCartItems();


    if (!cart[index]) {

        return;

    }


    const quantity =
        Number(cart[index].quantity) || 1;


    if (quantity < 10) {

        cart[index].quantity =
            quantity + 1;

    }


    saveCartItems(cart);

    loadCart();

}


/* =========================================================
   DECREASE QUANTITY
========================================================= */

function decreaseCartQuantity(index) {

    const cart =
        getCartItems();


    if (!cart[index]) {

        return;

    }


    const quantity =
        Number(cart[index].quantity) || 1;


    if (quantity > 1) {

        cart[index].quantity =
            quantity - 1;

    }

    else {

        cart.splice(index, 1);

    }


    saveCartItems(cart);

    loadCart();

}


/* =========================================================
   REMOVE ITEM
========================================================= */

function removeCartItem(index) {

    const cart =
        getCartItems();


    if (!cart[index]) {

        return;

    }


    const itemName =
        cart[index].name || "Medicine";


    const confirmRemove =
        confirm(
            "Remove " +
            itemName +
            " from cart?"
        );


    if (!confirmRemove) {

        return;

    }


    cart.splice(index, 1);


    saveCartItems(cart);

    loadCart();

}


/* =========================================================
   UPDATE SUMMARY
========================================================= */

function updateSummary(cart) {

    let subtotal = 0;

    let totalItems = 0;


    cart.forEach(function (item) {

        const price =
            Number(item.price) || 0;


        const quantity =
            Number(item.quantity) || 0;


        subtotal +=
            price * quantity;


        totalItems +=
            quantity;

    });


    /* 10% demo discount above ₹100 */

    let discount = 0;


    if (subtotal >= 100) {

        discount =
            Math.round(
                subtotal * 0.10
            );

    }


    /* Free delivery */

    const delivery = 0;


    const grandTotal =
        subtotal -
        discount +
        delivery;


    const summaryItems =
        document.getElementById(
            "summaryItems"
        );


    const subtotalElement =
        document.getElementById(
            "subtotal"
        );


    const deliveryElement =
        document.getElementById(
            "delivery"
        );


    const discountElement =
        document.getElementById(
            "discount"
        );


    const grandTotalElement =
        document.getElementById(
            "grandTotal"
        );


    if (summaryItems) {

        summaryItems.textContent =
            totalItems;

    }


    if (subtotalElement) {

        subtotalElement.textContent =
            "₹" + subtotal;

    }


    if (deliveryElement) {

        deliveryElement.textContent =
            delivery === 0
                ? "Free"
                : "₹" + delivery;

    }


    if (discountElement) {

        discountElement.textContent =
            "-₹" + discount;

    }


    if (grandTotalElement) {

        grandTotalElement.textContent =
            "₹" + grandTotal;

    }

}


/* =========================================================
   UPDATE CART COUNT
========================================================= */

function updateCartCount() {

    const cartCount =
        document.getElementById("cartCount");


    if (!cartCount) {

        return;

    }


    const cart =
        getCartItems();


    let totalItems = 0;


    cart.forEach(function (item) {

        totalItems +=
            Number(item.quantity) || 0;

    });


    cartCount.textContent =
        totalItems;

}


/* =========================================================
   SEARCH FROM CART
========================================================= */

function searchFromCart() {

    const input =
        document.getElementById("cartSearch");


    if (!input) {

        return;

    }


    const searchText =
        input.value.trim();


    if (searchText === "") {

        alert(
            "Please enter a medicine name."
        );

        return;

    }


    window.location.href =
        "medicines.html?search=" +
        encodeURIComponent(searchText);

}


/* =========================================================
   PROCEED TO CHECKOUT
========================================================= */

function proceedToCheckout() {

    const cart =
        getCartItems();


    if (cart.length === 0) {

        alert(
            "Your cart is empty."
        );

        return;

    }


    window.location.href =
        "checkout.html";

}