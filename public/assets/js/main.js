


    $(document).ready(function () {
		// Future JavaScript functions can be added here
console.log("AleppoGift JS loaded.");

        // Add to cart
        $(document).on('submit', '.add-to-cart-form', function (e) {
            e.preventDefault();
            const form = $(this);
            const button = form.find('button');
            const originalText = button.html();
            
            // Show loading state
            button.html('<i class="fas fa-spinner fa-spin me-2"></i>Adding...');
            button.prop('disabled', true);
            
            $.post('ajax/add_to_cart.php', form.serialize(), function (res) {
                if (res.success) {
                    $('#cart-count').text(res.count);
                    $('#cart-count-toggle').text(res.count);
                    // Update cart preview
                    $('#cartPreview').load('ajax/cart_preview.php');
                    
                    // Show toast notification
                    if (typeof cartToast !== 'undefined') {
                        cartToast.show();
                    }
                    
                    // Update button to show success briefly
                    button.html('<i class="fas fa-check me-2"></i>Added!');
                    setTimeout(() => {
                        button.html(originalText);
                        button.prop('disabled', false);
                    }, 1500);
                } else {
                    alert('Error adding to cart: ' + res.message);
                    button.html(originalText);
                    button.prop('disabled', false);
                }
            }, 'json').fail(function() {
                alert('Network error. Please try again.');
                button.html(originalText);
                button.prop('disabled', false);
            });
        });

        // Remove from cart
        $(document).on('click', '.remove-item', function () {
            const id = $(this).data('id');
            $.post('ajax/remove_from_cart.php', { product_id: id }, function (res) {
                if (res.success) {
                    $('#cart-count').text(res.count);
                    $('#cart-count-toggle').text(res.count);
                    $('#cartPreview').load('ajax/cart_preview.php');
                }
            }, 'json');
        });

        // Update quantity
        $(document).on('click', '.update-qty', function () {
            const id = $(this).data('id');
            const action = $(this).data('action');
            $.post('ajax/update_cart_qty.php', { product_id: id, action: action }, function (res) {
                if (res.success) {
                    $('#cart-count').text(res.count);
                    $('#cart-count-toggle').text(res.count);
                    $('#cartPreview').load('ajax/cart_preview.php');
                }
            }, 'json');
        });

   // Reset button functionality
    $('.btn-reset').on('click', function(e) {
        e.preventDefault();
        
        // Clear form fields
        $('#search').val('');
        $('#category').val('');
        $('#brand').val('');
        
        // Submit the empty form
        $(this).closest('form').submit();
    });


            document.getElementById('search-toggle').addEventListener('click', function() {
            document.getElementById('search-bar').classList.remove('d-none');
            document.querySelector('#search-bar input').focus();
                });

                document.getElementById('search-close').addEventListener('click', function() {
                    document.getElementById('search-bar').classList.add('d-none');
                });

    });


		function toggleCart() {
			const preview = document.getElementById('cartPreview');
			if (!preview) {
				console.error('⚠️ cartPreview element not found');
				return;
			}

			const isHidden = preview.style.display === 'none' || preview.style.display === '';
			preview.style.display = isHidden ? 'block' : 'none';

			// Load cart preview content if showing
			if (isHidden) {
				fetch('ajax/cart_preview.php')
					.then(res => {
						if (!res.ok) {
							throw new Error(`HTTP error! status: ${res.status}`);
						}
						return res.text();
					})
					.then(html => {
						const cartItems = document.getElementById('cart-items-preview');
						if (cartItems) {
							cartItems.innerHTML = html;
						} else {
							console.error('⚠️ cart-items-preview element not found');
						}
					})
					.catch(err => {
						console.error('❌ Failed to load cart preview:', err);
						// Optionally show an error message to the user
						const cartItems = document.getElementById('cart-items-preview');
						if (cartItems) {
							cartItems.innerHTML = '<p class="text-danger">Error loading cart contents</p>';
						}
					});
			}
		}


    // Close cart when clicking outside
    document.addEventListener('click', function(event) {
        const cartPreview = document.getElementById('cartPreview');
        const cartButton = document.querySelector('[onclick="toggleCart()"]');
        
        if (cartPreview.style.display === 'block' && 
            !cartPreview.contains(event.target) && 
            !cartButton.contains(event.target)) {
            cartPreview.style.display = 'none';
        }
    });
    
 
<!-- Add this JavaScript to your file -->

function shareToFacebook(text) {
    window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href) + '&quote=' + text, '_blank');
}

function shareToInstagram(text) {
    // Instagram doesn't have a direct share API, this will open in a new tab
    window.open('https://www.instagram.com/', '_blank');
}

function shareToTikTok(text) {
    window.open('https://www.tiktok.com/', '_blank');
}

function toggleShareMenu(button) {
    const shareMenu = button.closest('.share-container').querySelector('.share-menu');
    shareMenu.classList.toggle('show');
    
    // Close when clicking outside
    document.addEventListener('click', function(e) {
        if (!button.contains(e.target) && !shareMenu.contains(e.target)) {
            shareMenu.classList.remove('show');
        }
    }, { once: true });
}

// Share functions (implement these as needed)
function shareToFacebook(text) {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}&quote=${text}`, '_blank');
}

function shareToInstagram(text) {
    // Instagram doesn't have direct sharing, this would typically open the app
    alert('Copy this link to share on Instagram: ' + text);
}

function shareToTikTok(text) {
    // TikTok sharing implementation
    alert('Copy this link to share on TikTok: ' + text);
}
