<?php
/**
 * Template: Sales Interface
 *
 * The main Point of Sale (POS) screen for cashiers. Features include:
 * - Product search (live via API)
 * - Adding products to a sale cart
 * - Adjusting quantities in the cart
 * - Calculating totals dynamically
 * - Handling cash payment and calculating change
 * - Finalizing the sale (submitting data to a handler)
 * - Uses Alpine.js for client-side interactivity.
 *
 * Included by index.php when view=sale (default view).
 * Assumes product_functions.php and sale_functions.php are included by index.php.
 */

// --- Defensive Check: Ensure required functions are available ---
if (!function_exists('getAllProducts')) { // Example check, relies on index.php loading
    die("FATAL ERROR: Product functions not loaded in sales_interface.php.");
}
// Note: sale_functions.php might not be strictly needed *directly* in this template,
// but it's needed by the process_sale.php handler which this template interacts with.
// index.php includes it for the 'sale' view anyway.

// --- Handle Flash Messages (e.g., after a sale completion/redirect) ---
$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']); // Clear after retrieving
}

// --- Define API Endpoint URLs ---
// Adjust these paths relative to the root index.php if necessary.
// We assume an 'api' folder exists at the root for search, and handlers are in modules.
$search_api_url = 'modules/products/search_handler.phpDeng Aryan. 90 and A. No product, no. Today. No problem OK 19. Backspace. Change simple basic. Still compared successfully OK done and dusted reports will be of. Numbers of transactions. Main tere na dekhanamma anthemi Kahani Nene but system maker. System. Git handle source control. ';
$finalize_api_url = 'modules/sales/process_sale.php';

?>

<div class="container mx-auto px-4 py-6">

    <?php if ($flash_message): ?>
    <div id="sales-flash-message" class="mb-4 p-4 rounded-md shadow <?php echo ($flash_message['type'] === 'success') ? 'bg-green-100 border border-green-300 text-green-800' : 'bg-red-100 border border-red-300 text-red-800'; ?>" role="alert">
        <button type="button" onclick="this.parentElement.style.display='none';" class="float-right -mt-1 -mr-1 p-1 text-lg font-bold leading-none" aria-label="Dismiss alert">&times;</button>
        <span class="font-semibold"><?php echo ucfirst($flash_message['type']); ?>:</span>
        <?php echo htmlspecialchars($flash_message['text']); ?>
    </div>
    <?php endif; ?>

    <div x-data="salesInterface('<?php echo htmlspecialchars($search_api_url, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($finalize_api_url, ENT_QUOTES); ?>')"
         x-init="init()"
         class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">

        <div class="lg:col-span-1 bg-white p-4 sm:p-5 rounded-lg shadow-md border border-gray-200 space-y-4 flex flex-col">
            <h2 class="text-xl font-semibold text-gray-700 border-b border-gray-300 pb-2 mb-3">Find Products</h2>
            <div>
                <label for="search_term" class="sr-only">Search Products</label>
                <div class="relative">
                    <input type="text" id="search_term"
                           x-model.debounce.400ms="searchTerm" @input="searchProducts"
                           placeholder="Scan barcode or type ID / Name..."
                           class="form-input block w-full pl-3 pr-10 py-2 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-150 ease-in-out"
                           autocomplete="off"
                           aria-label="Search Products">
                     <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>
                     </div>
                </div>
            </div>

            <div class="flex-grow min-h-[200px] relative border border-gray-200 rounded-md bg-gray-50 overflow-hidden">
                <div x-show="isLoading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
                     <svg class="animate-spin h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                     <span class="ml-2 text-gray-600">Searching...</span>
                 </div>

                <ul class="divide-y divide-gray-200 h-full overflow-y-auto" x-show="!isLoading && searchResults.length > 0">
                    <template x-for="product in searchResults" :key="product.id">
                        <li class="p-3 hover:bg-blue-50 flex justify-between items-center cursor-pointer" @click="addToCart(product)">
                            <div class="flex-grow mr-2">
                                <p class="font-semibold text-gray-800 truncate" x-text="product.name" :title="product.name"></p>
                                <p class="text-sm text-gray-600">
                                    ID: <span class="font-mono" x-text="product.id"></span> |
                                    Price: <span class="font-mono">Rs. <span x-text="parseFloat(product.price).toFixed(2)"></span></span> |
                                    Stock: <span class="font-semibold" x-text="product.stock" :class="{ 'text-red-600': product.stock <= 0, 'text-green-600': product.stock > 0 }"></span>
                                </p>
                            </div>
                            <button @click.stop="addToCart(product)" tabindex="-1"
                                    :disabled="product.stock <= 0"
                                    class="bg-blue-500 hover:bg-blue-600 active:bg-blue-700 text-white text-xs font-bold py-1.5 px-3 rounded-md shadow transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg> Add
                            </button>
                        </li>
                    </template>
                </ul>

                <div class="flex items-center justify-center h-full text-center text-gray-500 p-4" x-show="!isLoading && searchResults.length === 0">
                    <span x-show="searchTerm.length > 0">No products found matching "<strong x-text="searchTerm"></strong>".</span>
                    <span x-show="searchTerm.length === 0">Enter search term or scan barcode above.</span>
                 </div>
            </div>
        </div><div class="lg:col-span-2 bg-white p-4 sm:p-5 rounded-lg shadow-md border border-gray-200 space-y-4 flex flex-col">
            <h2 class="text-xl font-semibold text-gray-700 border-b border-gray-300 pb-2 mb-3">Current Sale Cart</h2>

            <div class="flex-grow min-h-[250px] max-h-[55vh] border border-gray-200 rounded-md overflow-y-auto relative bg-gray-50">
                 <div class="flex items-center justify-center h-full text-center text-gray-400 p-5" x-show="cart.length === 0">
                     <span>Cart is empty.<br>Add products from the search panel.</span>
                 </div>

                <table class="min-w-full divide-y divide-gray-200" x-show="cart.length > 0">
                    <thead class="bg-gray-100 sticky top-0 z-10">
                        <tr>
                            <th scope="col" class="w-5/12 px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th scope="col" class="w-2/12 px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                            <th scope="col" class="w-2/12 px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th scope="col" class="w-2/12 px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Item Total</th>
                            <th scope="col" class="w-1/12 px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" aria-label="Remove"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(item, index) in cart" :key="item.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-800 truncate" x-text="item.name" :title="item.name"></td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-700 text-right font-mono" x-text="'Rs.' + parseFloat(item.price).toFixed(2)"></td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-700 text-center">
                                    <input type="number" min="1" step="1" :max="item.maxStock || 999" :value="item.quantity" @input="updateQuantity(item.id, parseInt($event.target.value))"
                                           class="form-input w-16 text-center border-gray-300 rounded shadow-sm py-1 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-150 ease-in-out"
                                           aria-label="Item Quantity">
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 text-right font-semibold font-mono" x-text="'Rs.' + (item.price * item.quantity).toFixed(2)"></td>
                                <td class="px-3 py-2 whitespace-nowrap text-center text-sm font-medium">
                                    <button @click="removeFromCart(item.id)" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-100" title="Remove Item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                        <span class="sr-only">Remove</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div><div class="pt-3 space-y-2">
                 <div class="flex justify-between items-center text-lg">
                     <span class="font-medium text-gray-700">Subtotal:</span>
                     <span class="font-semibold text-gray-900 font-mono" x-text="'Rs. ' + cartSubtotal.toFixed(2)"></span>
                 </div>
                 <div class="flex justify-between items-center text-2xl font-bold text-blue-700 bg-blue-50 p-3 rounded-md border border-blue-200">
                     <span class="">Grand Total:</span>
                     <span class="font-mono" x-text="'Rs. ' + cartTotal.toFixed(2)"></span>
                 </div>
             </div>

            <div class="pt-3 border-t border-gray-300">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 items-end">
                     <div>
                         <label for="amount_received" class="block text-sm font-medium text-gray-700 mb-1">Amount Received (Cash):</label>
                         <div class="relative mt-1">
                             <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                 <span class="text-gray-500 sm:text-sm">Rs.</span>
                             </div>
                             <input type="number" id="amount_received" name="amount_received" step="0.01" min="0"
                                    x-model.number="amountReceived" @input="finalizeError = null" :disabled="cart.length === 0"
                                    class="form-input block w-full rounded-md border border-gray-300 pl-10 pr-4 py-2 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 disabled:bg-gray-100 transition duration-150 ease-in-out font-mono text-lg"
                                    placeholder="0.00">
                         </div>
                     </div>
                     <div class="text-left md:text-right">
                         <p class="text-sm font-medium text-gray-700">Change Due:</p>
                         <p class="text-2xl font-bold font-mono" :class="changeDue >= 0 ? 'text-green-600' : 'text-red-600'" x-text="'Rs. ' + changeDue.toFixed(2)"></p>
                     </div>
                </div>
             </div>

            <div class="pt-4 border-t border-gray-300 flex justify-between items-center mt-auto"> <button @click="cancelSale" type="button"
                        class="bg-red-100 hover:bg-red-200 text-red-700 font-semibold py-2 px-5 rounded-md shadow-sm border border-red-300 transition duration-150 ease-in-out inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    Cancel Sale
                </button>

                 <button @click="finalizeSale" type="button"
                        :disabled="cart.length === 0 || isFinalizing || amountReceived === null || amountReceived < cartTotal || changeDue < 0"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-md shadow-sm hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-150 ease-in-out inline-flex items-center text-lg disabled:opacity-60 disabled:cursor-not-allowed">
                     <div x-show="isFinalizing" role="status" class="mr-2">
                         <svg aria-hidden="true" class="w-5 h-5 text-gray-200 animate-spin dark:text-gray-600 fill-white" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                     </div>
                     <svg x-show="!isFinalizing" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline-block mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    <span x-text="isFinalizing ? 'Processing...' : 'Finalize Sale'"></span>
                </button>
             </div>

             <div x-show="finalizeError" x-transition
                 class="mt-3 p-3 rounded-md bg-red-100 border border-red-300 text-red-800 text-sm"
                 role="alert"
                 x-text="finalizeError">
            </div>

        </div></div></div><script>
    function salesInterface(searchApiUrl, finalizeApiUrl) {
        return {
            // --- Reactive State Properties ---
            searchTerm: '',          // Bound to search input
            searchResults: [],       // Holds products from search API
            cart: [],                // Array of sale items: { id, name, price, quantity }
            amountReceived: null,    // Bound to amount received input (null signifies empty)
            isLoading: false,        // Controls search loading indicator
            isFinalizing: false,     // Controls finalize button state/indicator
            finalizeError: null,     // Holds error message from finalize attempt
            lastAddedProductId: null,// For potential focus management (optional)

            // --- Initialization ---
            init() {
                console.log("Sales interface initialized.");
                 // You could potentially load a saved cart from session/local storage here if needed
                 // Example: this.cart = JSON.parse(localStorage.getItem('savedCart') || '[]');
                 // Alpine.js automatically makes computed properties reactive
            },

            // --- Computed Properties (Getters) ---
            get cartSubtotal() {
                // Calculate sum of (price * quantity) for all items in cart
                return this.cart.reduce((total, item) => {
                    const price = parseFloat(item.price) || 0;
                    const quantity = parseInt(item.quantity) || 0;
                    return total + (price * quantity);
                }, 0);
            },
            get cartTotal() {
                // In this simple version, total is the same as subtotal
                // Add taxes or discounts here if they are implemented later
                return this.cartSubtotal;
            },
            get changeDue() {
                // Calculate change based on amount received and cart total
                if (this.amountReceived === null || this.amountReceived === '' || this.cartTotal <= 0) {
                    return 0; // No change if nothing received or cart empty
                }
                const received = parseFloat(this.amountReceived) || 0;
                const total = this.cartTotal;
                // Return positive change or 0 if exact amount, negative if amount is less
                return received - total;
            },

            // --- Methods ---
            searchProducts() {
                // Triggered by search input changes (debounced)
                if (this.searchTerm.trim().length < 1) { // Don't search on empty string
                    this.searchResults = [];
                    return;
                }
                this.isLoading = true;
                this.searchResults = []; // Clear previous results while loading
                this.finalizeError = null; // Clear previous errors

                const url = `${searchApiUrl}?search_term=${encodeURIComponent(this.searchTerm)}`;

                fetch(url)
                    .then(response => {
                        if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }
                        return response.json();
                    })
                    .then(data => {
                        if (Array.isArray(data)) {
                            this.searchResults = data;
                        } else {
                             console.warn("Search API did not return an array:", data);
                             this.searchResults = [];
                             this.finalizeError = "Received invalid data from product search.";
                        }
                    })
                    .catch(error => {
                        console.error('Product Search Fetch Error:', error);
                        this.finalizeError = 'Failed to search products. Check network or server status.';
                        this.searchResults = []; // Ensure results are cleared on error
                    })
                    .finally(() => {
                        this.isLoading = false;
                    });
            },

            addToCart(product) {
                // Adds a product from search results to the cart or increments quantity
                if (!product || !product.id || product.stock <= 0) {
                    alert(`Product '${product?.name || 'Unknown'}' is out of stock or invalid.`);
                    return; // Don't add if no stock or invalid product
                }

                const existingItemIndex = this.cart.findIndex(item => item.id === product.id);

                if (existingItemIndex > -1) {
                    // Item already in cart, increment quantity if stock allows
                    const currentQuantity = this.cart[existingItemIndex].quantity;
                    if (currentQuantity + 1 <= product.stock) {
                        this.cart[existingItemIndex].quantity++;
                    } else {
                        alert(`Cannot add more of '${product.name}'. Only ${product.stock} units available.`);
                    }
                } else {
                    // Add new item to cart if stock available
                     if (1 <= product.stock) {
                        this.cart.push({
                            id: product.id,
                            name: product.name,
                            price: parseFloat(product.price), // Store price at time of adding
                            quantity: 1,
                            maxStock: product.stock // Store max stock for potential validation in quantity input
                        });
                        this.lastAddedProductId = product.id;
                     } else {
                          // This case should be prevented by the stock check above, but as safety
                          alert(`Cannot add '${product.name}'. Out of stock.`);
                     }
                }
                 // Optional: Clear search after adding an item for better UX
                 // this.clearSearch();
                 this.finalizeError = null; // Clear any previous errors
            },

            updateQuantity(productId, newQuantity) {
                // Updates the quantity of an item in the cart
                const itemIndex = this.cart.findIndex(item => item.id === productId);
                if (itemIndex > -1) {
                    const qty = Math.max(1, parseInt(newQuantity) || 1); // Ensure quantity is at least 1

                    // Optional: Client-side check against maxStock (if stored during addToCart)
                    // if (this.cart[itemIndex].maxStock && qty > this.cart[itemIndex].maxStock) {
                    //     alert(`Quantity cannot exceed available stock (${this.cart[itemIndex].maxStock}).`);
                    //     // Find a way to revert the input - can be tricky with direct binding
                    //     // Maybe $nextTick + setting value? Or emit event.
                    //     // For now, let server handle final validation.
                    //     this.cart[itemIndex].quantity = this.cart[itemIndex].maxStock; // Set to max allowed
                    // } else {
                        this.cart[itemIndex].quantity = qty;
                    // }
                }
                 this.finalizeError = null; // Clear any previous errors
            },

            removeFromCart(productId) {
                // Removes an item from the cart by filtering
                this.cart = this.cart.filter(item => item.id !== productId);
                 this.finalizeError = null; // Clear any previous errors
            },

            clearSearch() {
                // Clears the search term and results
                this.searchTerm = '';
                this.searchResults = [];
            },

            cancelSale() {
                // Clears the entire current sale state after confirmation
                if (confirm('Are you sure you want to cancel this sale and clear the cart?')) {
                    this.cart = [];
                    this.amountReceived = null;
                    this.clearSearch();
                    this.finalizeError = null;
                    this.isFinalizing = false;
                    // Also hide any globally displayed flash messages if needed
                    const globalFlash = document.getElementById('sales-flash-message');
                    if (globalFlash) globalFlash.style.display = 'none';
                    console.log("Sale cancelled.");
                }
            },

            finalizeSale() {
                // Validates and submits the sale data to the backend handler
                this.finalizeError = null; // Clear previous errors

                // --- Client-Side Validation ---
                if (this.cart.length === 0) {
                    this.finalizeError = "Cannot finalize: Cart is empty."; return;
                }
                if (this.amountReceived === null || this.amountReceived === '' || parseFloat(this.amountReceived) < 0) {
                    this.finalizeError = "Cannot finalize: Please enter a valid amount received."; return;
                }
                 const received = parseFloat(this.amountReceived);
                if (received < this.cartTotal) {
                     this.finalizeError = `Cannot finalize: Amount received (Rs.${received.toFixed(2)}) is less than the total (Rs.${this.cartTotal.toFixed(2)}).`; return;
                }
                // Change calculation check (redundant if amount >= total is checked)
                // if (this.changeDue < 0) {
                //     this.finalizeError = "Cannot finalize: Insufficient amount received."; return;
                // }

                // --- Prepare Data for Backend ---
                this.isFinalizing = true;
                const saleData = {
                    items: this.cart.map(item => ({ // Send only necessary data to backend
                        product_id: item.id,
                        quantity: item.quantity,
                        price_at_sale: item.price // Price when item was added
                    })),
                    total_amount: this.cartTotal,
                    amount_received: received,
                    change_given: this.changeDue,
                    payment_type: 'Cash' // Assuming Cash payment for this simple version
                };

                // --- Send Data via Fetch POST ---
                fetch(finalizeApiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json', // We are sending JSON
                        'Accept': 'application/json'        // We expect JSON response
                    },
                    body: JSON.stringify(saleData) // Convert JS object to JSON string
                })
                .then(response => {
                    // Check if response is ok (status 200-299) and if content type is JSON
                    if (!response.ok) {
                        // Try to get error message from non-JSON response if possible
                        return response.text().then(text => {
                            throw new Error(`Server responded with status ${response.status}. Response: ${text || '(empty)'}`);
                        });
                     }
                     // Check content type before parsing JSON
                     const contentType = response.headers.get("content-type");
                     if (contentType && contentType.indexOf("application/json") !== -1) {
                         return response.json(); // Parse JSON body
                     } else {
                         return response.text().then(text => { // Handle non-JSON success response?
                             console.warn("Server returned non-JSON response:", text);
                             // Assume success if status was ok, but backend response format is unexpected
                              return { success: true, message: "Sale likely processed, but server response format was unexpected.", sale_id: null };
                             // throw new Error("Received non-JSON response from server");
                         });
                     }

                })
                .then(data => {
                    // Process JSON response from backend
                    if (data.success) {
                        // Sale successful! Show confirmation and reset interface.
                        alert(`Sale Completed Successfully!\nSale ID: ${data.sale_id || 'N/A'}\nTotal: Rs.${this.cartTotal.toFixed(2)}\nChange: Rs.${this.changeDue.toFixed(2)}`);
                        // Consider a nicer modal/receipt display instead of alert
                        this.cart = [];
                        this.amountReceived = null;
                        this.clearSearch();
                        this.finalizeError = null;
                        // Optionally trigger print or display receipt details from data.receipt if returned
                        // Reloading might be simplest way to reset completely and show server flash msg if any
                         // window.location.reload();
                    } else {
                        // Sale failed on the backend (e.g., stock issue, validation)
                        this.finalizeError = `Sale Failed: ${data.message || 'An unknown error occurred on the server.'}`;
                    }
                })
                .catch(error => {
                    // Handle network errors or issues during fetch/parsing
                    console.error('Finalize Sale Fetch/Process Error:', error);
                    this.finalizeError = `Sale Failed: ${error.message || 'Could not connect to server or process the request.'}`;
                })
                .finally(() => {
                    // Runs regardless of success or failure
                    this.isFinalizing = false;
                });
            } // end finalizeSale
        } // end salesInterface return object
    } // end salesInterface function
</script>
