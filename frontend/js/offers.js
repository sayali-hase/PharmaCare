/* =====================================================
   PHARMACARE OFFERS JAVASCRIPT
   ===================================================== */


/* ================= PRODUCT DATA ================= */

const offerProducts = [

    {
        id: "med001",
        name: "Paracetamol 500mg",
        category: "Pain Relief",
        price: 22.50,
        mrp: 25,
        discount: 10
    },

    {
        id: "med002",
        name: "Vitamin C Tablets",
        category: "Vitamins",
        price: 102,
        mrp: 120,
        discount: 15
    },

    {
        id: "med003",
        name: "Cetirizine 10mg",
        category: "Cold & Flu",
        price: 42.75,
        mrp: 45,
        discount: 5
    },

    {
        id: "med004",
        name: "Antiseptic Cream",
        category: "Skin Care",
        price: 74.80,
        mrp: 85,
        discount: 12
    },

    {
        id: "med005",
        name: "Digestive Tablets",
        category: "Digestive",
        price: 59.80,
        mrp: 65,
        discount: 8
    },

    {
        id: "med006",
        name: "Ibuprofen 400mg",
        category: "Pain Relief",
        price: 49.50,
        mrp: 55,
        discount: 10
    },

    {
        id: "med007",
        name: "Cough Syrup",
        category: "Cold & Flu",
        price: 80.75,
        mrp: 95,
        discount: 15
    },

    {
        id: "med008",
        name: "Multivitamin Tablets",
        category: "Vitamins",
        price: 144,
        mrp: 180,
        discount: 20
    }

];


/* ================= FIND PRODUCT ================= */

function getOfferProduct(productId) {

    return offerProducts.find(
        product => product.id === productId
    );

}


/* ================= OPEN PRODUCT ================= */

function openProduct(productId) {

    window.location.href =
        "product-details.html?id=" +
        encodeURIComponent(productId);

}


/* ================= ADD TO CART ================= */

function addToCart(productId) {

    const product = getOfferProduct(productId);

    if (!product) {
        return;
    }


    let cart = [];

    try {

        cart =
            JSON.parse(
                localStorage.getItem("pharmacareCart")
            ) || [];

    } catch (error) {

        cart = [];

    }


    const existingItem = cart.find(
        item => item.productId === product.id
    );


    if (existingItem) {

        existingItem.quantity =
            Number(existingItem.quantity || 1) + 1;

    } else {

        cart.push({

            productId: product.id,

            id: product.id,

            name: product.name,

            category: product.category,

            price: product.price,

            mrp: product.mrp,

            discount: product.discount,

            quantity: 1

        });

    }


    localStorage.setItem(
        "pharmacareCart",
        JSON.stringify(cart)
    );


    updateCartCount();

    alert(product.name + " added to cart.");
}


/* ================= BUY NOW ================= */

function buyNow(productId) {

    const product = getOfferProduct(productId);

    if (!product) {
        return;
    }


    const buyNowItem = {

        productId: product.id,

        id: product.id,

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


    sessionStorage.setItem(
        "checkoutMode",
        "buyNow"
    );


    window.location.href =
        "checkout.html?mode=buyNow";

}


/* ================= CART COUNT ================= */

function updateCartCount() {

    const cartCountElement =
        document.getElementById("cartCount");

    if (!cartCountElement) {
        return;
    }


    let cart = [];

    try {

        cart =
            JSON.parse(
                localStorage.getItem("pharmacareCart")
            ) || [];

    } catch (error) {

        cart = [];

    }


    let totalQuantity = 0;


    cart.forEach(item => {

        totalQuantity +=
            Number(item.quantity || 0);

    });


    cartCountElement.textContent =
        totalQuantity;

}


/* ================= SEARCH ================= */

function searchOffers() {

    const searchInput =
        document.getElementById("offerSearch");

    if (!searchInput) {
        return;
    }


    const searchTerm =
        searchInput.value
            .trim()
            .toLowerCase();


    const cards =
        document.querySelectorAll(
            ".offer-product"
        );


    cards.forEach(card => {

        const productName =
            (card.dataset.name || "")
                .toLowerCase();


        if (
            searchTerm === "" ||
            productName.includes(searchTerm)
        ) {

            card.style.display = "";

        } else {

            card.style.display = "none";

        }

    });

}


/* ================= SEARCH ENTER KEY ================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        updateCartCount();


        const searchInput =
            document.getElementById("offerSearch");


        if (searchInput) {

            searchInput.addEventListener(
                "keyup",
                function (event) {

                    if (
                        event.key === "Enter"
                    ) {

                        searchOffers();

                    }

                }
            );


            searchInput.addEventListener(
                "input",
                function () {

                    searchOffers();

                }
            );

        }

    }
);