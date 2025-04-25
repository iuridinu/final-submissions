document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart from localStorage
    let cart = JSON.parse(localStorage.getItem('iceCreamCart')) || [];
    updateCartCount();
    
    // Add to cart buttons
    const addButtons = document.querySelectorAll('.add-to-cart');
    addButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');
            const productPrice = parseFloat(this.getAttribute('data-price'));
            
            // Check if product is already in cart
            const existingItem = cart.find(item => item.id === productId);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    quantity: 1
                });
            }
            
            // Save to localStorage
            localStorage.setItem('iceCreamCart', JSON.stringify(cart));
            
            // Update cart display
            updateCartCount();
            
            // Show notification
            showNotification(`${productName} added to cart!`);
        });
    });
    
    // Cart button click - show modal
    document.getElementById('cart').addEventListener('click', function(e) {
        e.preventDefault();
        displayCartItems();
        const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));
        cartModal.show();
    });
    
    // Checkout button
    document.getElementById('checkout-btn').addEventListener('click', function() {
        window.location.href = 'checkout.php';
    });
    
    // Display cart items in modal
    function displayCartItems() {
        const cartItemsContainer = document.getElementById('cart-items');
        const cartTotalElement = document.getElementById('cart-total');
        
        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '<p class="text-center py-4">Your cart is empty</p>';
            cartTotalElement.textContent = 'Rs. 0.00';
            return;
        }
        
        let html = '<table class="table">';
        html += '<thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th></th></tr></thead>';
        html += '<tbody>';
        
        let total = 0;
        
        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            
            html += `<tr>
                <td>${item.name}</td>
                <td>Rs. ${item.price.toFixed(2)}</td>
                <td>
                    <div class="input-group input-group-sm" style="width: 100px;">
                        <button class="btn btn-outline-secondary decrease-qty" data-index="${index}">-</button>
                        <input type="text" class="form-control text-center" value="${item.quantity}" readonly>
                        <button class="btn btn-outline-secondary increase-qty" data-index="${index}">+</button>
                    </div>
                </td>
                <td>Rs. ${itemTotal.toFixed(2)}</td>
                <td><button class="btn btn-sm btn-danger remove-item" data-index="${index}"><i class="fas fa-trash"></i></button></td>
            </tr>`;
        });
        
        html += '</tbody></table>';
        cartItemsContainer.innerHTML = html;
        cartTotalElement.textContent = `Rs. ${total.toFixed(2)}`;
        
        // Add event listeners for cart manipulation
        const decreaseButtons = document.querySelectorAll('.decrease-qty');
        const increaseButtons = document.querySelectorAll('.increase-qty');
        const removeButtons = document.querySelectorAll('.remove-item');
        
        decreaseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                if (cart[index].quantity > 1) {
                    cart[index].quantity -= 1;
                    localStorage.setItem('iceCreamCart', JSON.stringify(cart));
                    displayCartItems();
                    updateCartCount();
                }
            });
        });
        
        increaseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                cart[index].quantity += 1;
                localStorage.setItem('iceCreamCart', JSON.stringify(cart));
                displayCartItems();
                updateCartCount();
            });
        });
        
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                cart.splice(index, 1);
                localStorage.setItem('iceCreamCart', JSON.stringify(cart));
                displayCartItems();
                updateCartCount();
            });
        });
    }
    
    // Update cart count in the navbar
    function updateCartCount() {
        const cartCountElement = document.getElementById('cart-count');
        const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
        cartCountElement.textContent = totalItems;
    }
    
    // Show notification
    function showNotification(message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '1050';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Add to document
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 150);
        }, 3000);
    }
});
