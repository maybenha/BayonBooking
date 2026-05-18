// ==========================================
// FILTER SECTION JAVASCRIPT
// File: assets/js/filter-script.js
// ==========================================

// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    loadSavedFilters();
    setupDateRestrictions();
    setupRealTimeValidation();
});

// Initialize all filter functionality
function initializeFilters() {
    const filterForm = document.querySelector('.filter-form');
    const resetBtn = document.querySelector('.btn-filter-reset');
    const filters = document.querySelectorAll('.filter-input');
    
    // Add event listeners to all filter inputs
    filters.forEach(filter => {
        if (filter.type !== 'date') {
            filter.addEventListener('change', function() {
                validateFilter(this);
            });
        }
        
        // Add tooltips
        addTooltip(filter);
    });
    
    // Form submission handler
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            applyFilters();
        });
    }
    
    // Reset button handler
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            resetAllFilters();
        });
    }
    
    // Add loading animation on form submit
    const submitBtn = document.querySelector('.btn-filter-submit');
    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            showLoading();
        });
    }
}

// Setup date restrictions (can't select past dates)
function setupDateRestrictions() {
    const checkIn = document.getElementById('check_in');
    const checkOut = document.getElementById('check_out');
    const today = new Date().toISOString().split('T')[0];
    
    if (checkIn) {
        checkIn.setAttribute('min', today);
        
        checkIn.addEventListener('change', function() {
            if (checkOut && this.value) {
                checkOut.setAttribute('min', this.value);
                
                // If checkout is before checkin, reset it
                if (checkOut.value && checkOut.value < this.value) {
                    checkOut.value = '';
                }
            }
        });
    }
    
    if (checkOut) {
        checkOut.setAttribute('min', today);
    }
}

// Setup real-time validation
function setupRealTimeValidation() {
    const maxPrice = document.getElementById('max_price');
    
    if (maxPrice) {
        maxPrice.addEventListener('input', function() {
            if (this.value < 0) {
                this.value = 0;
            }
            if (this.value > 10000) {
                this.value = 10000;
            }
        });
    }
}

// Validate individual filter
function validateFilter(filter) {
    const value = filter.value.trim();
    const filterId = filter.id;
    
    switch(filterId) {
        case 'max_price':
            if (value && (isNaN(value) || value < 0)) {
                showError(filter, 'Please enter a valid price');
                return false;
            } else {
                clearError(filter);
                return true;
            }
            break;
            
        case 'room_type':
        case 'bed_type':
        case 'capacity':
            if (value && value !== '') {
                clearError(filter);
                return true;
            }
            break;
            
        default:
            clearError(filter);
            return true;
    }
}

// Show error message
function showError(element, message) {
    element.classList.add('error');
    let errorMsg = element.parentElement.querySelector('.error-message');
    if (!errorMsg) {
        errorMsg = document.createElement('div');
        errorMsg.className = 'error-message';
        element.parentElement.appendChild(errorMsg);
    }
    errorMsg.textContent = message;
}

// Clear error message
function clearError(element) {
    element.classList.remove('error');
    const errorMsg = element.parentElement.querySelector('.error-message');
    if (errorMsg) {
        errorMsg.textContent = '';
    }
}

// Add tooltip to filter inputs
function addTooltip(filter) {
    const tooltips = {
        'room_type': 'Select the type of room you prefer',
        'max_price': 'Maximum price per night in USD',
        'bed_type': 'Choose your preferred bed configuration',
        'capacity': 'Number of guests staying',
        'check_in': 'Your arrival date',
        'check_out': 'Your departure date'
    };
    
    if (tooltips[filter.id]) {
        filter.setAttribute('data-tooltip', tooltips[filter.id]);
    }
}

// Apply filters
function applyFilters() {
    // Get all filter values
    const filters = {
        room_type: document.getElementById('room_type')?.value || '',
        max_price: document.getElementById('max_price')?.value || '',
        bed_type: document.getElementById('bed_type')?.value || '',
        capacity: document.getElementById('capacity')?.value || '',
        check_in: document.getElementById('check_in')?.value || '',
        check_out: document.getElementById('check_out')?.value || ''
    };
    
    // Validate dates
    if (filters.check_in && filters.check_out) {
        if (filters.check_out < filters.check_in) {
            showNotification('Check-out date cannot be before check-in date', 'error');
            return;
        }
    }
    
    // Save filters to localStorage
    saveFiltersToLocalStorage(filters);
    
    // Build URL with query parameters
    const queryString = buildQueryString(filters);
    
    // Apply filters to room cards (you can modify this based on your needs)
    filterRoomCards(filters);
    
    // Show success notification
    showNotification('Filters applied successfully!', 'success');
}

// Build query string from filters
function buildQueryString(filters) {
    const params = new URLSearchParams();
    
    for (const [key, value] of Object.entries(filters)) {
        if (value && value !== '') {
            params.append(key, value);
        }
    }
    
    return params.toString();
}

// Filter room cards based on criteria
function filterRoomCards(filters) {
    const roomCards = document.querySelectorAll('.room-card');
    let visibleCount = 0;
    
    roomCards.forEach(card => {
        let show = true;
        
        // Filter by room type (based on title)
        if (filters.room_type && show) {
            const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
            if (!title.includes(filters.room_type.toLowerCase())) {
                show = false;
            }
        }
        
        // Filter by max price
        if (filters.max_price && show) {
            const priceElement = card.querySelector('.price');
            if (priceElement) {
                const price = parseInt(priceElement.textContent.replace(/[^0-9]/g, ''));
                if (price > parseInt(filters.max_price)) {
                    show = false;
                }
            }
        }
        
        // Filter by bed type
        if (filters.bed_type && show) {
            const bedElement = card.querySelector('.bed-type');
            if (bedElement) {
                const bedText = bedElement.textContent.toLowerCase();
                if (!bedText.includes(filters.bed_type.toLowerCase())) {
                    show = false;
                }
            }
        }
        
        // Filter by capacity
        if (filters.capacity && show) {
            const capacityElement = card.querySelector('.capacity');
            if (capacityElement) {
                const capacity = parseInt(capacityElement.textContent.replace(/[^0-9]/g, ''));
                const filterCapacity = parseInt(filters.capacity);
                if (capacity < filterCapacity) {
                    show = false;
                }
            }
        }
        
        // Show/hide the card with animation
        if (show) {
            card.style.display = '';
            card.style.animation = 'fadeIn 0.5s ease';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show message if no rooms found
    showNoResultsMessage(visibleCount);
}

// Show message when no rooms match filters
function showNoResultsMessage(visibleCount) {
    const roomsContainer = document.querySelector('#rooms .row');
    let noResultsMsg = document.getElementById('no-results-message');
    
    if (visibleCount === 0) {
        if (!noResultsMsg && roomsContainer) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.id = 'no-results-message';
            noResultsMsg.className = 'col-12 text-center';
            noResultsMsg.innerHTML = `
                <div class="no-results-card">
                    <i class="fas fa-search fa-3x"></i>
                    <h3>No rooms found</h3>
                    <p>Please try adjusting your filters</p>
                    <button class="btn-filter-reset" onclick="resetAllFilters()">
                        <i class="fas fa-undo-alt"></i> Reset Filters
                    </button>
                </div>
            `;
            roomsContainer.appendChild(noResultsMsg);
        }
    } else {
        if (noResultsMsg) {
            noResultsMsg.remove();
        }
    }
}

// Save filters to localStorage
function saveFiltersToLocalStorage(filters) {
    localStorage.setItem('roomFilters', JSON.stringify(filters));
    localStorage.setItem('filterTimestamp', new Date().getTime());
}

// Load saved filters from localStorage
function loadSavedFilters() {
    const savedFilters = localStorage.getItem('roomFilters');
    const timestamp = localStorage.getItem('filterTimestamp');
    
    // Only load filters from last 24 hours
    if (savedFilters && timestamp && (new Date().getTime() - parseInt(timestamp) < 86400000)) {
        const filters = JSON.parse(savedFilters);
        
        // Populate form fields
        for (const [key, value] of Object.entries(filters)) {
            const element = document.getElementById(key);
            if (element && value) {
                element.value = value;
            }
        }
        
        // Optionally apply saved filters
        if (Object.values(filters).some(v => v)) {
            setTimeout(() => {
                applyFilters();
            }, 500);
        }
    }
}

// Reset all filters
function resetAllFilters() {
    // Clear all form inputs
    const filters = document.querySelectorAll('.filter-input');
    filters.forEach(filter => {
        filter.value = '';
        clearError(filter);
    });
    
    // Clear localStorage
    localStorage.removeItem('roomFilters');
    localStorage.removeItem('filterTimestamp');
    
    // Show all room cards
    const roomCards = document.querySelectorAll('.room-card');
    roomCards.forEach(card => {
        card.style.display = '';
        card.style.animation = 'fadeIn 0.5s ease';
    });
    
    // Remove no results message
    const noResultsMsg = document.getElementById('no-results-message');
    if (noResultsMsg) {
        noResultsMsg.remove();
    }
    
    // Show notification
    showNotification('All filters have been reset!', 'info');
    
    // Optional: Reload page to clear URL parameters
    if (window.location.search) {
        window.location.href = window.location.pathname;
    }
}

// Show loading animation
function showLoading() {
    const filterCard = document.querySelector('.filter-card');
    if (filterCard) {
        filterCard.classList.add('filter-loading');
        setTimeout(() => {
            filterCard.classList.remove('filter-loading');
        }, 1000);
    }
}

// Show notification
function showNotification(message, type = 'success') {
    // Create notification element if it doesn't exist
    let notification = document.getElementById('filter-notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'filter-notification';
        document.body.appendChild(notification);
        
        // Add styles for notification
        const style = document.createElement('style');
        style.textContent = `
            #filter-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 10px;
                color: white;
                font-weight: 600;
                z-index: 9999;
                animation: slideInRight 0.3s ease;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            #filter-notification.success {
                background: linear-gradient(135deg, #28a745, #20c997);
            }
            
            #filter-notification.error {
                background: linear-gradient(135deg, #dc3545, #c82333);
            }
            
            #filter-notification.info {
                background: linear-gradient(135deg, #17a2b8, #138496);
            }
        `;
        document.head.appendChild(style);
    }
    
    // Set notification content and type
    notification.textContent = message;
    notification.className = type;
    notification.style.display = 'block';
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        notification.style.display = 'none';
    }, 3000);
}

// Export functions for global use
window.applyFilters = applyFilters;
window.resetAllFilters = resetAllFilters;
window.filterRoomCards = filterRoomCards;

// Add CSS animation for fadeIn
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);