// Gaming Hub - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // Mobile Navigation
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }
    
    // User Dropdown
    const userAvatar = document.querySelector('.user-avatar');
    const dropdown = document.querySelector('.dropdown');
    
    if (userAvatar) {
        userAvatar.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', () => {
            dropdown.classList.remove('show');
        });
    }
    
    // Navbar Scroll Effect
    const navbar = document.querySelector('.navbar');
    let lastScroll = 0;
    
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 100) {
            navbar.style.background = 'rgba(15, 15, 15, 0.98)';
            navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.5)';
        } else {
            navbar.style.background = 'rgba(15, 15, 15, 0.95)';
            navbar.style.boxShadow = 'none';
        }
        
        lastScroll = currentScroll;
    });
    
    // Wishlist Toggle
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const gameId = this.dataset.gameId;
            const icon = this.querySelector('i');
            
            try {
                const response = await fetch('api/wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ game_id: gameId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.classList.toggle('active');
                    icon.classList.toggle('fas');
                    icon.classList.toggle('far');
                    
                    // Show toast notification
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message || 'Please login first', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });
    
    // Rating System
    const ratingInputs = document.querySelectorAll('.rating-input i');
    let selectedRating = 0;
    
    ratingInputs.forEach((star, index) => {
        star.addEventListener('mouseover', () => {
            highlightStars(index + 1);
        });
        
        star.addEventListener('click', () => {
            selectedRating = index + 1;
            document.getElementById('rating-value').value = selectedRating;
            highlightStars(selectedRating);
        });
    });
    
    document.querySelector('.rating-input')?.addEventListener('mouseleave', () => {
        highlightStars(selectedRating);
    });
    
    function highlightStars(count) {
        ratingInputs.forEach((star, index) => {
            if (index < count) {
                star.classList.add('active');
                star.classList.remove('far');
                star.classList.add('fas');
            } else {
                star.classList.remove('active');
                star.classList.remove('fas');
                star.classList.add('far');
            }
        });
    }
    
    // Review Form
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('api/review.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Review submitted successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    }
    
    // Download Tracking
    document.querySelectorAll('.download-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            const gameId = this.dataset.gameId;
            
            try {
                await fetch('api/download.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ game_id: gameId })
                });
            } catch (error) {
                console.error('Error tracking download:', error);
            }
        });
    });
    
    // Lightbox
    const lightbox = document.querySelector('.lightbox');
    const lightboxImg = document.querySelector('.lightbox img');
    const lightboxClose = document.querySelector('.lightbox-close');
    
    document.querySelectorAll('.screenshot-item').forEach(item => {
        item.addEventListener('click', () => {
            lightboxImg.src = item.querySelector('img').src;
            lightbox.classList.add('active');
        });
    });
    
    if (lightboxClose) {
        lightboxClose.addEventListener('click', () => {
            lightbox.classList.remove('active');
        });
    }
    
    if (lightbox) {
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                lightbox.classList.remove('active');
            }
        });
    }
    
    // Tabs
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.dataset.tab;
            
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            btn.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Filter Games
    const filterBtns = document.querySelectorAll('.filter-btn');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const category = btn.dataset.category;
            filterGames(category);
        });
    });
    
    async function filterGames(category) {
        const gamesGrid = document.querySelector('.games-grid');
        if (!gamesGrid) return;
        
        gamesGrid.style.opacity = '0.5';
        
        try {
            const response = await fetch(`api/games.php?category=${category}&ajax=1`);
            const html = await response.text();
            
            gamesGrid.innerHTML = html;
            gamesGrid.style.opacity = '1';
            
            // Re-attach wishlist events
            attachWishlistEvents();
        } catch (error) {
            console.error('Error filtering games:', error);
        }
    }
    
    // Search Suggestions
    const searchInput = document.querySelector('.search-box input');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value;
            
            if (query.length < 2) {
                document.querySelector('.search-suggestions')?.remove();
                return;
            }
            
            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    
                    showSearchSuggestions(data);
                } catch (error) {
                    console.error('Search error:', error);
                }
            }, 300);
        });
    }
    
    function showSearchSuggestions(games) {
        let suggestions = document.querySelector('.search-suggestions');
        if (!suggestions) {
            suggestions = document.createElement('div');
            suggestions.className = 'search-suggestions';
            suggestions.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #1A1A1A;
                border: 1px solid #333;
                border-radius: 15px;
                margin-top: 10px;
                max-height: 300px;
                overflow-y: auto;
                z-index: 100;
            `;
            document.querySelector('.search-box').appendChild(suggestions);
        }
        
        if (games.length === 0) {
            suggestions.innerHTML = '<div style="padding: 15px; color: #666;">No games found</div>';
            return;
        }
        
        suggestions.innerHTML = games.map(game => `
            <a href="game-details.php?slug=${game.slug}" style="
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 12px 15px;
                border-bottom: 1px solid #333;
                color: #fff;
                transition: all 0.3s;
            " onmouseover="this.style.background='rgba(0,212,255,0.1)'" 
            onmouseout="this.style.background='transparent'">
                <img src="uploads/games/${game.cover_image}" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
                <div>
                    <div style="font-weight: 600;">${game.title}</div>
                    <div style="font-size: 0.85rem; color: #00D4FF;">${game.category_name}</div>
                </div>
            </a>
        `).join('');
    }
    
    // Toast Notification
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 90px;
            right: 30px;
            padding: 15px 25px;
            border-radius: 10px;
            font-weight: 600;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            max-width: 350px;
        `;
        
        const colors = {
            success: 'background: rgba(57, 255, 20, 0.15); border: 1px solid #39FF14; color: #39FF14;',
            error: 'background: rgba(255, 0, 110, 0.15); border: 1px solid #FF006E; color: #FF006E;',
            info: 'background: rgba(0, 212, 255, 0.15); border: 1px solid #00D4FF; color: #00D4FF;'
        };
        
        toast.style.cssText += colors[type] || colors.info;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Intersection Observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.game-card, .news-card, .category-card, .section-header').forEach(el => {
        el.style.opacity = '0';
        observer.observe(el);
    });
    
    // Add CSS animations dynamically
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    
    // Form Validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#FF006E';
                    
                    setTimeout(() => {
                        field.style.borderColor = '';
                    }, 3000);
                }
            });
            
            if (!valid) {
                e.preventDefault();
                showToast('Please fill in all required fields', 'error');
            }
        });
    });
    
    // Lazy Loading Images
    const lazyImages = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    lazyImages.forEach(img => imageObserver.observe(img));
    
    // Admin Sidebar Toggle
    const adminToggle = document.querySelector('.admin-toggle');
    const adminSidebar = document.querySelector('.admin-sidebar');
    
    if (adminToggle && adminSidebar) {
        adminToggle.addEventListener('click', () => {
            adminSidebar.classList.toggle('open');
        });
    }
    
    // Confirm Delete
    document.querySelectorAll('.confirm-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
    
});

// Helper to re-attach wishlist events after AJAX
function attachWishlistEvents() {
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const gameId = this.dataset.gameId;
            
            try {
                const response = await fetch('api/wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ game_id: gameId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.classList.toggle('active');
                    showToast(data.message, 'success');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });
}