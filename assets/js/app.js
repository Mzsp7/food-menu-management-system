
// Food Menu Management System - Frontend JavaScript
class MenuApp {
    constructor() {
        this.currentUser = null;
        this.currentPage = 'login';
        this.menuItems = [];
        this.categories = [];
        this.users = [];
        this.imageHelper = new ImageHelper();

        this.init();
    }

    init() {
        this.bindEvents();
        this.checkAuthStatus();
        this.setupNavigation();
    }

    // Event Binding
    bindEvents() {
        // Navigation
        document.getElementById('nav-toggle').addEventListener('click', this.toggleMobileNav);
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => this.handleNavigation(e));
        });

        // Authentication
        document.getElementById('login-form').addEventListener('submit', (e) => this.handleLogin(e));
        document.getElementById('register-form').addEventListener('submit', (e) => this.handleRegister(e));
        document.getElementById('show-register').addEventListener('click', (e) => this.showRegister(e));
        document.getElementById('show-login').addEventListener('click', (e) => this.showLogin(e));
        document.getElementById('logout-btn').addEventListener('click', (e) => this.handleLogout(e));

        // Menu Items
        document.getElementById('add-item-btn').addEventListener('click', () => this.showItemModal());
        document.getElementById('item-form').addEventListener('submit', (e) => this.handleItemSubmit(e));
        document.getElementById('close-item-modal').addEventListener('click', () => this.hideItemModal());
        document.getElementById('cancel-item').addEventListener('click', () => this.hideItemModal());

        // Categories
        document.getElementById('add-category-btn').addEventListener('click', () => this.showCategoryModal());
        document.getElementById('category-form').addEventListener('submit', (e) => this.handleCategorySubmit(e));
        document.getElementById('close-category-modal').addEventListener('click', () => this.hideCategoryModal());
        document.getElementById('cancel-category').addEventListener('click', () => this.hideCategoryModal());

        // Search and Filter
        document.getElementById('search-input').addEventListener('input', (e) => this.handleSearch(e));
        document.getElementById('category-filter').addEventListener('change', (e) => this.handleFilter(e));
        document.getElementById('sort-options').addEventListener('change', (e) => this.handleSort(e));

        // Image suggestion functionality
        document.getElementById('suggest-image-btn').addEventListener('click', () => this.showImageSuggestions());
        document.getElementById('item-image').addEventListener('input', (e) => this.updateImagePreview(e.target.value));

        // Modal close on outside click
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.hideAllModals();
            }
        });
    }

    // Authentication Methods
    async checkAuthStatus() {
        try {
            const response = await this.apiCall('auth/check.php');
            if (response.success) {
                this.currentUser = response.user;
                this.showDashboard();
                this.updateUserInterface();
            } else {
                this.showLogin();
            }
        } catch (error) {
            this.showLogin();
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            this.showLoading();
            const response = await this.apiCall('auth/login.php', {
                method: 'POST',
                body: formData
            });

            if (response.success) {
                this.currentUser = response.user;
                this.showToast('Login successful!', 'success');
                this.showDashboard();
                this.updateUserInterface();
            } else {
                this.showToast(response.message || 'Login failed', 'error');
            }
        } catch (error) {
            this.showToast('Login error: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    async handleRegister(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        // Validate password confirmation
        if (formData.get('password') !== formData.get('confirm_password')) {
            this.showToast('Passwords do not match', 'error');
            return;
        }

        try {
            this.showLoading();
            const response = await this.apiCall('auth/register.php', {
                method: 'POST',
                body: formData
            });

            if (response.success) {
                this.showToast('Registration successful! Please login.', 'success');
                this.showLogin();
                document.getElementById('register-form').reset();
            } else {
                this.showToast(response.message || 'Registration failed', 'error');
            }
        } catch (error) {
            this.showToast('Registration error: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    async handleLogout(e) {
        e.preventDefault();
        try {
            await this.apiCall('auth/logout.php', { method: 'POST' });
            this.currentUser = null;
            this.showLogin();
            this.showToast('Logged out successfully', 'success');
        } catch (error) {
            this.showToast('Logout error: ' + error.message, 'error');
        }
    }

    // Navigation Methods
    setupNavigation() {
        // Set up single page application navigation
        this.showPage('login');
    }

    handleNavigation(e) {
        e.preventDefault();
        const page = e.target.getAttribute('data-page');
        if (page) {
            this.showPage(page);
        }
    }

    showPage(pageName) {
        // Hide all pages
        document.querySelectorAll('.page').forEach(page => {
            page.classList.remove('active');
        });

        // Show selected page
        const targetPage = document.getElementById(`${pageName}-page`);
        if (targetPage) {
            targetPage.classList.add('active');
            this.currentPage = pageName;

            // Update navigation active state
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            const activeLink = document.querySelector(`[data-page="${pageName}"]`);
            if (activeLink) {
                activeLink.classList.add('active');
            }

            // Load page data
            this.loadPageData(pageName);
        }
    }

    async loadPageData(pageName) {
        switch (pageName) {
            case 'dashboard':
                await this.loadDashboardData();
                break;
            case 'menu':
                await this.loadMenuItems();
                await this.loadCategories();
                break;
            case 'categories':
                await this.loadCategories();
                break;
            case 'users':
                if (this.currentUser && this.currentUser.role === 'admin') {
                    await this.loadUsers();
                }
                break;
        }
    }

    // Dashboard Methods
    async loadDashboardData() {
        try {
            const response = await this.apiCall('dashboard/stats.php');
            if (response.success) {
                const stats = response.data.stats;
                document.getElementById('total-items').textContent = stats.total_items || 0;
                document.getElementById('total-categories').textContent = stats.total_categories || 0;
                document.getElementById('total-users').textContent = stats.total_users || 0;
            }
        } catch (error) {
            console.error('Error loading dashboard data:', error);
        }
    }

    // Menu Items Methods
    async loadMenuItems() {
        try {
            this.showLoading();
            const response = await this.apiCall('menu/list.php');
            if (response.success) {
                this.menuItems = response.data || [];
                this.renderMenuItems();
            }
        } catch (error) {
            this.showToast('Error loading menu items: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    renderMenuItems(items = this.menuItems) {
        const grid = document.getElementById('menu-items-grid');
        
        if (items.length === 0) {
            grid.innerHTML = `
                <div class="no-items">
                    <i class="fas fa-utensils"></i>
                    <h3>No menu items found</h3>
                    <p>Start by adding your first menu item</p>
                </div>
            `;
            return;
        }

        grid.innerHTML = items.map(item => `
            <div class="item-card">
                ${item.image_url ?
                    `<img src="${item.image_url}" alt="${item.name}" class="item-image loading"
                          onload="this.classList.remove('loading')"
                          onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'; this.classList.remove('loading');">
                     <div class="no-image" style="display:none;"><i class="fas fa-image"></i></div>` :
                    `<div class="no-image"><i class="fas fa-image"></i></div>`
                }
                <div class="item-content">
                    <h3 class="item-title">${item.name}</h3>
                    <p class="item-description">${item.description || ''}</p>
                    <div class="item-price">₹${parseFloat(item.price).toFixed(2)}</div>
                    <div class="item-actions">
                        <button class="btn btn-secondary" onclick="app.editItem(${item.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-danger" onclick="app.deleteItem(${item.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Categories Methods
    async loadCategories() {
        try {
            const response = await this.apiCall('categories/list.php');
            if (response.success) {
                this.categories = response.data.categories || [];
                this.renderCategories();
                this.updateCategorySelects();
            }
        } catch (error) {
            this.showToast('Error loading categories: ' + error.message, 'error');
        }
    }

    renderCategories() {
        const container = document.getElementById('categories-list');
        
        if (this.categories.length === 0) {
            container.innerHTML = `
                <div class="no-items">
                    <i class="fas fa-tags"></i>
                    <h3>No categories found</h3>
                    <p>Start by adding your first category</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.categories.map(category => `
            <div class="category-card">
                <h3 class="category-title">${category.name}</h3>
                <p class="category-description">${category.description || ''}</p>
                <div class="category-actions">
                    <button class="btn btn-secondary" onclick="app.editCategory(${category.id})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-danger" onclick="app.deleteCategory(${category.id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        `).join('');
    }

    updateCategorySelects() {
        const selects = ['item-category', 'category-filter'];
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                const currentValue = select.value;
                const options = this.categories.map(cat => 
                    `<option value="${cat.id}">${cat.name}</option>`
                ).join('');
                
                if (selectId === 'category-filter') {
                    select.innerHTML = '<option value="">All Categories</option>' + options;
                } else {
                    select.innerHTML = '<option value="">Select Category</option>' + options;
                }
                
                select.value = currentValue;
            }
        });
    }

    // Utility Methods
    toggleMobileNav() {
        const navMenu = document.getElementById('nav-menu');
        navMenu.classList.toggle('active');
    }

    showLogin() {
        this.showPage('login');
        document.querySelector('.navbar').style.display = 'none';
    }

    showRegister(e) {
        e.preventDefault();
        this.showPage('register');
    }

    showDashboard() {
        document.querySelector('.navbar').style.display = 'block';
        this.showPage('dashboard');
    }

    updateUserInterface() {
        if (this.currentUser && this.currentUser.role === 'admin') {
            document.body.classList.add('admin');
        } else {
            document.body.classList.remove('admin');
        }
    }

    // API Helper
    async apiCall(endpoint, options = {}) {
        const url = `api/${endpoint}`;
        const defaultOptions = {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const response = await fetch(url, { ...defaultOptions, ...options });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }

    // UI Helper Methods
    showLoading() {
        document.getElementById('loading').classList.add('active');
    }

    hideLoading() {
        document.getElementById('loading').classList.remove('active');
    }

    showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    hideAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('active');
        });
    }

    showItemModal(item = null) {
        const modal = document.getElementById('item-modal');
        const title = document.getElementById('item-modal-title');
        const form = document.getElementById('item-form');
        
        if (item) {
            title.textContent = 'Edit Menu Item';
            this.populateItemForm(item);
        } else {
            title.textContent = 'Add Menu Item';
            form.reset();
            document.getElementById('item-id').value = '';
        }
        
        modal.classList.add('active');
    }

    hideItemModal() {
        document.getElementById('item-modal').classList.remove('active');
    }

    showCategoryModal(category = null) {
        const modal = document.getElementById('category-modal');
        const title = document.getElementById('category-modal-title');
        const form = document.getElementById('category-form');
        
        if (category) {
            title.textContent = 'Edit Category';
            this.populateCategoryForm(category);
        } else {
            title.textContent = 'Add Category';
            form.reset();
            document.getElementById('category-id').value = '';
        }
        
        modal.classList.add('active');
    }

    hideCategoryModal() {
        document.getElementById('category-modal').classList.remove('active');
    }

    // Form handling methods
    populateItemForm(item) {
        document.getElementById('item-id').value = item.id;
        document.getElementById('item-name').value = item.name;
        document.getElementById('item-description').value = item.description || '';
        document.getElementById('item-price').value = item.price;
        document.getElementById('item-category').value = item.category_id;
        document.getElementById('item-image').value = item.image_url || '';
    }

    populateCategoryForm(category) {
        document.getElementById('category-id').value = category.id;
        document.getElementById('category-name').value = category.name;
        document.getElementById('category-description').value = category.description || '';
    }

    async handleItemSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const itemId = formData.get('id');

        try {
            this.showLoading();
            const endpoint = itemId ? 'menu/update.php' : 'menu/create.php';
            const response = await this.apiCall(endpoint, {
                method: 'POST',
                body: formData
            });

            if (response.success) {
                this.showToast(itemId ? 'Item updated successfully!' : 'Item added successfully!', 'success');
                this.hideItemModal();
                await this.loadMenuItems();
                if (this.currentPage === 'dashboard') {
                    await this.loadDashboardData();
                }
            } else {
                this.showToast(response.message || 'Operation failed', 'error');
            }
        } catch (error) {
            this.showToast('Error: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    async handleCategorySubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const categoryId = formData.get('id');

        try {
            this.showLoading();
            const endpoint = categoryId ? 'categories/update.php' : 'categories/create.php';
            const response = await this.apiCall(endpoint, {
                method: 'POST',
                body: formData
            });

            if (response.success) {
                this.showToast(categoryId ? 'Category updated successfully!' : 'Category added successfully!', 'success');
                this.hideCategoryModal();
                await this.loadCategories();
                if (this.currentPage === 'dashboard') {
                    await this.loadDashboardData();
                }
            } else {
                this.showToast(response.message || 'Operation failed', 'error');
            }
        } catch (error) {
            this.showToast('Error: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    async editItem(itemId) {
        const item = this.menuItems.find(item => item.id == itemId);
        if (item) {
            this.showItemModal(item);
        }
    }

    async deleteItem(itemId) {
        if (!confirm('Are you sure you want to delete this item?')) {
            return;
        }

        try {
            this.showLoading();
            const response = await this.apiCall('menu/delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: itemId })
            });

            if (response.success) {
                this.showToast('Item deleted successfully!', 'success');
                await this.loadMenuItems();
                if (this.currentPage === 'dashboard') {
                    await this.loadDashboardData();
                }
            } else {
                this.showToast(response.message || 'Delete failed', 'error');
            }
        } catch (error) {
            this.showToast('Error: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    async editCategory(categoryId) {
        const category = this.categories.find(cat => cat.id == categoryId);
        if (category) {
            this.showCategoryModal(category);
        }
    }

    async deleteCategory(categoryId) {
        if (!confirm('Are you sure you want to delete this category? This will also remove it from all menu items.')) {
            return;
        }

        try {
            this.showLoading();
            const response = await this.apiCall('categories/delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: categoryId })
            });

            if (response.success) {
                this.showToast('Category deleted successfully!', 'success');
                await this.loadCategories();
                await this.loadMenuItems(); // Reload items to update category references
                if (this.currentPage === 'dashboard') {
                    await this.loadDashboardData();
                }
            } else {
                this.showToast(response.message || 'Delete failed', 'error');
            }
        } catch (error) {
            this.showToast('Error: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    // Search and Filter Methods
    handleSearch(e) {
        const searchTerm = e.target.value.toLowerCase();
        const filteredItems = this.menuItems.filter(item =>
            item.name.toLowerCase().includes(searchTerm) ||
            (item.description && item.description.toLowerCase().includes(searchTerm))
        );
        this.renderMenuItems(filteredItems);
    }

    handleFilter(e) {
        const categoryId = e.target.value;
        let filteredItems = this.menuItems;

        if (categoryId) {
            filteredItems = this.menuItems.filter(item => item.category_id == categoryId);
        }

        this.renderMenuItems(filteredItems);
    }

    handleSort(e) {
        const sortBy = e.target.value;
        let sortedItems = [...this.menuItems];

        switch (sortBy) {
            case 'name':
                sortedItems.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case 'price':
                sortedItems.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
                break;
            case 'category':
                sortedItems.sort((a, b) => {
                    const catA = this.categories.find(cat => cat.id == a.category_id)?.name || '';
                    const catB = this.categories.find(cat => cat.id == b.category_id)?.name || '';
                    return catA.localeCompare(catB);
                });
                break;
        }

        this.renderMenuItems(sortedItems);
    }

    // Users Management (Admin only)
    async loadUsers() {
        try {
            const response = await this.apiCall('users/list.php');
            if (response.success) {
                this.users = response.data || [];
                this.renderUsers();
            }
        } catch (error) {
            this.showToast('Error loading users: ' + error.message, 'error');
        }
    }

    renderUsers() {
        const container = document.getElementById('users-list');

        if (this.users.length === 0) {
            container.innerHTML = `
                <div class="no-items">
                    <i class="fas fa-users"></i>
                    <h3>No users found</h3>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${this.users.map(user => `
                        <tr>
                            <td>${user.username}</td>
                            <td>${user.email}</td>
                            <td><span class="badge badge-${user.role}">${user.role}</span></td>
                            <td>${new Date(user.created_at).toLocaleDateString()}</td>
                            <td>
                                <button class="btn btn-secondary" onclick="app.editUser(${user.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${user.id !== this.currentUser.id ? `
                                    <button class="btn btn-danger" onclick="app.deleteUser(${user.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                ` : ''}
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    async editUser(userId) {
        // Implementation for user editing would go here
        this.showToast('User editing feature coming soon!', 'warning');
    }

    async deleteUser(userId) {
        if (!confirm('Are you sure you want to delete this user?')) {
            return;
        }

        try {
            this.showLoading();
            const response = await this.apiCall('users/delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: userId })
            });

            if (response.success) {
                this.showToast('User deleted successfully!', 'success');
                await this.loadUsers();
                if (this.currentPage === 'dashboard') {
                    await this.loadDashboardData();
                }
            } else {
                this.showToast(response.message || 'Delete failed', 'error');
            }
        } catch (error) {
            this.showToast('Error: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    // Image handling methods
    showImageSuggestions() {
        const itemName = document.getElementById('item-name').value || 'food';
        const categorySelect = document.getElementById('item-category');
        const categoryName = categorySelect.options[categorySelect.selectedIndex]?.text || null;

        this.imageHelper.showImageSuggestions(itemName, categoryName, (imageUrl) => {
            document.getElementById('item-image').value = imageUrl;
            this.updateImagePreview(imageUrl);
        });
    }

    updateImagePreview(url) {
        const container = document.getElementById('image-preview-container');
        container.innerHTML = '';

        if (url) {
            const preview = this.imageHelper.createImagePreview(url, container);
            preview.querySelector('.change-image').addEventListener('click', () => {
                this.showImageSuggestions();
            });
        }
    }
}

// Initialize the application
const app = new MenuApp();