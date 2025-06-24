/**
 * Image Helper for Food Menu Management System
 * Provides utilities for handling food images
 */

class ImageHelper {
    constructor() {
        this.foodImages = [
            // Appetizers
            { name: 'Buffalo Wings', url: 'https://images.unsplash.com/photo-1527477396000-e27163b481c2?w=400&h=300&fit=crop&crop=center', category: 'Appetizers' },
            { name: 'Garlic Bread', url: 'https://images.unsplash.com/photo-1573140247632-f8fd74997d5c?w=400&h=300&fit=crop&crop=center', category: 'Appetizers' },
            { name: 'Mozzarella Sticks', url: 'https://images.unsplash.com/photo-1548340748-6d2b7d7da280?w=400&h=300&fit=crop&crop=center', category: 'Appetizers' },
            { name: 'Nachos', url: 'https://images.unsplash.com/photo-1513456852971-30c0b8199d4d?w=400&h=300&fit=crop&crop=center', category: 'Appetizers' },
            { name: 'Spring Rolls', url: 'https://images.unsplash.com/photo-1544982503-9f984c14501a?w=400&h=300&fit=crop&crop=center', category: 'Appetizers' },
            
            // Main Courses
            { name: 'Grilled Chicken', url: 'https://images.unsplash.com/photo-1532550907401-a500c9a57435?w=400&h=300&fit=crop&crop=center', category: 'Main Courses' },
            { name: 'Beef Burger', url: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&h=300&fit=crop&crop=center', category: 'Main Courses' },
            { name: 'Margherita Pizza', url: 'https://images.unsplash.com/photo-1604382354936-07c5d9983bd3?w=400&h=300&fit=crop&crop=center', category: 'Main Courses' },
            { name: 'Pasta Carbonara', url: 'https://images.unsplash.com/photo-1588013273468-315900bafd4d?w=400&h=300&fit=crop&crop=center', category: 'Main Courses' },
            { name: 'Fish and Chips', url: 'https://images.unsplash.com/photo-1544982503-9f984c14501a?w=400&h=300&fit=crop&crop=center', category: 'Main Courses' },
            { name: 'Steak', url: 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=400&h=300&fit=crop&crop=center', category: 'Main Courses' },
            { name: 'Salmon', url: 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=400&h=300&fit=crop&crop=center', category: 'Main Courses' },
            
            // Salads
            { name: 'Caesar Salad', url: 'https://images.unsplash.com/photo-1546793665-c74683f339c1?w=400&h=300&fit=crop&crop=center', category: 'Salads' },
            { name: 'Greek Salad', url: 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&h=300&fit=crop&crop=center', category: 'Salads' },
            { name: 'Garden Salad', url: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400&h=300&fit=crop&crop=center', category: 'Salads' },
            { name: 'Caprese Salad', url: 'https://images.unsplash.com/photo-1515543237350-b3eea1ec8082?w=400&h=300&fit=crop&crop=center', category: 'Salads' },
            
            // Desserts
            { name: 'Chocolate Cake', url: 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400&h=300&fit=crop&crop=center', category: 'Desserts' },
            { name: 'Tiramisu', url: 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?w=400&h=300&fit=crop&crop=center', category: 'Desserts' },
            { name: 'Cheesecake', url: 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=400&h=300&fit=crop&crop=center', category: 'Desserts' },
            { name: 'Ice Cream', url: 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&h=300&fit=crop&crop=center', category: 'Desserts' },
            { name: 'Apple Pie', url: 'https://images.unsplash.com/photo-1535920527002-b35e96722da9?w=400&h=300&fit=crop&crop=center', category: 'Desserts' },
            
            // Beverages
            { name: 'Orange Juice', url: 'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?w=400&h=300&fit=crop&crop=center', category: 'Beverages' },
            { name: 'Iced Coffee', url: 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=400&h=300&fit=crop&crop=center', category: 'Beverages' },
            { name: 'Green Smoothie', url: 'https://images.unsplash.com/photo-1610970881699-44a5587cabec?w=400&h=300&fit=crop&crop=center', category: 'Beverages' },
            { name: 'Lemonade', url: 'https://images.unsplash.com/photo-1523371683702-1b26d014b2b1?w=400&h=300&fit=crop&crop=center', category: 'Beverages' },
            { name: 'Hot Chocolate', url: 'https://images.unsplash.com/photo-1542990253-0b8be4b5be8c?w=400&h=300&fit=crop&crop=center', category: 'Beverages' },
            
            // Soups
            { name: 'Tomato Soup', url: 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=400&h=300&fit=crop&crop=center', category: 'Soups' },
            { name: 'Mushroom Soup', url: 'https://images.unsplash.com/photo-1547592180-85f173990554?w=400&h=300&fit=crop&crop=center', category: 'Soups' },
            { name: 'Chicken Noodle Soup', url: 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=400&h=300&fit=crop&crop=center', category: 'Soups' },
            { name: 'Vegetable Soup', url: 'https://images.unsplash.com/photo-1547592180-85f173990554?w=400&h=300&fit=crop&crop=center', category: 'Soups' }
        ];
    }

    /**
     * Get suggested images based on food name or category
     */
    getSuggestedImages(foodName, category = null) {
        const suggestions = [];
        
        // First, try to find exact or partial matches by name
        const nameMatches = this.foodImages.filter(img => 
            img.name.toLowerCase().includes(foodName.toLowerCase()) ||
            foodName.toLowerCase().includes(img.name.toLowerCase())
        );
        
        suggestions.push(...nameMatches);
        
        // Then, add category matches if category is provided
        if (category) {
            const categoryMatches = this.foodImages.filter(img => 
                img.category === category && !suggestions.includes(img)
            );
            suggestions.push(...categoryMatches.slice(0, 3)); // Limit to 3 category suggestions
        }
        
        // If no matches found, return random suggestions from the same category or general suggestions
        if (suggestions.length === 0) {
            if (category) {
                const categoryImages = this.foodImages.filter(img => img.category === category);
                suggestions.push(...categoryImages.slice(0, 5));
            } else {
                suggestions.push(...this.foodImages.slice(0, 5));
            }
        }
        
        return suggestions.slice(0, 6); // Return max 6 suggestions
    }

    /**
     * Create image suggestion modal
     */
    createImageSuggestionModal() {
        const modal = document.createElement('div');
        modal.id = 'image-suggestion-modal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Choose Food Image</h3>
                    <span class="close" id="close-image-modal">&times;</span>
                </div>
                <div class="image-suggestions">
                    <div class="suggestion-grid" id="suggestion-grid">
                        <!-- Image suggestions will be loaded here -->
                    </div>
                    <div class="custom-url-section">
                        <h4>Or enter custom image URL:</h4>
                        <input type="url" id="custom-image-url" placeholder="https://example.com/image.jpg">
                        <button type="button" class="btn btn-secondary" id="use-custom-url">Use Custom URL</button>
                    </div>
                </div>
            </div>
        `;
        
        return modal;
    }

    /**
     * Show image suggestion modal
     */
    showImageSuggestions(foodName, category, callback) {
        let modal = document.getElementById('image-suggestion-modal');
        
        if (!modal) {
            modal = this.createImageSuggestionModal();
            document.body.appendChild(modal);
        }
        
        const suggestions = this.getSuggestedImages(foodName, category);
        const grid = modal.querySelector('#suggestion-grid');
        
        grid.innerHTML = suggestions.map(img => `
            <div class="suggestion-item" data-url="${img.url}">
                <img src="${img.url}" alt="${img.name}" loading="lazy">
                <p>${img.name}</p>
            </div>
        `).join('');
        
        // Add event listeners
        modal.querySelector('#close-image-modal').onclick = () => {
            modal.classList.remove('active');
        };
        
        modal.querySelector('#use-custom-url').onclick = () => {
            const customUrl = modal.querySelector('#custom-image-url').value;
            if (customUrl) {
                callback(customUrl);
                modal.classList.remove('active');
            }
        };
        
        // Add click listeners to suggestion items
        grid.querySelectorAll('.suggestion-item').forEach(item => {
            item.onclick = () => {
                const url = item.getAttribute('data-url');
                callback(url);
                modal.classList.remove('active');
            };
        });
        
        modal.classList.add('active');
    }

    /**
     * Validate image URL
     */
    validateImageUrl(url) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(true);
            img.onerror = () => resolve(false);
            img.src = url;
        });
    }

    /**
     * Get optimized image URL (for performance)
     */
    getOptimizedImageUrl(url, width = 400, height = 300) {
        // If it's an Unsplash URL, add optimization parameters
        if (url.includes('unsplash.com')) {
            const baseUrl = url.split('?')[0];
            return `${baseUrl}?w=${width}&h=${height}&fit=crop&crop=center&auto=format&q=80`;
        }
        
        return url;
    }

    /**
     * Create image preview
     */
    createImagePreview(url, container) {
        const preview = document.createElement('div');
        preview.className = 'image-preview';
        preview.innerHTML = `
            <img src="${url}" alt="Preview" class="preview-image">
            <button type="button" class="btn btn-secondary btn-sm change-image">
                <i class="fas fa-edit"></i> Change Image
            </button>
        `;
        
        container.appendChild(preview);
        
        return preview;
    }
}

// Export for use in other files
window.ImageHelper = ImageHelper;
