const products = {

    med001: {
        id: "med001",
        name: "Paracetamol 500mg",
        category: "Pain Relief",
        price: 22.50,
        mrp: 25,
        discount: 10
    },

    med002: {
        id: "med002",
        name: "Vitamin C Tablets",
        category: "Vitamins",
        price: 102,
        mrp: 120,
        discount: 15
    },

    med003: {
        id: "med003",
        name: "Cetirizine 10mg",
        category: "Cold & Flu",
        price: 42.75,
        mrp: 45,
        discount: 5
    },

    med004: {
        id: "med004",
        name: "Antiseptic Cream",
        category: "Skin Care",
        price: 74.80,
        mrp: 85,
        discount: 12
    },

    med005: {
        id: "med005",
        name: "Digestive Tablets",
        category: "Digestive",
        price: 59.80,
        mrp: 65,
        discount: 8
    },

    med006: {
        id: "med006",
        name: "Ibuprofen 400mg",
        category: "Pain Relief",
        price: 49.50,
        mrp: 55,
        discount: 10
    },

    med007: {
        id: "med007",
        name: "Cough Syrup",
        category: "Cold & Flu",
        price: 80.75,
        mrp: 95,
        discount: 15
    },

    med008: {
        id: "med008",
        name: "Multivitamin Tablets",
        category: "Vitamins",
        price: 144,
        mrp: 180,
        discount: 20
    }

};


/* =========================================================
   PRODUCT DETAILS
========================================================= */

function openProduct(id) {

    if (!products[id]) {
        return;
    }

    window.location.href =
        "product-details.html?id=" + encodeURIComponent(id);
}


/* =========================================================
   CART
========================================================= */

function getCart() {

    try {

        return JSON.parse(
            localStorage.getItem("pharmacareCart")
        ) || [];

    } catch (error) {

        return [];

    }
}


function saveCart(cart) {

    localStorage.setItem(
        "pharmacareCart",
        JSON.stringify(cart)
    );

}


function updateCartCount() {

    const cart = getCart();

    let count = 0;

    cart.forEach(item => {

        count += Number(item.quantity || 1);

    });

    const cartCount =
        document.getElementById("cartCount");

    if (cartCount) {

        cartCount.textContent = count;

    }

}


function addToCart(id) {

    const product = products[id];

    if (!product) {
        return;
    }

    const cart = getCart();

    const existing =
        cart.find(item => item.productId === id);

    if (existing) {

        existing.quantity =
            Number(existing.quantity || 1) + 1;

    } else {

        cart.push({

            productId: product.id,

            name: product.name,

            category: product.category,

            price: product.price,

            mrp: product.mrp,

            discount: product.discount,

            quantity: 1

        });

    }

    saveCart(cart);

    updateCartCount();

    alert(product.name + " added to cart.");

}


/* =========================================================
   BUY NOW
========================================================= */

function buyNow(id) {

    const product = products[id];

    if (!product) {
        return;
    }

    const buyNowItem = {

        productId: product.id,

        name: product.name,

        category: product.category,

        price: product.price,

        mrp: product.mrp,

        discount: product.discount,

        quantity: 1

    };

    localStorage.setItem(
        "buyNowItem",
        JSON.stringify(buyNowItem)
    );

    window.location.href = "checkout.html";

}


/* =========================================================
   SEARCH
========================================================= */

function searchMedicines() {

    const input =
        document.getElementById("medicineSearch");

    const cards =
        document.querySelectorAll(".medicine-card");

    if (!input) {
        return;
    }

    const value =
        input.value.toLowerCase().trim();

    let visible = 0;

    cards.forEach(card => {

        const text =
            card.textContent.toLowerCase();

        if (text.includes(value)) {

            card.style.display = "";

            visible++;

        } else {

            card.style.display = "none";

        }

    });

    updateMedicineCount(visible);

}


/* =========================================================
   CATEGORY FILTER
========================================================= */

function filterMedicines(category) {

    const cards =
        document.querySelectorAll(".medicine-card");

    let visible = 0;

    cards.forEach(card => {

        const cardCategory =
            card.dataset.category;

        if (
            category === "all" ||
            cardCategory === category
        ) {

            card.style.display = "";

            visible++;

        } else {

            card.style.display = "none";

        }

    });

    updateMedicineCount(visible);

}


function updateMedicineCount(count) {

    const counter =
        document.getElementById("medicineCount");

    if (!counter) {
        return;
    }

    if (count === 8) {

        counter.textContent =
            "Showing all medicines";

    } else {

        counter.textContent =
            "Showing " + count + " medicines";

    }

}


/* =========================================================
   SORT
========================================================= */

function sortMedicines(value) {

    const grid =
        document.getElementById("medicineGrid");

    if (!grid) {
        return;
    }

    const cards =
        Array.from(
            grid.querySelectorAll(".medicine-card")
        );

    cards.sort((a, b) => {

        const idA = a.dataset.id;
        const idB = b.dataset.id;

        if (value === "low") {

            return products[idA].price -
                   products[idB].price;

        }

        if (value === "high") {

            return products[idB].price -
                   products[idA].price;

        }

        if (value === "name") {

            return products[idA].name.localeCompare(
                products[idB].name
            );

        }

        return idA.localeCompare(idB);

    });

    cards.forEach(card => {

        grid.appendChild(card);

    });

}


/* =========================================================
   EVENT LISTENERS
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        updateCartCount();


        /* PRODUCT CLICK */

        document
            .querySelectorAll(".product-link")
            .forEach(element => {

                element.addEventListener(
                    "click",
                    function (event) {

                        event.stopPropagation();

                        const id =
                            this.dataset.id;

                        openProduct(id);

                    }
                );

            });


        /* ADD TO CART */

        document
            .querySelectorAll(".add-cart-btn")
            .forEach(button => {

                button.addEventListener(
                    "click",
                    function (event) {

                        event.stopPropagation();

                        addToCart(
                            this.dataset.id
                        );

                    }
                );

            });


        /* BUY NOW */

        document
            .querySelectorAll(".buy-btn")
            .forEach(button => {

                button.addEventListener(
                    "click",
                    function (event) {

                        event.stopPropagation();

                        buyNow(
                            this.dataset.id
                        );

                    }
                );

            });


        /* SEARCH */

        const searchButton =
            document.getElementById("searchButton");

        if (searchButton) {

            searchButton.addEventListener(
                "click",
                searchMedicines
            );

        }


        const searchInput =
            document.getElementById("medicineSearch");

        if (searchInput) {

            searchInput.addEventListener(
                "input",
                searchMedicines
            );

        }


        /* FILTER */

        document
            .querySelectorAll(".filter-btn")
            .forEach(button => {

                button.addEventListener(
                    "click",
                    function () {

                        document
                            .querySelectorAll(".filter-btn")
                            .forEach(btn =>
                                btn.classList.remove("active")
                            );

                        this.classList.add("active");

                        filterMedicines(
                            this.dataset.category
                        );

                    }
                );

            });


        /* SORT */

        const sortSelect =
            document.getElementById("sortMedicine");

        if (sortSelect) {

            sortSelect.addEventListener(
                "change",
                function () {

                    sortMedicines(this.value);

                }
            );

        }

    }
);
/* =====================================================
   PRODUCT CARD CLICK
===================================================== */

document.addEventListener("DOMContentLoaded", function () {

    const medicineCards = document.querySelectorAll(".medicine-card");

    medicineCards.forEach(function (card) {

        card.addEventListener("click", function () {

            const productId = card.getAttribute("data-id");

            if (!productId) {
                console.error("Product ID missing");
                return;
            }

            window.location.href =
                "product-details.html?id=" + productId;

        });

    });

});