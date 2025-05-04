<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Order Details - Canteen Online Ordering System</title>
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
    </style>
</head>
<body class="bg-gray-50 font-sans">
    @php
    use Illuminate\Support\Str;
    @endphp
    
    <div class="flex">
        <!-- Sidebar -->
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
                    
                    <div class="flex items-center">
                        <a href="{{ route('orders.index') }}" class="text-primary hover:text-primary-dark mr-2">
                            <iconify-icon icon="mdi:arrow-left" width="24" height="24"></iconify-icon>
                        </a>
                        <h1 class="text-xl font-semibold text-gray-800">Order Details</h1>
                    </div>

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

            <!-- Order Details Section -->
            <div class="px-5 py-3">
                <div class="bg-white rounded-xl p-5 shadow-sm">
                    <!-- Order Header -->
                    <div class="border-b pb-4 mb-4">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-center">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <h2 class="text-xl font-semibold text-gray-800">Order #{{ $order->order_number }}</h2>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        @if($order->status == 'completed') bg-green-100 text-green-800
                                        @elseif($order->status == 'cancelled') bg-red-100 text-red-800
                                        @elseif($order->status == 'processing') bg-blue-100 text-blue-800
                                        @else bg-yellow-100 text-yellow-800
                                        @endif">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                    
                                    @if($order->status == 'ready')
                                    <span class="ml-2 px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">
                                        Auto-cancel in: <span id="pickup-countdown" data-updated-at="{{ $order->updated_at ? $order->updated_at->toISOString() : now()->toISOString() }}">
                                            @php
                                                // Calculate seconds remaining
                                                $updatedAt = $order->updated_at ?? now();
                                                $now = now();
                                                
                                                // More explicit calculation with seconds since epoch
                                                $updatedAtTimestamp = $updatedAt->timestamp;
                                                $nowTimestamp = $now->timestamp;
                                                $elapsedSeconds = $nowTimestamp - $updatedAtTimestamp;
                                                
                                                $remainingSeconds = max(0, 60 - $elapsedSeconds);
                                                
                                                // Debug information
                                                \Log::info('Order details timer for #'.$order->order_number, [
                                                    'updated_at' => $updatedAt->toDateTimeString(),
                                                    'now' => $now->toDateTimeString(),
                                                    'elapsed' => $elapsedSeconds,
                                                    'remaining' => $remainingSeconds,
                                                ]);
                                                
                                                echo $remainingSeconds . 's';
                                            @endphp
                                        </span>
                                    </span>
                                    @endif
                                </div>
                                <p class="text-gray-600">Placed on {{ $order->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                            <div class="mt-4 md:mt-0 flex items-center gap-2">
                                <p class="text-gray-700">Total: <span class="font-semibold text-primary">₱{{ number_format($order->total_amount, 2) }}</span></p>
                                <button id="show-receipt-btn" class="bg-primary text-white px-3 py-1.5 rounded-lg hover:bg-primary/90 text-sm flex items-center gap-1.5">
                                    <iconify-icon icon="mdi:receipt" width="18" height="18"></iconify-icon>
                                    Show Receipt
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Seller Info -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Seller</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="font-medium">{{ $order->seller->stall_name ?? 'Stall #' . $order->seller_id }}</p>
                                <p class="text-gray-600">{{ $order->seller->stall_location ?? 'Location not specified' }}</p>
                                <p class="text-gray-600">{{ $order->seller->contact_number ?? 'Contact not available' }}</p>
                            </div>
                        </div>
                        
                        <!-- Order Info -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Pickup Details</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="mb-3">
                                    <p><span class="font-medium">Stall Name:</span> {{ $order->seller->stall_name ?? 'Not specified' }}</p>
                                    <p><span class="font-medium">Stall Location:</span> {{ $order->seller->stall_location ?? 'Not specified' }}</p>
                                    <p><span class="font-medium">Stall Number:</span> {{ $order->seller->stall_number ?? ($order->seller->stall_letter ?? 'Not specified') }}</p>
                                    <p><span class="font-medium">Building:</span> {{ $order->seller->building ?? 'Not specified' }}</p>
                                </div>
                                <p><span class="font-medium">Pickup Time:</span> As soon as it's ready</p>
                                <p class="mt-2 text-sm text-gray-500">Please wait for notification when your order is ready for pickup.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Items -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Order Items</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-primary text-white">
                                        <th class="py-3 px-4 text-left rounded-tl-lg">Item</th>
                                        <th class="py-3 px-4 text-center">Quantity</th>
                                        <th class="py-3 px-4 text-right">Subtotal</th>
                                        <th class="py-3 px-4 text-center rounded-tr-lg">Rating</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->orderItems as $item)
                                    <tr class="border-b">
                                        <td class="py-3 px-4">
                                            <div class="flex items-center">
                                                <div class="w-12 h-12 mr-3">
                                                    @if($item->menuItem && $item->menuItem->image_url)
                                                        <img src="{{ $item->menuItem->image_url }}" alt="{{ $item->menuItem->item_name ?? 'Food item' }}" class="w-full h-full object-cover rounded-md">
                                                    @else
                                                        <img src="https://source.unsplash.com/50x50/?food" alt="Food item" class="w-full h-full object-cover rounded-md">
                                                    @endif
                                                </div>
                                                <div>
                                                    <p class="font-medium">{{ $item->menuItem->item_name ?? 'Item #'.$item->item_id }}</p>
                                                    @if($item->menuItem && $item->menuItem->description)
                                                        <p class="text-sm text-gray-500 truncate">{{ Str::limit($item->menuItem->description, 30) }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-center">{{ $item->quantity }}</td>
                                        <td class="py-3 px-4 text-right">₱{{ number_format($item->subtotal, 2) }}</td>
                                        <td class="py-3 px-4 text-center">
                                            @if($order->status == 'completed')
                                                @if($item->rating)
                                                    <div class="flex justify-center items-center">
                                                        <div class="flex">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                @if($i <= $item->rating)
                                                                    <iconify-icon icon="mdi:star" class="text-yellow-400" width="16" height="16"></iconify-icon>
                                                                @else
                                                                    <iconify-icon icon="mdi:star-outline" class="text-gray-400" width="16" height="16"></iconify-icon>
                                                                @endif
                                                            @endfor
                                                        </div>
                                                        <button class="ml-2 text-xs text-primary hover:underline" 
                                                            onclick="Swal.fire({
                                                                title: 'Rate this item',
                                                                html: `
                                                                    <div class='rating-stars text-center mb-4'>
                                                                        <div class='flex justify-center gap-1'>
                                                                            <i class='star cursor-pointer text-3xl text-gray-300 hover:text-yellow-400' data-value='1'>★</i>
                                                                            <i class='star cursor-pointer text-3xl text-gray-300 hover:text-yellow-400' data-value='2'>★</i>
                                                                            <i class='star cursor-pointer text-3xl text-gray-300 hover:text-yellow-400' data-value='3'>★</i>
                                                                            <i class='star cursor-pointer text-3xl text-gray-300 hover:text-yellow-400' data-value='4'>★</i>
                                                                            <i class='star cursor-pointer text-3xl text-gray-300 hover:text-yellow-400' data-value='5'>★</i>
                                                                        </div>
                                                                    </div>
                                                                    <textarea id='review' class='w-full p-2 border border-gray-300 rounded' rows='3' placeholder='Leave a review (optional)'></textarea>
                                                                    <input type='hidden' id='rating-value' value='{{ $item->rating }}'>
                                                                    <input type='hidden' id='order-item-id' value='{{ $item->order_item_id }}'>
                                                                `,
                                                                showCancelButton: true,
                                                                confirmButtonText: 'Submit',
                                                                confirmButtonColor: '#4F46E5',
                                                                cancelButtonText: 'Cancel',
                                                                didOpen: () => {
                                                                    // Set initial rating
                                                                    const currentRating = {{ $item->rating }};
                                                                    const stars = document.querySelectorAll('.rating-stars .star');
                                                                    
                                                                    // Highlight stars up to current rating
                                                                    stars.forEach(star => {
                                                                        const value = parseInt(star.getAttribute('data-value'));
                                                                        if (value <= currentRating) {
                                                                            star.classList.remove('text-gray-300');
                                                                            star.classList.add('text-yellow-400');
                                                                        }
                                                                        
                                                                        // Add click event to stars
                                                                        star.addEventListener('click', function() {
                                                                            const value = parseInt(this.getAttribute('data-value'));
                                                                            document.getElementById('rating-value').value = value;
                                                                            
                                                                            // Update star colors
                                                                            stars.forEach(s => {
                                                                                const starValue = parseInt(s.getAttribute('data-value'));
                                                                                if (starValue <= value) {
                                                                                    s.classList.remove('text-gray-300');
                                                                                    s.classList.add('text-yellow-400');
                                                                                } else {
                                                                                    s.classList.remove('text-yellow-400');
                                                                                    s.classList.add('text-gray-300');
                                                                                }
                                                                            });
                                                                        });
                                                                    });
                                                                }
                                                            }).then((result) => {
                                                                if (result.isConfirmed) {
                                                                    const ratingValue = document.getElementById('rating-value').value;
                                                                    const reviewText = document.getElementById('review').value;
                                                                    const orderItemId = document.getElementById('order-item-id').value;
                                                                    
                                                                    // Send AJAX request to save rating
                                                                    fetch('{{ route('rating.store') }}', {
                                                                        method: 'POST',
                                                                        headers: {
                                                                            'Content-Type': 'application/json',
                                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                            'Accept': 'application/json'
                                                                        },
                                                                        body: JSON.stringify({
                                                                            order_item_id: orderItemId,
                                                                            rating: ratingValue,
                                                                            review: reviewText
                                                                        })
                                                                    })
                                                                    .then(response => response.json())
                                                                    .then(data => {
                                                                        if (data.success) {
                                                                            Swal.fire({
                                                                                title: 'Rating Submitted',
                                                                                text: data.message,
                                                                                icon: 'success',
                                                                                confirmButtonColor: '#4F46E5'
                                                                            }).then(() => {
                                                                                location.reload();
                                                                            });
                                                                        } else {
                                                                            Swal.fire({
                                                                                title: 'Error',
                                                                                text: data.message || 'Failed to submit rating',
                                                                                icon: 'error',
                                                                                confirmButtonColor: '#4F46E5'
                                                                            });
                                                                        }
                                                                    })
                                                                    .catch(error => {
                                                                        console.error('Error:', error);
                                                                        Swal.fire({
                                                                            title: 'Error',
                                                                            text: 'An error occurred while submitting your rating',
                                                                            icon: 'error',
                                                                            confirmButtonColor: '#4F46E5'
                                                                        });
                                                                    });
                                                                }
                                                            })">
                                                            Edit
                                                        </button>
                                                @else
                                                    <button class="bg-primary text-white px-3 py-1 rounded-lg text-xs hover:bg-primary/90" 
                                                        onclick="Swal.fire({
                                                                title: 'Rate this item',
                                                                html: `
                                                                    <div class='rating-stars text-center mb-4'>
                                                                        <div class='flex justify-center gap-1'>
                                                                            <i class='star cursor-pointer text-3xl text-gray-300 hover:text-yellow-400' data-value='1'>★</i>
                                                                            <i class='star cursor-pointer text-3xl text-gray-300 hover:text-yellow-400' data-value='2'>★</i>
                                                                            <i class='star cursor-pointer text-3xl text-gray-300 hover:text-yellow-400' data-value='3'>★</i>
                                                                            <i class='star cursor-pointer text-3xl text-gray-300 hover:text-yellow-400' data-value='4'>★</i>
                                                                            <i class='star cursor-pointer text-3xl text-gray-300 hover:text-yellow-400' data-value='5'>★</i>
                                                                        </div>
                                                                    </div>
                                                                    <textarea id='review' class='w-full p-2 border border-gray-300 rounded' rows='3' placeholder='Leave a review (optional)'></textarea>
                                                                    <input type='hidden' id='rating-value' value='0'>
                                                                    <input type='hidden' id='order-item-id' value='{{ $item->order_item_id }}'>
                                                                `,
                                                                showCancelButton: true,
                                                                confirmButtonText: 'Submit',
                                                                confirmButtonColor: '#4F46E5',
                                                                cancelButtonText: 'Cancel',
                                                                didOpen: () => {
                                                                    // Add click event to stars
                                                                    const stars = document.querySelectorAll('.rating-stars .star');
                                                                    stars.forEach(star => {
                                                                        star.addEventListener('click', function() {
                                                                            const value = parseInt(this.getAttribute('data-value'));
                                                                            document.getElementById('rating-value').value = value;
                                                                            
                                                                            // Update star colors
                                                                            stars.forEach(s => {
                                                                                const starValue = parseInt(s.getAttribute('data-value'));
                                                                                if (starValue <= value) {
                                                                                    s.classList.remove('text-gray-300');
                                                                                    s.classList.add('text-yellow-400');
                                                                                } else {
                                                                                    s.classList.remove('text-yellow-400');
                                                                                    s.classList.add('text-gray-300');
                                                                                }
                                                                            });
                                                                        });
                                                                    });
                                                                }
                                                            }).then((result) => {
                                                                if (result.isConfirmed) {
                                                                    const ratingValue = document.getElementById('rating-value').value;
                                                                    const reviewText = document.getElementById('review').value;
                                                                    const orderItemId = document.getElementById('order-item-id').value;
                                                                    
                                                                    if (ratingValue === '0') {
                                                                        Swal.fire({
                                                                            title: 'Rating Required',
                                                                            text: 'Please select a rating from 1 to 5 stars',
                                                                            icon: 'warning',
                                                                            confirmButtonColor: '#4F46E5'
                                                                        });
                                                                        return;
                                                                    }
                                                                    
                                                                    // Send AJAX request to save rating
                                                                    fetch('{{ route('rating.store') }}', {
                                                                        method: 'POST',
                                                                        headers: {
                                                                            'Content-Type': 'application/json',
                                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                            'Accept': 'application/json'
                                                                        },
                                                                        body: JSON.stringify({
                                                                            order_item_id: orderItemId,
                                                                            rating: ratingValue,
                                                                            review: reviewText
                                                                        })
                                                                    })
                                                                    .then(response => response.json())
                                                                    .then(data => {
                                                                        if (data.success) {
                                                                            Swal.fire({
                                                                                title: 'Rating Submitted',
                                                                                text: data.message,
                                                                                icon: 'success',
                                                                                confirmButtonColor: '#4F46E5'
                                                                            }).then(() => {
                                                                                location.reload();
                                                                            });
                                                                        } else {
                                                                            Swal.fire({
                                                                                title: 'Error',
                                                                                text: data.message || 'Failed to submit rating',
                                                                                icon: 'error',
                                                                                confirmButtonColor: '#4F46E5'
                                                                            });
                                                                        }
                                                                    })
                                                                    .catch(error => {
                                                                        console.error('Error:', error);
                                                                        Swal.fire({
                                                                            title: 'Error',
                                                                            text: 'An error occurred while submitting your rating',
                                                                            icon: 'error',
                                                                            confirmButtonColor: '#4F46E5'
                                                                        });
                                                                    });
                                                                }
                                                            })">
                                                        Rate Item
                                                    </button>
                                                @endif
                                            @else
                                                <span class="text-xs text-gray-500">Available after order completion</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-50 font-semibold">
                                        <td class="py-3 px-4 rounded-bl-lg" colspan="2">Total</td>
                                        <td class="py-3 px-4 text-right rounded-br-lg">₱{{ number_format($order->total_amount, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receipt-modal" class="fixed inset-0 bg-black/50 hidden justify-center items-center z-50">
        <div class="bg-white rounded-xl max-w-md w-full mx-5 max-h-[90vh] overflow-y-auto">
            <!-- Receipt Header -->
            <div class="bg-primary text-white p-4 rounded-t-xl">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-xl font-bold">Order Receipt</h3>
                    <button id="close-receipt" class="text-white hover:text-gray-200">
                        <iconify-icon icon="mdi:close" width="24" height="24"></iconify-icon>
                    </button>
                </div>
                <p class="text-sm opacity-90">Show this to the seller to collect your order</p>
            </div>
            
            <div class="p-5 pt-3">
                <!-- Order Info -->
                <div class="text-center mb-4">
                    <h4 class="text-lg font-bold text-gray-900">{{ $order->seller->stall_name ?? 'Stall #' . $order->seller_id }}</h4>
                    <p class="text-sm text-gray-500">Order #{{ $order->order_number }}</p>
                    <p class="text-xs text-gray-500">{{ $order->created_at->format('M d, Y h:i A') }}</p>
                </div>
                
                <div class="border-t border-dashed border-gray-300 my-3"></div>
                
                <!-- Stall Info -->
                <div class="mb-4">
                    <p class="font-semibold text-gray-800 mb-2">Stall Details:</p>
                    <p class="text-sm"><span class="font-medium">Location:</span> {{ $order->seller->stall_location ?? 'Not specified' }}</p>
                    <p class="text-sm"><span class="font-medium">Stall Number:</span> {{ $order->seller->stall_number ?? ($order->seller->stall_letter ?? 'Not specified') }}</p>
                    <p class="text-sm"><span class="font-medium">Building:</span> {{ $order->seller->building ?? 'Not specified' }}</p>
                </div>
                
                <div class="border-t border-dashed border-gray-300 my-3"></div>
                
                <!-- Customer Info -->
                <div class="mb-4">
                    <p class="font-medium text-gray-700">Student: <span class="font-normal">{{ Auth::user()->name ?? Auth::user()->email }}</span></p>
                </div>
                
                <div class="border-t border-dashed border-gray-300 my-3"></div>
                
                <!-- Order Items -->
                <div class="mb-4">
                    <p class="font-semibold text-gray-800 mb-2">Items:</p>
                    <div class="space-y-2">
                        @foreach($order->orderItems as $item)
                        <div class="flex justify-between">
                            <div>
                                <span class="font-medium">{{ $item->quantity }} x </span>
                                <span>{{ $item->menuItem->item_name ?? 'Item #'.$item->item_id }}</span>
                            </div>
                            <span class="font-medium">₱{{ number_format($item->subtotal, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="border-t border-dashed border-gray-300 my-3"></div>
                
                <!-- Total -->
                <div class="flex justify-between font-bold text-lg">
                    <span>Total:</span>
                    <span>₱{{ number_format($order->total_amount, 2) }}</span>
                </div>
                
                <div class="border-t border-dashed border-gray-300 my-4"></div>
                
                <!-- Status -->
                <div class="mb-5 text-center">
                    <span class="px-4 py-1.5 rounded-full text-sm font-semibold
                        @if($order->status == 'completed') bg-green-100 text-green-800
                        @elseif($order->status == 'cancelled') bg-red-100 text-red-800
                        @elseif($order->status == 'processing') bg-blue-100 text-blue-800
                        @else bg-yellow-100 text-yellow-800
                        @endif">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
                
                <!-- Action buttons -->
                <div class="flex gap-3">
                    <button id="print-receipt" class="bg-primary text-white rounded-lg py-2.5 px-4 flex-1 hover:bg-primary/90 flex items-center justify-center gap-2">
                        <iconify-icon icon="mdi:printer" width="18" height="18"></iconify-icon>
                        Print Receipt
                    </button>
                    <button id="download-receipt" class="bg-gray-100 text-gray-800 rounded-lg py-2.5 px-4 flex-1 hover:bg-gray-200 flex items-center justify-center gap-2">
                        <iconify-icon icon="mdi:download" width="18" height="18"></iconify-icon>
                        Download
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rating Modal - Removed since we're using SweetAlert now -->

    <script>
        // Mobile sidebar toggle
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const closeSidebar = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        
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
        
        // Run on page load
        window.addEventListener('DOMContentLoaded', checkMobileView);
        
        // Run on resize
        window.addEventListener('resize', checkMobileView);

        // Receipt Modal Toggle
        const receiptModal = document.getElementById('receipt-modal');
        const showReceiptBtn = document.getElementById('show-receipt-btn');
        const closeReceiptBtn = document.getElementById('close-receipt');
        const printReceiptBtn = document.getElementById('print-receipt');
        const downloadReceiptBtn = document.getElementById('download-receipt');
        
        if (showReceiptBtn && receiptModal) {
            showReceiptBtn.addEventListener('click', function() {
                receiptModal.classList.remove('hidden');
                receiptModal.classList.add('flex');
            });
        }
        
        if (closeReceiptBtn && receiptModal) {
            closeReceiptBtn.addEventListener('click', function() {
                receiptModal.classList.remove('flex');
                receiptModal.classList.add('hidden');
            });
        }
        
        // Close on outside click
        if (receiptModal) {
            receiptModal.addEventListener('click', function(event) {
                if (event.target === receiptModal) {
                    receiptModal.classList.remove('flex');
                    receiptModal.classList.add('hidden');
                }
            });
        }
        
        // Print receipt
        if (printReceiptBtn) {
            printReceiptBtn.addEventListener('click', function() {
                const receiptContent = document.querySelector('#receipt-modal .bg-white').innerHTML;
                const originalContents = document.body.innerHTML;
                
                document.body.innerHTML = `
                    <div class="p-5 max-w-md mx-auto">
                        ${receiptContent}
                    </div>
                `;
                
                window.print();
                document.body.innerHTML = originalContents;
                location.reload();
            });
        }
        
        // Download as image (screenshot)
        if (downloadReceiptBtn) {
            downloadReceiptBtn.addEventListener('click', function() {
                alert("To save this receipt, take a screenshot or use the print button and save as PDF.");
            });
        }

        // SweetAlert notifications for success and error messages
        document.addEventListener('DOMContentLoaded', function() {
            // Check for success message
            @if(session('success'))
                Swal.fire({
                    title: 'Success',
                    text: "{{ session('success') }}",
                    icon: 'success',
                    confirmButtonColor: '#4F46E5',
                    confirmButtonText: 'OK'
                });
            @endif

            // Check for error message
            @if(session('error'))
                Swal.fire({
                    title: 'Error',
                    text: "{{ session('error') }}",
                    icon: 'error',
                    confirmButtonColor: '#4F46E5',
                    confirmButtonText: 'OK'
                });
            @endif
            
            // Show validation errors if any
            @if($errors->any())
                Swal.fire({
                    title: 'Validation Error',
                    html: `@foreach($errors->all() as $error)
                        <p class="text-sm">{{ $error }}</p>
                    @endforeach`,
                    icon: 'error',
                    confirmButtonColor: '#4F46E5',
                    confirmButtonText: 'OK'
                });
            @endif
        });

        @if($order->status == 'ready')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const countdownElement = document.getElementById('pickup-countdown');
                if (countdownElement) {
                    // First verify with the server how much time is actually remaining
                    $.ajax({
                        url: "{{ route('orders.check-remaining-time') }}",
                        type: "POST",
                        data: {
                            order_id: {{ $order->order_id }},
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
                                countdownElement.textContent = 'Time expired!';
                                
                                // Auto-cancel the order that has expired
                                $.ajax({
                                    url: "{{ route('orders.auto-cancel') }}",
                                    type: "POST",
                                    data: {
                                        order_id: {{ $order->order_id }},
                                        _token: $('meta[name="csrf-token"]').attr('content')
                                    },
                                    success: function(response) {
                                        console.log('Auto-cancel response:', response);
                                        if (response.success) {
                                            // Inform the user
                                            Swal.fire({
                                                title: 'Order Auto-Cancelled',
                                                text: 'This order has been automatically cancelled because it was not picked up within 60 seconds.',
                                                icon: 'warning',
                                                confirmButtonText: 'OK',
                                                confirmButtonColor: '#4F46E5'
                                            }).then(() => {
                                                // Reload the page to show updated status
                                                window.location.reload();
                                            });
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('Error auto-cancelling order:', error);
                                    }
                                });
                                
                                return; // Skip setting up the timer
                            }
                            
                            // Set the initial remaining seconds from server response
                            let remainingSeconds = response.remaining_seconds;
                            
                            // Update the display
                            countdownElement.textContent = remainingSeconds + 's';
                            
                            // Apply styling based on remaining time
                            if (remainingSeconds <= 10) {
                                countdownElement.parentElement.classList.remove('bg-red-100', 'text-red-800');
                                countdownElement.parentElement.classList.add('bg-red-500', 'text-white', 'animate-pulse');
                            }
                            
                            // Update the timer every second
                            const timerId = setInterval(() => {
                                try {
                                    remainingSeconds--;
                                    
                                    // Update the display
                                    countdownElement.textContent = remainingSeconds + 's';
                                    
                                    // Apply styling based on remaining time
                                    if (remainingSeconds <= 10) {
                                        countdownElement.parentElement.classList.remove('bg-red-100', 'text-red-800');
                                        countdownElement.parentElement.classList.add('bg-red-500', 'text-white', 'animate-pulse');
                                    }
                                    
                                    // Clear the interval when time is up
                                    if (remainingSeconds <= 0) {
                                        clearInterval(timerId);
                                        countdownElement.textContent = 'Time expired!';
                                        
                                        // Auto-cancel the order that has expired
                                        $.ajax({
                                            url: "{{ route('orders.auto-cancel') }}",
                                            type: "POST",
                                            data: {
                                                order_id: {{ $order->order_id }},
                                                _token: $('meta[name="csrf-token"]').attr('content')
                                            },
                                            success: function(response) {
                                                console.log('Auto-cancel response:', response);
                                                if (response.success) {
                                                    // Inform the user
                                                    Swal.fire({
                                                        title: 'Order Auto-Cancelled',
                                                        text: 'This order has been automatically cancelled because it was not picked up within 60 seconds.',
                                                        icon: 'warning',
                                                        confirmButtonText: 'OK',
                                                        confirmButtonColor: '#4F46E5'
                                                    }).then(() => {
                                                        // Reload the page to show updated status
                                                        window.location.reload();
                                                    });
                                                }
                                            },
                                            error: function(xhr, status, error) {
                                                console.error('Error auto-cancelling order:', error);
                                            }
                                        });
                                    }
                                } catch (error) {
                                    console.error('Error in countdown timer:', error);
                                    clearInterval(timerId);
                                }
                            }, 1000);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error checking remaining time:', error);
                            countdownElement.textContent = '60s'; // Fallback
                        }
                    });
                }
            });
        </script>
        @endif
    </script>
</body>
</html> 
