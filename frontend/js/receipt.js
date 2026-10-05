/* =========================================================
   PHARMACARE
   RECEIPT JAVASCRIPT
========================================================= */


/* =========================================================
   PAGE LOAD
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    loadReceipt();

    if (typeof updateCartCount === "function") {
        updateCartCount();
    }

});


/* =========================================================
   LOAD RECEIPT
========================================================= */

function loadReceipt() {

    let order = null;

    /* ================= GET ORDER ID ================= */

    const params =
        new URLSearchParams(
            window.location.search
        );

    const orderId =
        params.get("orderId");


    /* ================= GET ORDER ================= */

    try {

        const orders =
            JSON.parse(
                localStorage.getItem("pharmaOrders") || "[]"
            );


        /* Find order using Order ID */

        if (orderId) {

            order =
                orders.find(function (item) {

                    return String(item.orderId) ===
                        String(orderId);

                });

        }


        /* Fallback to last order */

        if (!order) {

            order =
                JSON.parse(
                    localStorage.getItem(
                        "pharmaLastOrder"
                    )
                );

        }

    } catch (error) {

        console.error(
            "Error loading receipt:",
            error
        );

        order = null;

    }


    /* ================= NO ORDER ================= */

    if (!order) {

        alert(
            "Receipt information not found."
        );

        window.location.href =
            "medicines.html";

        return;

    }


    /* ================= DISPLAY RECEIPT ================= */

    displayReceipt(order);

}


/* =========================================================
   DISPLAY RECEIPT
========================================================= */

function displayReceipt(order) {

    /* =====================================================
       ORDER INFORMATION
    ===================================================== */

    setText(
        "receiptOrderId",
        order.orderId || "-"
    );


    setText(
        "receiptDate",
        order.date || "-"
    );


    /* Payment Method */

    setText(
        "receiptPayment",
        order.paymentMethod || "-"
    );


    /* =====================================================
       PAYMENT STATUS
    ===================================================== */

    const paymentStatusElement =
        document.querySelector(".paid-text");


    if (paymentStatusElement) {

        paymentStatusElement.textContent =
            order.paymentStatus || "Pending";

    }


    /* =====================================================
       UPI ID
    ===================================================== */

    setText(
        "receiptUpi",
        order.upiId || "Not Applicable"
    );


    /* =====================================================
       CUSTOMER INFORMATION
    ===================================================== */

    /*
       Checkout मध्ये customer information
       customer object मध्ये save केली आहे.
    */

    const customer =
        order.customer || {};


    /* Customer Name */

    setText(
        "receiptCustomerName",
        customer.name || "-"
    );


    /* Mobile */

    setText(
        "receiptMobile",
        customer.mobile || "-"
    );


    /* Address */

    setText(
        "receiptAddress",
        customer.address || "-"
    );


    /* City */

    setText(
        "receiptCity",
        customer.city || "-"
    );


    /* Pincode */

    setText(
        "receiptPincode",
        customer.pincode || "-"
    );


    /* =====================================================
       MEDICINE ITEMS
    ===================================================== */

    const itemsContainer =
        document.getElementById(
            "receiptItems"
        );


    if (itemsContainer) {

        itemsContainer.innerHTML = "";


        const items =
            Array.isArray(order.items)
                ? order.items
                : [];


        if (items.length === 0) {

            const emptyRow =
                document.createElement("tr");

            emptyRow.innerHTML = `
                <td colspan="4">
                    No medicine information available.
                </td>
            `;

            itemsContainer.appendChild(
                emptyRow
            );

        }


        items.forEach(function (item) {

            const price =
                Number(item.price) || 0;


            const quantity =
                Number(item.quantity) || 1;


            const total =
                price * quantity;


            const row =
                document.createElement("tr");


            row.innerHTML = `

                <td>
                    ${item.name || "Medicine"}
                </td>

                <td>
                    ${quantity}
                </td>

                <td>
                    ₹${price.toFixed(2)}
                </td>

                <td>
                    ₹${total.toFixed(2)}
                </td>

            `;


            itemsContainer.appendChild(
                row
            );

        });

    }


    /* =====================================================
       TOTALS
    ===================================================== */

    const subtotal =
        Number(order.subtotal) || 0;


    const discount =
        Number(order.discount) || 0;


    const delivery =
        Number(order.delivery) || 0;


    const grandTotal =
        Number(order.grandTotal) || 0;


    /* Subtotal */

    setText(
        "receiptSubtotal",
        "₹" + subtotal.toFixed(2)
    );


    /* Discount */

    setText(
        "receiptDiscount",
        "-₹" + discount.toFixed(2)
    );


    /* Delivery */

    setText(
        "receiptDelivery",

        delivery === 0
            ? "Free"
            : "₹" + delivery.toFixed(2)
    );


    /* Grand Total */

    setText(
        "receiptGrandTotal",
        "₹" + grandTotal.toFixed(2)
    );

}


/* =========================================================
   SET TEXT
========================================================= */

function setText(id, value) {

    const element =
        document.getElementById(id);


    if (element) {

        element.textContent = value;

    }

}


/* =========================================================
   PRINT RECEIPT
========================================================= */

function printReceipt() {

    window.print();

}


/* =========================================================
   CONTINUE SHOPPING
========================================================= */

function goToHome() {

    window.location.href =
        "../index.html";

}


/* =========================================================
   SEARCH
========================================================= */

function searchFromReceipt() {

    const input =
        document.getElementById(
            "receiptSearch"
        );


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