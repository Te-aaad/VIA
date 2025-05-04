<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Orders - Canteen Online Ordering System</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Iconify for icons -->
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#10B981',
                        accent: '#F59E0B',
                        light: '#F3F4F6',
                        dark: '#1F2937',
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        .message-popup {
            transition: opacity 0.5s ease-in-out;
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        /* Timer animation styles */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.6;
            }
        }
        
        .animate-pulse {
            animation: pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        .pickup-countdown {
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex">
       @include('includes.student-sidebar')

        <!-- Main Content -->
        <div class="ml-0 md:ml-64 w-full min-h-screen transition-all duration-300">
            <!-- Error Message (if any) -->
            @if(session('error'))
            <div class="p-5">
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl shadow-sm" role="alert">
                    <p class="font-bold">Error</p>
                    <p>{{ session('error') }}</p>
                </div>
            </div>
            @endif
            
            <!-- Success Message (if any) -->
            @if(session('success'))
            <div class="p-5">
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl shadow-sm" role="alert">
                    <p class="font-bold">Success</p>
                    <p>{{ session('success') }}</p>
                </div>
            </div>
            @endif
            
            <!-- Top Navigation Bar -->
            <div class="p-5">
                <div class="bg-white rounded-xl shadow-sm p-4 flex justify-between items-center">
                    <button id="sidebar-toggle" class="md:hidden text-primary text-2xl">
                        <iconify-icon icon="mdi:menu" width="28" height="28"></iconify-icon>
                    </button>
                    
                    <h1 class="text-xl font-semibold text-gray-800 mx-4">My Orders</h1>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('dashboard') }}" class="p-2 bg-gray-100 rounded-full hover:bg-gray-200 transition duration-200 hover:scale-110 group">
                            <iconify-icon icon="mdi:home" class="text-primary group-hover:text-accent" width="24" height="24"></iconify-icon>
                        </a>
                        <a href="#" class="p-2 bg-gray-100 rounded-full hover:bg-gray-200 transition duration-200 hover:scale-110 group">
                            <iconify-icon icon="mdi:account" class="text-primary group-hover:text-accent" width="24" height="24"></iconify-icon>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Orders Section -->
            <div class="px-5 py-3">
                <!-- Order Filters -->
                <div class="mb-6 flex flex-col md:flex-row md:justify-between items-start md:items-center gap-4">
                    <div class="flex items-center gap-2">
                        <button id="all-orders-btn" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition active">All Orders</button>
                        <button id="active-orders-btn" class="px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-100 transition">Active</button>
                        <button id="completed-orders-btn" class="px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-100 transition">Completed</button>
                        <button id="cancelled-orders-btn" class="px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-100 transition">Cancelled</button>
                    </div>
                    
                    <div class="relative w-full md:w-64">
                        <input type="text" id="search-orders" placeholder="Search orders..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <iconify-icon icon="mdi:magnify" class="text-gray-400" width="20" height="20"></iconify-icon>
                        </div>
                    </div>
                </div>
                
                <!-- Orders List -->
                <div class="space-y-5 order-list">
                    @if(count($orders) > 0)
                        @foreach($orders as $order)
                            <div class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100 order-item" 
                                data-status="{{ $order->status }}"
                                data-order-number="{{ $order->order_number }}"
                                data-seller="{{ $order->seller->stall_name ?? 'Seller #' . $order->seller_id }}">
                                <div class="flex flex-col md:flex-row">
                                    <!-- Order Basic Info -->
                                    <div class="p-5 flex-grow">
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <div class="flex items-center gap-3">
                                                    <h3 class="text-lg font-semibold">Order #{{ $order->order_number }}</h3>
                                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium
                                                        @if($order->status == 'cancelled') bg-red-100 text-red-800
                                                        @elseif($order->status == 'completed') bg-green-100 text-green-800
                                                        @elseif($order->status == 'ready') bg-blue-100 text-blue-800
                                                        @else bg-yellow-100 text-yellow-800
                                                        @endif">
                                                        {{ ucfirst($order->status) }}
                                                    </span>
                                                    
                                                    @if($order->status == 'ready')
                                                    <span class="ml-2 px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold pickup-timer-badge">
                                                        Auto-cancel in: <span class="pickup-countdown" data-order-id="{{ $order->order_id }}" data-ready-since="{{ $order->ready_since ? date('c', strtotime($order->ready_since)) : now()->toISOString() }}">
                                                            @php
                                                                // Calculate seconds remaining
                                                                $readySince = $order->ready_since ? \Carbon\Carbon::parse($order->ready_since) : now();
                                                                $now = now();
                                                                
                                                                // More explicit calculation with seconds since epoch
                                                                $readySinceTimestamp = $readySince->timestamp;
                                                                $nowTimestamp = $now->timestamp;
                                                                $elapsedSeconds = $nowTimestamp - $readySinceTimestamp;
                                                                
                                                                $remainingSeconds = max(0, 60 - $elapsedSeconds);
                                                                
                                                                // Debug information
                                                                $debug = [
                                                                    'ready_since' => $readySince->toDateTimeString(),
                                                                    'now' => $now->toDateTimeString(),
                                                                    'readySince_timestamp' => $readySinceTimestamp,
                                                                    'now_timestamp' => $nowTimestamp,
                                                                    'elapsed' => $elapsedSeconds,
                                                                    'remaining' => $remainingSeconds,
                                                                ];
                                                                // Log to server for debugging
                                                                \Log::info('Timer calculation for Order #'.$order->order_number, $debug);
                                                                
                                                                echo $remainingSeconds . 's';
                                                            @endphp
                                                        </span>
                                                    </span>
                                                    @endif
                                                </div>
                                                <p class="text-gray-600 text-sm mt-1">{{ $order->created_at->format('M d, Y h:i A') }}</p>
                                            </div>
                                            <p class="font-medium text-primary">₱{{ number_format($order->total_amount, 2) }}</p>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <p class="text-gray-700 font-medium">{{ $order->seller->stall_name ?? 'Seller #' . $order->seller_id }}</p>
                                            <p class="text-gray-600 text-sm">{{ $order->seller->stall_location ?? 'No location specified' }}</p>
                                        </div>
                                        
                                        <!-- Order Items Preview -->
                                        <div class="mb-4">
                                            <p class="text-gray-700 font-medium mb-2">Items:</p>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($order->orderItems as $item)
                                                    <div class="bg-gray-100 rounded-full px-3 py-1 text-sm text-gray-800">
                                                        {{ $item->quantity }}x {{ $item->menuItem->item_name ?? 'Item #'.$item->item_id }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        
                                        <div class="flex gap-3">
                                            <a href="{{ route('orders.show', $order->order_id) }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition text-sm">
                                                View Details
                                            </a>
                                            
                                            @if($order->status == 'pending')
                                                <form action="{{ route('orders.cancel', $order->order_id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="bg-red-50 text-red-700 px-4 py-2 rounded-lg hover:bg-red-100 transition text-sm cancel-order-btn" data-order-id="{{ $order->order_id }}">
                                                        Cancel Order
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Order Thumbnail -->
                                    <div class="w-full md:w-40 h-32 md:h-40 flex items-center justify-center overflow-hidden border-t md:border-t-0 md:border-l border-gray-100">
                                        <div class="w-24 h-24 rounded-full overflow-hidden">
                                            @if($order->orderItems->first() && $order->orderItems->first()->menuItem && $order->orderItems->first()->menuItem->image_url)
                                                <img src="{{ $order->orderItems->first()->menuItem->image_url }}" alt="Food" class="w-full h-full object-cover">
                                            @else
                                                <div class="bg-gray-200 w-full h-full flex items-center justify-center">
                                                    <iconify-icon icon="mdi:food" class="text-gray-400" width="36" height="36"></iconify-icon>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="bg-white rounded-xl p-8 shadow-sm text-center">
                            <iconify-icon icon="mdi:food-off" class="text-gray-400 mb-4" width="64" height="64"></iconify-icon>
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">No Orders Yet</h3>
                            <p class="text-gray-600 mb-4">You haven't placed any orders yet. Browse the menu and place your first order!</p>
                            <a href="{{ route('dashboard') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition">
                                Browse Menu
                            </a>
                        </div>
                    @endif
                </div>
                
                <!-- Pagination -->
                @if($orders->hasPages())
                    <div class="mt-6">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Mobile sidebar toggle
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const closeSidebar = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        
        // Initialize countdown timers for ready orders
        function initializeCountdownTimers() {
            const countdownElements = document.querySelectorAll('.pickup-countdown');
            countdownElements.forEach(element => {
                const orderId = element.getAttribute('data-order-id');
                
                // First verify with the server how much time is actually remaining
                $.ajax({
                    url: "{{ route('orders.check-remaining-time') }}",
                    type: "POST",
                    data: {
                        order_id: orderId,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('Time check response for order #' + response.order_number + ':', response);
                        
                        if (!response.success) {
                            console.error('Failed to check remaining time:', response.message);
                            return;
                        }
                        
                        // If the order should be cancelled automatically
                        if (response.should_cancel) {
                            element.textContent = 'Expired!';
                            
                            // Find the order item and update its appearance
                            const orderItem = element.closest('.order-item');
                            if (orderItem) {
                                const statusBadge = orderItem.querySelector('.rounded-full:not(.pickup-timer-badge)');
                                if (statusBadge) {
                                    statusBadge.classList.remove('bg-blue-100', 'text-blue-800');
                                    statusBadge.classList.add('bg-red-100', 'text-red-800');
                                    statusBadge.textContent = 'Cancelled';
                                }
                                
                                // Manually send auto-cancel request
                                console.log(`Order ID: ${orderId} already expired. Sending auto-cancel request...`);
                                
                                $.ajax({
                                    url: "{{ route('orders.auto-cancel') }}",
                                    type: "POST",
                                    data: {
                                        order_id: orderId,
                                        _token: $('meta[name="csrf-token"]').attr('content')
                                    },
                                    success: function(cancelResponse) {
                                        console.log('Auto-cancel response:', cancelResponse);
                                        if (cancelResponse.success) {
                                            // Reload the page to show the updated status
                                            setTimeout(() => {
                                                window.location.reload();
                                            }, 1000);
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('Error auto-cancelling order:', error);
                                    }
                                });
                                
                                return; // Skip setting up the timer
                            }
                        }
                        
                        // Set the initial remaining seconds from server response
                        let remainingSeconds = response.remaining_seconds;
                        
                        // Update the display
                        element.textContent = remainingSeconds + 's';
                        
                        // Apply styling based on remaining time
                        if (remainingSeconds <= 10) {
                            const badge = element.closest('.pickup-timer-badge');
                            if (badge) {
                                badge.classList.remove('bg-red-100', 'text-red-800');
                                badge.classList.add('bg-red-500', 'text-white', 'animate-pulse');
                            }
                        }
                        
                        // Update the timer every second
                        const timerId = setInterval(() => {
                            try {
                                remainingSeconds--;
                                
                                // Update the display
                                element.textContent = remainingSeconds + 's';
                                
                                // Apply styling based on remaining time
                                if (remainingSeconds <= 10) {
                                    const badge = element.closest('.pickup-timer-badge');
                                    if (badge) {
                                        badge.classList.remove('bg-red-100', 'text-red-800');
                                        badge.classList.add('bg-red-500', 'text-white', 'animate-pulse');
                                    }
                                }
                                
                                // Clear the interval when time is up
                                if (remainingSeconds <= 0) {
                                    clearInterval(timerId);
                                    element.textContent = 'Expired!';
                                    
                                    // Find the order item and update its appearance
                                    const orderItem = element.closest('.order-item');
                                    if (orderItem) {
                                        const statusBadge = orderItem.querySelector('.rounded-full:not(.pickup-timer-badge)');
                                        if (statusBadge) {
                                            statusBadge.classList.remove('bg-blue-100', 'text-blue-800');
                                            statusBadge.classList.add('bg-red-100', 'text-red-800');
                                            statusBadge.textContent = 'Cancelled';
                                        }
                                        
                                        // Manually send auto-cancel request
                                        console.log(`Timer expired for order ID: ${orderId}. Sending auto-cancel request...`);
                                        
                                        $.ajax({
                                            url: "{{ route('orders.auto-cancel') }}",
                                            type: "POST",
                                            data: {
                                                order_id: orderId,
                                                _token: $('meta[name="csrf-token"]').attr('content')
                                            },
                                            success: function(response) {
                                                console.log('Auto-cancel response:', response);
                                                if (response.success) {
                                                    // Show a message to the user
                                                    Swal.fire({
                                                        title: 'Order Auto-Cancelled',
                                                        text: 'Your order has been automatically cancelled because it was not picked up within 60 seconds.',
                                                        icon: 'warning',
                                                        confirmButtonText: 'OK',
                                                        confirmButtonColor: '#4F46E5'
                                                    }).then(() => {
                                                        // Reload the page to show the updated status
                                                        window.location.reload();
                                                    });
                                                }
                                            },
                                            error: function(xhr, status, error) {
                                                console.error('Error auto-cancelling order:', error);
                                            }
                                        });
                                    }
                                }
                            } catch (error) {
                                console.error('Error in countdown timer:', error);
                                clearInterval(timerId);
                            }
                        }, 1000);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error checking remaining time:', error);
                        // Use client-side fallback in case of error
                        console.log('Falling back to client-side timer calculation');
                    }
                });
            });
        }
        
        // Run initialization when the DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initializeCountdownTimers();
            checkMobileView();
        });
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('transform-none');
                sidebar.classList.toggle('-translate-x-full');
            });
        }
        
        if (closeSidebar && sidebar) {
            closeSidebar.addEventListener('click', function() {
                sidebar.classList.remove('transform-none');
                sidebar.classList.add('-translate-x-full');
            });
        }
        
        // Check if we're on mobile and hide sidebar by default
        function checkMobileView() {
            if (window.innerWidth < 768 && sidebar) {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('transform-none');
            } else if (sidebar) {
                sidebar.classList.remove('-translate-x-full');
            }
        }
        
        // Run on resize
        window.addEventListener('resize', checkMobileView);
        
        // Filter buttons
        const allBtn = document.getElementById('all-orders-btn');
        const activeBtn = document.getElementById('active-orders-btn');
        const completedBtn = document.getElementById('completed-orders-btn');
        const cancelledBtn = document.getElementById('cancelled-orders-btn');
        const searchInput = document.getElementById('search-orders');
        const orderItems = document.querySelectorAll('.order-item');
        
        // Add event listeners to filter buttons
        if (allBtn) allBtn.addEventListener('click', function() {
            setActiveButton(this);
            filterOrders();
        });
        
        if (activeBtn) activeBtn.addEventListener('click', function() {
            setActiveButton(this);
            filterOrders();
        });
        
        if (completedBtn) completedBtn.addEventListener('click', function() {
            setActiveButton(this);
            filterOrders();
        });
        
        if (cancelledBtn) cancelledBtn.addEventListener('click', function() {
            setActiveButton(this);
            filterOrders();
        });
        
        // Add event listener to search input
        if (searchInput) searchInput.addEventListener('input', filterOrders);
        
        function setActiveButton(button) {
            [allBtn, activeBtn, completedBtn, cancelledBtn].forEach(btn => {
                btn.classList.remove('bg-primary', 'text-white');
                btn.classList.add('bg-white', 'text-gray-700');
            });
            
            button.classList.remove('bg-white', 'text-gray-700');
            button.classList.add('bg-primary', 'text-white');
        }
        
        function filterOrders() {
            const searchText = searchInput.value.toLowerCase();
            const activeFilter = document.querySelector('.px-4.py-2.bg-primary.text-white').id;
            
            orderItems.forEach(item => {
                const status = item.getAttribute('data-status');
                const orderNumber = item.getAttribute('data-order-number').toLowerCase();
                const seller = item.getAttribute('data-seller').toLowerCase();
                
                let showItem = true;
                
                // Filter by status
                if (activeFilter === 'active-orders-btn' && status !== 'pending' && status !== 'ready') {
                    showItem = false;
                } else if (activeFilter === 'completed-orders-btn' && status !== 'completed') {
                    showItem = false;
                } else if (activeFilter === 'cancelled-orders-btn' && status !== 'cancelled') {
                    showItem = false;
                }
                
                // Filter by search text
                if (searchText && !orderNumber.includes(searchText) && !seller.includes(searchText)) {
                    showItem = false;
                }
                
                // Show/hide the item
                if (showItem) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>
