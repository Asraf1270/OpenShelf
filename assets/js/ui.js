/**
 * OpenShelf UI System
 * Vanilla JavaScript for interactive components
 */

class OpenShelfUI {
    constructor() {
        this.init();
    }
    
    init() {
        this.initMobileMenu();
        this.initDropdowns();
        this.initNotifications();
        this.initModals();
        this.initAlerts();
        this.initForms();
        this.initTabs();
    }
    
    /**
     * Mobile menu toggle
     */
    initMobileMenu() {
        const toggle = document.getElementById('navbarToggle');
        const menu = document.getElementById('navbarMenu');
        
        if (toggle && menu) {
            toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                menu.classList.toggle('show');
                toggle.classList.toggle('active');
                
                // Toggle icon
                const icon = toggle.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-bars');
                    icon.classList.toggle('fa-times');
                }
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!menu.contains(e.target) && !toggle.contains(e.target)) {
                    menu.classList.remove('show');
                    toggle.classList.remove('active');
                    const icon = toggle.querySelector('i');
                    if (icon) {
                        icon.classList.add('fa-bars');
                        icon.classList.remove('fa-times');
                    }
                }
            });
        }
    }
    
    /**
     * Dropdown menus
     */
    initDropdowns() {
        const triggers = document.querySelectorAll('[data-dropdown-trigger]');
        
        triggers.forEach(trigger => {
            const targetId = trigger.dataset.dropdownTarget;
            const dropdown = document.getElementById(targetId);
            
            if (dropdown) {
                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    
                    // Close other dropdowns
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        if (menu !== dropdown) {
                            menu.classList.remove('show');
                        }
                    });
                    
                    dropdown.classList.toggle('show');
                });
            }
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', () => {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
        });
    }
    
    /**
     * Notification system
     */
    initNotifications() {
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        if (notificationBtn && notificationDropdown) {
            notificationBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
                
                // Mark notifications as read when opened
                if (notificationDropdown.classList.contains('show')) {
                    this.markNotificationsAsRead();
                }
            });
        }
        
        // Load notifications
        this.loadNotifications();
    }
    
    loadNotifications() {
        // This would normally fetch from an API
        // For demo, we'll use mock data
        const mockNotifications = [
            {
                id: 1,
                type: 'borrow_request',
                title: 'New Borrow Request',
                message: 'John wants to borrow "The Great Gatsby"',
                time: '5 min ago',
                read: false,
                icon: 'fa-hand-holding-heart'
            },
            {
                id: 2,
                type: 'request_approved',
                title: 'Request Approved',
                message: 'Your request for "Clean Code" was approved',
                time: '1 hour ago',
                read: false,
                icon: 'fa-check-circle'
            },
            {
                id: 3,
                type: 'return_reminder',
                title: 'Return Reminder',
                message: 'Please return "Dune" by tomorrow',
                time: '2 hours ago',
                read: true,
                icon: 'fa-clock'
            }
        ];
        
        this.renderNotifications(mockNotifications);
        this.updateNotificationBadge(mockNotifications.filter(n => !n.read).length);
    }
    
    renderNotifications(notifications) {
        const list = document.getElementById('notificationList');
        if (!list) return;
        
        if (notifications.length === 0) {
            list.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>No notifications</p>
                </div>
            `;
            return;
        }
        
        list.innerHTML = notifications.map(notification => `
            <div class="notification-item ${notification.read ? '' : 'unread'}" data-id="${notification.id}">
                <div class="notification-icon" style="background-color: var(--primary);">
                    <i class="fas ${notification.icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">
                        <i class="far fa-clock"></i> ${notification.time}
                    </div>
                </div>
            </div>
        `).join('');
        
        // Add click handlers
        list.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', () => {
                this.handleNotificationClick(item.dataset.id);
            });
        });
    }
    
    updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    markNotificationsAsRead() {
        // This would call an API to mark notifications as read
        document.querySelectorAll('.notification-item.unread').forEach(item => {
            item.classList.remove('unread');
        });
        this.updateNotificationBadge(0);
    }
    
    handleNotificationClick(id) {
        console.log('Notification clicked:', id);
        // Navigate to notification detail or mark as read
        window.location.href = `/notifications?id=${id}`;
    }
    
    /**
     * Modal system
     */
    initModals() {
        // Open modal buttons
        document.querySelectorAll('[data-modal-target]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = btn.dataset.modalTarget;
                this.openModal(modalId);
            });
        });
        
        // Close modal buttons
        document.querySelectorAll('[data-modal-close]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const modal = btn.closest('.modal');
                if (modal) {
                    this.closeModal(modal.id);
                }
            });
        });
        
        // Close on overlay click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal.id);
                }
            });
        });
        
        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    this.closeModal(modal.id);
                });
            }
        });
    }
    
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    /**
     * Alert system
     */
    initAlerts() {
        document.querySelectorAll('.alert .alert-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const alert = btn.closest('.alert');
                if (alert) {
                    this.closeAlert(alert);
                }
            });
        });
    }
    
    closeAlert(alert) {
        alert.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            alert.remove();
        }, 300);
    }
    
    showAlert(message, type = 'info', title = '') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        
        let icon = 'fa-info-circle';
        if (type === 'success') icon = 'fa-check-circle';
        if (type === 'warning') icon = 'fa-exclamation-triangle';
        if (type === 'danger') icon = 'fa-exclamation-circle';
        
        alert.innerHTML = `
            <i class="fas ${icon}"></i>
            <div class="alert-content">
                ${title ? `<div class="alert-title">${title}</div>` : ''}
                <div class="alert-message">${message}</div>
            </div>
            <button class="alert-close">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        const container = document.getElementById('alertContainer') || document.body;
        container.appendChild(alert);
        
        // Add close handler
        alert.querySelector('.alert-close').addEventListener('click', () => {
            this.closeAlert(alert);
        });
        
        // Auto close after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                this.closeAlert(alert);
            }
        }, 5000);
        
        return alert;
    }
    
    /**
     * Form validation
     */
    initForms() {
        document.querySelectorAll('form[data-validate]').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
            
            // Real-time validation on blur
            form.querySelectorAll('[data-validate]').forEach(input => {
                input.addEventListener('blur', () => {
                    this.validateField(input);
                });
                
                input.addEventListener('input', () => {
                    this.clearFieldError(input);
                });
            });
        });
    }
    
    validateForm(form) {
        let isValid = true;
        
        form.querySelectorAll('[data-validate]').forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    validateField(field) {
        const value = field.value.trim();
        const rules = field.dataset.validate.split(' ');
        let isValid = true;
        let errorMessage = '';
        
        for (const rule of rules) {
            if (rule === 'required' && !value) {
                isValid = false;
                errorMessage = 'This field is required';
                break;
            }
            
            if (rule === 'email' && value && !this.isValidEmail(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
                break;
            }
            
            if (rule === 'phone' && value && !this.isValidPhone(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid phone number';
                break;
            }
            
            if (rule.startsWith('min:')) {
                const min = parseInt(rule.split(':')[1]);
                if (value && value.length < min) {
                    isValid = false;
                    errorMessage = `Minimum ${min} characters required`;
                    break;
                }
            }
            
            if (rule.startsWith('max:')) {
                const max = parseInt(rule.split(':')[1]);
                if (value && value.length > max) {
                    isValid = false;
                    errorMessage = `Maximum ${max} characters allowed`;
                    break;
                }
            }
        }
        
        if (!isValid) {
            this.showFieldError(field, errorMessage);
        } else {
            this.clearFieldError(field);
        }
        
        return isValid;
    }
    
    showFieldError(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        
        const formGroup = field.closest('.form-group');
        if (formGroup) {
            let error = formGroup.querySelector('.form-error');
            if (!error) {
                error = document.createElement('div');
                error.className = 'form-error';
                formGroup.appendChild(error);
            }
            error.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        }
    }
    
    clearFieldError(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        
        const formGroup = field.closest('.form-group');
        if (formGroup) {
            const error = formGroup.querySelector('.form-error');
            if (error) {
                error.remove();
            }
        }
    }
    
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    isValidPhone(phone) {
        return /^[\d\s\-+()]{10,}$/.test(phone);
    }
    
    /**
     * Tab system
     */
    initTabs() {
        document.querySelectorAll('[data-tab-group]').forEach(group => {
            const tabs = group.querySelectorAll('[data-tab]');
            const panes = group.querySelectorAll('[data-tab-pane]');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    const tabId = tab.dataset.tab;
                    
                    // Update tabs
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    
                    // Update panes
                    panes.forEach(pane => {
                        if (pane.dataset.tabPane === tabId) {
                            pane.classList.add('active');
                        } else {
                            pane.classList.remove('active');
                        }
                    });
                });
            });
        });
    }
    
    /**
     * Utility functions
     */
    formatTimeAgo(timestamp) {
        const now = new Date();
        const past = new Date(timestamp);
        const diff = Math.floor((now - past) / 1000);
        
        if (diff < 60) return 'just now';
        if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`;
        if (diff < 604800) return `${Math.floor(diff / 86400)} days ago`;
        
        return past.toLocaleDateString();
    }
    
    formatDate(date) {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.openshelf = new OpenShelfUI();
});