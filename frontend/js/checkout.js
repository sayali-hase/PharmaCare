/* =========================
   CHECKOUT MODE
========================= */

const checkoutParams =
    new URLSearchParams(window.location.search);

const checkoutMode =
    checkoutParams.get("mode");

const hasBuyNowItem =
    localStorage.getItem("buyNowItem") !== null;

const isBuyNowMode =
    (
        checkoutMode &&
        checkoutMode.toLowerCase() === "buynow"
    ) || hasBuyNowItem;


/* =========================
   GET CHECKOUT ITEMS
========================= */

function getCheckoutItems() {

    /* Direct Buy Now */

    if (isBuyNowMode) {

        try {

            const buyNowItem =
                JSON.parse(
                    localStorage.getItem("buyNowItem")
                );

            if (buyNowItem) {

                return [buyNowItem];

            }

        } catch (error) {

            console.error(
                "Buy Now item error:",
                error
            );

            return [];

        }

        return [];

    }


    /* Normal Cart Checkout */

    try {

        const cart =
            JSON.parse(
                localStorage.getItem(
                    "pharmacareCart"
                )
            ) || [];

        return Array.isArray(cart)
            ? cart
            : [];

    } catch (error) {

        console.error(
            "Cart error:",
            error
        );

        return [];

    }

}


/* =========================
   DISCOUNT DATA
========================= */

const checkoutMedicineDiscounts = {

    "Paracetamol 500mg": 10,
    "Vitamin C Tablets": 15,
    "Cetirizine 10mg": 5,
    "Antiseptic Cream": 12,
    "Digestive Tablets": 8,
    "Ibuprofen 400mg": 10,
    "Cough Syrup": 15,
    "Multivitamin Tablets": 20

};


/* ==============================
   CHECK USER ACCOUNT
============================== */

function checkUserAccount() {

    const savedUser =
        localStorage.getItem("pharmaUser");

    if (!savedUser) {

        alert(
            "Please create an account before placing an order."
        );

        window.location.href =
            "signup.html";

        return false;

    }

    return true;

}


/* ==============================
   PAGE LOAD
============================== */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        if (!checkUserAccount()) {
            return;
        }

        loadCheckoutSummary();

        setupPaymentMethods();


        /* SEARCH */

        const searchInput =
            document.getElementById(
                "checkoutSearch"
            );

        if (searchInput) {

            searchInput.addEventListener(
                "keypress",
                function (event) {

                    if (event.key === "Enter") {

                        event.preventDefault();

                        searchFromCheckout();

                    }

                }
            );

        }


        /* CART COUNT */

        if (
            typeof updateCartCount ===
            "function"
        ) {

            updateCartCount();

        }

    }
);


/* ==============================
   GET DISCOUNT
============================== */

function getCheckoutDiscount(name) {

    return checkoutMedicineDiscounts[name] || 0;

}


/* ==============================
   GET NORMAL CART
============================== */

function getCheckoutCart() {

    let cart = [];

    try {

        cart =
            JSON.parse(
                localStorage.getItem(
                    "pharmacareCart"
                ) || "[]"
            );

    } catch (error) {

        console.error(
            "Cart parse error:",
            error
        );

        cart = [];

    }

    return Array.isArray(cart)
        ? cart
        : [];

}


/* ==============================
   LOAD CHECKOUT SUMMARY
============================== */

function loadCheckoutSummary() {

    const checkoutItemsData =
        getCheckoutItems();


    const checkoutItems =
        document.getElementById(
            "checkoutItems"
        );

    const summaryItems =
        document.getElementById(
            "summaryItems"
        );


    if (!checkoutItems) {

        return;

    }


    checkoutItems.innerHTML = "";


    /*
       summaryItems is used for
       showing total item count.
    */

    if (summaryItems) {

        summaryItems.textContent = "0";

    }


    if (
        checkoutItemsData.length === 0
    ) {

        checkoutItems.innerHTML =
            "<p>Your cart is empty.</p>";

        updateCheckoutTotals();

        return;

    }


    let totalQuantity = 0;


    checkoutItemsData.forEach(
        function (item) {

            const quantity =
                Number(
                    item.quantity || 1
                );

            const price =
                Number(
                    item.price || 0
                );

            const total =
                price * quantity;


            totalQuantity += quantity;


            /* CHECKOUT ITEM */

            const itemDiv =
                document.createElement(
                    "div"
                );

            itemDiv.className =
                "checkout-item";


            itemDiv.innerHTML = `

                <div>

                    <strong>
                        ${item.name || "Medicine"}
                    </strong>

                    <p>
                        Quantity: ${quantity}
                    </p>

                    <p>
                        Price: ₹${price.toFixed(2)}
                    </p>

                </div>

                <strong>
                    ₹${total.toFixed(2)}
                </strong>

            `;


            checkoutItems.appendChild(
                itemDiv
            );

        }
    );


    if (summaryItems) {

        summaryItems.textContent =
            totalQuantity;

    }


    updateCheckoutTotals();

}


/* ==============================
   UPDATE TOTALS
============================== */

function updateCheckoutTotals() {

    const checkoutItems =
        getCheckoutItems();


    let subtotal = 0;

    let discount = 0;


    checkoutItems.forEach(
        function (item) {

            const quantity =
                Number(
                    item.quantity || 1
                );

            const price =
                Number(
                    item.price || 0
                );

            const itemTotal =
                price * quantity;


            subtotal += itemTotal;


            const discountPercent =
                getCheckoutDiscount(
                    item.name
                );


            discount +=
                itemTotal *
                discountPercent /
                100;

        }
    );


    const delivery =
        subtotal > 0
            ? 40
            : 0;


    const grandTotal =
        subtotal +
        delivery -
        discount;


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


    if (subtotalElement) {

        subtotalElement.textContent =
            "₹" +
            subtotal.toFixed(2);

    }


    if (deliveryElement) {

        deliveryElement.textContent =
            "₹" +
            delivery.toFixed(2);

    }


    if (discountElement) {

        discountElement.textContent =
            "-₹" +
            discount.toFixed(2);

    }


    if (grandTotalElement) {

        grandTotalElement.textContent =
            "₹" +
            grandTotal.toFixed(2);

    }

}


/* ==============================
   PAYMENT METHODS
============================== */

function setupPaymentMethods() {

    const cod =
        document.getElementById(
            "cod"
        );


    if (cod) {

        cod.checked = true;

        showPayment("cod");

    }

}


/* ==============================
   SHOW PAYMENT DETAILS
============================== */

function showPayment(method) {

    const sections = [

        "upiDetails",
        "cardDetails",
        "netbankingDetails",
        "walletDetails"

    ];


    /* HIDE ALL */

    sections.forEach(
        function (id) {

            const element =
                document.getElementById(
                    id
                );


            if (element) {

                element.style.display =
                    "none";

            }

        }
    );


    /* UPI */

    if (method === "upi") {

        const element =
            document.getElementById(
                "upiDetails"
            );


        if (element) {

            element.style.display =
                "block";

        }

    }


    /* CARD */

    if (method === "card") {

        const element =
            document.getElementById(
                "cardDetails"
            );


        if (element) {

            element.style.display =
                "block";

        }

    }


    /* NET BANKING */

    if (method === "netbanking") {

        const element =
            document.getElementById(
                "netbankingDetails"
            );


        if (element) {

            element.style.display =
                "block";

        }

    }


    /* WALLET */

    if (method === "wallet") {

        const element =
            document.getElementById(
                "walletDetails"
            );


        if (element) {

            element.style.display =
                "block";

        }

    }

}


/* ==============================
   UPI APP
============================== */

function selectUPI(app) {

    const upiOptions =
        document.querySelectorAll(
            ".upi-option"
        );


    upiOptions.forEach(
        function (option) {

            option.classList.remove(
                "selected"
            );

        }
    );


    const selected =
        document.querySelector(
            `[data-upi="${app}"]`
        );


    if (selected) {

        selected.classList.add(
            "selected"
        );

    }

}


/* ==============================
   SEARCH
============================== */

function searchFromCheckout() {

    const input =
        document.getElementById(
            "checkoutSearch"
        );


    if (!input) {

        return;

    }


    const value =
        input.value.trim();


    if (value !== "") {

        window.location.href =
            "medicines.html?search=" +
            encodeURIComponent(
                value
            );

    } else {

        alert(
            "Please enter a medicine name."
        );

    }

}


/* ==============================
   PLACE ORDER
============================== */

function placeOrder(event) {

    if (event) {

        event.preventDefault();

    }


    /* ACCOUNT CHECK */

    if (!checkUserAccount()) {

        return;

    }


    /* ==============================
       GET CHECKOUT ITEMS
    ============================== */

    const checkoutItems =
        getCheckoutItems();


    if (
        checkoutItems.length === 0
    ) {

        alert(
            "Your cart is empty."
        );

        return;

    }


    /* ==============================
       CUSTOMER DETAILS
    ============================== */

    const customerName =
        getValue(
            "customerName"
        );


    const customerMobile =
        getValue(
            "customerMobile"
        );


    const customerPincode =
        getValue(
            "customerPincode"
        );


    const customerAddress =
        getValue(
            "customerAddress"
        );


    const customerCity =
        getValue(
            "customerCity"
        );


    if (
        customerName === "" ||
        customerMobile === "" ||
        customerPincode === "" ||
        customerAddress === "" ||
        customerCity === ""
    ) {

        alert(
            "Please fill in all delivery information."
        );

        return;

    }


    /* ==============================
       PAYMENT
    ============================== */

    const selectedPayment =
        document.querySelector(
            'input[name="payment"]:checked'
        );


    if (!selectedPayment) {

        alert(
            "Please select a payment method."
        );

        return;

    }


    const paymentMethod =
        selectedPayment.value;


    /* ==============================
       UPI VALIDATION
    ============================== */

    if (
        paymentMethod === "UPI"
    ) {

        const upiId =
            getValue(
                "upiId"
            );


        if (upiId === "") {

            alert(
                "Please enter your UPI ID."
            );

            return;

        }

    }


    /* ==============================
       CARD VALIDATION
    ============================== */

    if (
        paymentMethod === "Card"
    ) {

        const cardNumber =
            getValue(
                "cardNumber"
            );


        const expiry =
            getValue(
                "cardExpiry"
            );


        const cvv =
            getValue(
                "cardCVV"
            );


        const cardName =
            getValue(
                "cardName"
            );


        if (
            cardNumber === "" ||
            expiry === "" ||
            cvv === "" ||
            cardName === ""
        ) {

            alert(
                "Please fill in all card details."
            );

            return;

        }

    }


    /* ==============================
       NET BANKING VALIDATION
    ============================== */

    if (
        paymentMethod ===
        "Net Banking"
    ) {

        const bank =
            getValue(
                "bankName"
            );


        if (bank === "") {

            alert(
                "Please select your bank."
            );

            return;

        }

    }


    /* ==============================
       WALLET VALIDATION
    ============================== */

    if (
        paymentMethod === "Wallet"
    ) {

        const wallet =
            getValue(
                "walletName"
            );


        if (wallet === "") {

            alert(
                "Please select your wallet."
            );

            return;

        }

    }


    /* ==============================
       CALCULATE TOTAL
    ============================== */

    let subtotal = 0;

    let discount = 0;


    checkoutItems.forEach(
        function (item) {

            const quantity =
                Number(
                    item.quantity || 1
                );


            const price =
                Number(
                    item.price || 0
                );


            const itemTotal =
                price * quantity;


            subtotal += itemTotal;


            const discountPercent =
                getCheckoutDiscount(
                    item.name
                );


            discount +=
                itemTotal *
                discountPercent /
                100;

        }
    );


    const delivery =
        subtotal > 0
            ? 40
            : 0;


    const grandTotal =
        subtotal +
        delivery -
        discount;


    /* ==============================
       ORDER ID
    ============================== */

    const orderId =
        "PC" +
        String(
            Date.now()
        ).slice(-8);


    /* ==============================
       PAYMENT STATUS
    ============================== */

    const paymentStatus =
        paymentMethod ===
        "Cash on Delivery"
            ? "Pending"
            : "Paid";


    /* ==============================
       ORDER OBJECT
    ============================== */

    const order = {

        orderId: orderId,

        customer: {

            name: customerName,

            mobile: customerMobile,

            pincode: customerPincode,

            address: customerAddress,

            city: customerCity

        },


        /* Buy Now OR Cart */

        items: checkoutItems,


        subtotal: subtotal,

        delivery: delivery,

        discount: discount,

        grandTotal: grandTotal,


        paymentMethod:
            paymentMethod,

        paymentStatus:
            paymentStatus,


        status:
            "Order Placed",


        date:
            new Date()
                .toLocaleString()

    };


    /* ==============================
       SAVE ORDER
    ============================== */

    let orders = [];


    try {

        orders =
            JSON.parse(
                localStorage.getItem(
                    "pharmaOrders"
                ) || "[]"
            );

    } catch (error) {

        console.error(
            "Orders parse error:",
            error
        );

        orders = [];

    }


    if (!Array.isArray(orders)) {

        orders = [];

    }


    orders.push(order);


    localStorage.setItem(
        "pharmaOrders",
        JSON.stringify(
            orders
        )
    );


    localStorage.setItem(
        "pharmaLastOrder",
        JSON.stringify(
            order
        )
    );


    /* ==============================
       CLEAR CHECKOUT DATA
    ============================== */

    if (isBuyNowMode) {

        /*
           Direct Buy Now:
           Only remove buyNowItem.
           Existing cart remains safe.
        */

        localStorage.removeItem(
            "buyNowItem"
        );


        sessionStorage.removeItem(
            "checkoutMode"
        );

    } else {

        /*
           Normal Cart Checkout:
           Clear complete cart.
        */

        localStorage.removeItem(
            "pharmacareCart"
        );

    }


    /* ==============================
       UPDATE CART COUNT
    ============================== */

    if (
        typeof updateCartCount ===
        "function"
    ) {

        updateCartCount();

    }


    /* ==============================
       SUCCESS MESSAGE
    ============================== */

    alert(
        "Order placed successfully!"
    );


    /* ==============================
       RECEIPT PAGE
    ============================== */

    window.location.href =
        "receipt.html?orderId=" +
        encodeURIComponent(
            orderId
        );

}


/* ==============================
   COMPLETE ONLINE ORDER
============================== */

function completeOnlineOrder() {

    const selectedPayment =
        document.querySelector(
            'input[name="payment"]:checked'
        );


    if (!selectedPayment) {

        alert(
            "Please select a payment method."
        );

        return;

    }


    /* Payment selected */

    closePaymentModal();

    placeOrder();

}


/* ==============================
   CLOSE PAYMENT MODAL
============================== */

function closePaymentModal() {

    const modal =
        document.getElementById(
            "paymentModal"
        );


    if (modal) {

        modal.style.display =
            "none";

    }

}


/* ==============================
   GET VALUE
============================== */

function getValue(id) {

    const element =
        document.getElementById(
            id
        );


    if (!element) {

        return "";

    }


    return element.value.trim();

}