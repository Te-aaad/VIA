<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display a listing of the user's orders.
     */
    public function index()
    {
        $user = Auth::user();
        $orders = Order::where('student_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->with(['seller', 'orderItems.menuItem'])
            ->paginate(10);
            
        return view('orders.index', compact('orders'));
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'spot_number' => 'nullable|string|max:20',
            'payment_method' => 'nullable|in:cash,credit,wallet'
        ]);
        
        try {
            DB::beginTransaction();
            
            $user = Auth::user();
            
            // Check if user has any pending orders already
            $pendingOrdersCount = DB::table('orders')
                ->where('student_id', $user->user_id)
                ->where('status', 'pending')
                ->count();
                
            if ($pendingOrdersCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have pending orders. Please wait for them to be completed before placing a new order.'
                ], 400);
            }
            
            // Get the cart for the user
            $cart = DB::table('carts')
                ->where('user_id', $user->user_id)
                ->first();
                
            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your cart is empty'
                ], 400);
            }
            
            // Get cart items
            $cartItems = DB::table('cart_items')
                ->where('cart_id', $cart->cart_id)
                ->get();
                
            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your cart is empty'
                ], 400);
            }
            
            // Check if total items exceed limit
            $totalQuantity = $cartItems->sum('quantity');
            if ($totalQuantity > 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only order up to a maximum of 3 items per transaction. Please complete your current order first.'
                ], 400);
            }
            
            // Check stock availability for all items
            foreach ($cartItems as $item) {
                $menuItem = DB::table('menuitems')
                    ->where('item_id', $item->item_id)
                    ->first();
                    
                if (!$menuItem) {
                    return response()->json([
                        'success' => false,
                        'message' => "Item '{$item->item_name}' is no longer available."
                    ], 400);
                }
                
                if (!$menuItem->is_available) {
                    return response()->json([
                        'success' => false,
                        'message' => "'{$item->item_name}' is currently unavailable."
                    ], 400);
                }
            }

            // Get information about each menu item
            $enrichedCartItems = [];
            foreach ($cartItems as $item) {
                $menuItem = DB::table('menuitems')
                    ->where('item_id', $item->item_id)
                    ->first();
                    
                $enrichedItem = clone $item;
                $enrichedItem->seller_id = $menuItem->seller_id;
                $enrichedCartItems[] = $enrichedItem;
            }
            
            // Group items by seller
            $itemsBySeller = collect($enrichedCartItems)->groupBy('seller_id');
            
            // Don't verify stock - allow all orders to proceed
            
            $orders = [];
            
            // Create an order for each seller
            foreach ($itemsBySeller as $sellerId => $items) {
                $totalAmount = $items->sum(function ($item) {
                    return $item->price * $item->quantity;
                });
                
                // If spot_number wasn't provided, generate one
                $spotNumber = $validated['spot_number'] ?? 'Table-' . rand(1, 100);
                
                // Generate unique order number for this seller's order
                $orderNumber = 'ORD-' . strtoupper(Str::random(6));
                
                // Create the order using direct DB insert to avoid timestamp issues
                $orderId = DB::table('orders')->insertGetId([
                    'student_id' => $user->user_id,
                    'seller_id' => $sellerId,
                    'order_number' => $orderNumber,
                    'total_amount' => $totalAmount,
                    'spot_number' => $spotNumber,
                    'status' => 'pending'
                ]);
                
                // Manually create Order object for response
                $order = new Order();
                $order->order_id = $orderId;
                $order->student_id = $user->user_id; 
                $order->seller_id = $sellerId;
                $order->order_number = $orderNumber;
                $order->total_amount = $totalAmount;
                $order->spot_number = $spotNumber;
                $order->status = 'pending';
                
                // Add the order items directly with DB
                foreach ($items as $item) {
                    $subtotal = $item->price * $item->quantity;
                    
                    DB::table('orderitems')->insert([
                        'order_id' => $orderId,
                        'item_id' => $item->item_id,
                        'quantity' => $item->quantity,
                        'subtotal' => $subtotal
                    ]);
                    
                    // Update inventory - decrease available_stock
                    DB::table('menuitems')
                        ->where('item_id', $item->item_id)
                        ->decrement('available_stock', $item->quantity);
                }
                
                $orders[] = $order;
            }
            
            // Clear the cart
            DB::table('cart_items')->where('cart_id', $cart->cart_id)->delete();
            DB::table('carts')->where('cart_id', $cart->cart_id)->update(['total_amount' => 0]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'order_number' => $orderNumber,
                'orders' => $orders
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log detailed error
            \Log::error('Order creation failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to place order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order.
     */
    public function show($id)
    {
        $user = Auth::user();
        $order = Order::with('orderItems')->findOrFail($id);
        
        // Check if this order belongs to the current user
        if ($order->student_id !== $user->user_id) {
            return redirect()->route('orders.index')
                ->with('error', 'You do not have permission to view this order');
        }
        
        return view('orders.show', compact('order'));
    }

    /**
     * Display a listing of orders for the seller.
     *
     * @return \Illuminate\Http\Response
     */
    public function sellerIndex()
    {
        $user = auth()->user();
        
        if ($user->role !== 'seller') {
            return redirect()->route('dashboard')->with('error', 'You do not have access to seller orders.');
        }
        
        $seller = \App\Models\Seller::where('user_id', $user->user_id)->first();
        
        if (!$seller) {
            return redirect()->route('dashboard')->with('error', 'Seller profile not found.');
        }
        
        // Get orders with optional status filter
        $query = \App\Models\Order::where('seller_id', $seller->seller_id);
        
        // Apply status filter if specified
        if (request('status') && in_array(request('status'), ['pending', 'ready', 'completed', 'cancelled'])) {
            $query->where('status', request('status'));
        }
        
        $orders = $query->orderBy('created_at', 'desc')
            ->with(['student', 'orderItems'])
            ->paginate(20);
        
        return view('seller.orders.index', compact('orders', 'seller'));
    }
    
    /**
     * Display the specified order for the seller.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function sellerShow($id)
    {
        $user = auth()->user();
        
        if ($user->role !== 'seller') {
            return redirect()->route('dashboard')->with('error', 'You do not have access to seller orders.');
        }
        
        $seller = \App\Models\Seller::where('user_id', $user->user_id)->first();
        
        if (!$seller) {
            return redirect()->route('dashboard')->with('error', 'Seller profile not found.');
        }
        
        $order = \App\Models\Order::where('order_id', $id)
            ->where('seller_id', $seller->seller_id)
            ->with(['student', 'orderItems', 'orderItems.menuItem'])
            ->first();
        
        if (!$order) {
            return redirect()->route('seller.orders.index')->with('error', 'Order not found or you do not have permission to view it.');
        }
        
        return view('seller.orders.show', compact('order', 'seller'));
    }
    
    /**
     * Update the order status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        $user = auth()->user();
        
        if ($user->role !== 'seller') {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to update order status.');
        }
        
        $seller = \App\Models\Seller::where('user_id', $user->user_id)->first();
        
        if (!$seller) {
            return redirect()->route('dashboard')->with('error', 'Seller profile not found.');
        }
        
        $order = \App\Models\Order::where('order_id', $id)
            ->where('seller_id', $seller->seller_id)
            ->with('student') // Load the student relationship
            ->first();
        
        if (!$order) {
            return redirect()->route('seller.orders.index')->with('error', 'Order not found or you do not have permission to update it.');
        }
        
        $request->validate([
            'status' => 'required|in:pending,ready,completed,cancelled',
        ]);
        
        // Get the previous status
        $oldStatus = $order->status;
        
        // Update the status
        $order->status = $request->status;
        
        // If the order is marked as ready for pickup, set the ready_since timestamp
        if ($request->status === 'ready' && $oldStatus !== 'ready') {
            $order->ready_since = now();
            \Log::info('Order #' . $order->order_number . ' marked as ready at ' . $order->ready_since);
        }
        
        $order->save();
        
        // If the order is marked as ready for pickup
        if ($request->status === 'ready' && $oldStatus !== 'ready') {
            // Send notification to student
            try {
                // Direct insert into notifications table to bypass any queue issues during development
                \DB::table('notifications')->insert([
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'type' => 'App\\Notifications\\OrderReadyForPickup',
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $order->student_id,
                    'data' => json_encode([
                        'order_id' => $order->order_id,
                        'order_number' => $order->order_number,
                        'seller_name' => $order->seller->stall_name ?? 'Seller #' . $order->seller_id,
                        'message' => 'Your order is ready for pickup! Please collect it within 60 seconds.',
                        'type' => 'ready_for_pickup',
                        'pickup_by' => now()->addSeconds(60)->toDateTimeString(),
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Schedule job to auto-cancel if not picked up within 60 seconds
                \App\Jobs\CancelUnpickedOrder::dispatch($order->order_id)
                    ->delay(now()->addSeconds(60));
                    
                // Add an extra success message
                return redirect()->route('seller.orders.show', $order->order_id)
                    ->with('success', 'Order status has been updated to Ready for Pickup. The customer has been notified and has 60 seconds to pick up the order.');
            } catch (\Exception $e) {
                \Log::error('Failed to process ready order notification: ' . $e->getMessage(), [
                    'order_id' => $id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        return redirect()->route('seller.orders.show', $order->order_id)
            ->with('success', 'Order status has been updated to ' . ucfirst($order->status));
    }

    /**
     * Cancel a pending order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        $user = Auth::user();
        $order = Order::findOrFail($id);
        
        // Check if this order belongs to the current user
        if ($order->student_id !== $user->user_id) {
            return redirect()->route('orders.index')
                ->with('error', 'You do not have permission to cancel this order');
        }
        
        // Check if the order can be cancelled (only pending orders)
        if ($order->status !== 'pending') {
            return redirect()->route('orders.show', $id)
                ->with('error', 'Only pending orders can be cancelled');
        }
        
        try {
            DB::beginTransaction();
            
            // Update the order status to 'cancelled'
            DB::table('orders')
                ->where('order_id', $order->order_id)
                ->update(['status' => 'cancelled']);
            
            // Return items to inventory
            foreach ($order->orderItems as $item) {
                // Get the menu item
                $menuItem = DB::table('menuitems')
                    ->where('item_id', $item->item_id)
                    ->first();
                
                if ($menuItem) {
                    // Increase the available stock
                    DB::table('menuitems')
                        ->where('item_id', $item->item_id)
                        ->increment('available_stock', $item->quantity);
                }
            }
            
            DB::commit();
            
            return redirect()->route('orders.index')
                ->with('success', 'Order cancelled successfully');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log detailed error
            \Log::error('Order cancellation failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'order_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('orders.show', $id)
                ->with('error', 'Failed to cancel order: ' . $e->getMessage());
        }
    }

    /**
     * Check if the user has any pending orders
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPending()
    {
        try {
            $user = Auth::user();
            
            $pendingOrdersCount = DB::table('orders')
                ->where('student_id', $user->user_id)
                ->where('status', 'pending')
                ->count();
                
            return response()->json([
                'success' => true,
                'has_pending_orders' => $pendingOrdersCount > 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check pending orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check for orders that are ready for pickup
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkReadyForPickup()
    {
        try {
            $user = Auth::user();
            \Log::info('Checking ready for pickup orders for user #' . $user->user_id);
            
            $readyOrders = DB::table('orders')
                ->where('student_id', $user->user_id)
                ->where('status', 'ready')
                ->select('order_id', 'order_number', 'ready_since')
                ->get();
            
            // Log each ready order for debugging
            if ($readyOrders->count() > 0) {
                \Log::info('Found ' . $readyOrders->count() . ' ready orders:');
                foreach ($readyOrders as $order) {
                    $readySince = \Carbon\Carbon::parse($order->ready_since);
                    $now = now();
                    $elapsedSeconds = $now->timestamp - $readySince->timestamp;
                    $remainingSeconds = max(0, 60 - $elapsedSeconds);
                    
                    \Log::info("Order #{$order->order_number}: ready since {$order->ready_since}, {$elapsedSeconds}s ago, {$remainingSeconds}s remaining");
                }
            } else {
                \Log::info('No ready orders found for user #' . $user->user_id);
            }
                
            return response()->json([
                'success' => true,
                'ready_orders' => $readyOrders
            ]);
        } catch (\Exception $e) {
            \Log::error('Error checking ready orders: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check ready orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Automatically cancel an order if not picked up
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autoCancel(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            \Log::info('Auto-cancel request received for order ID: ' . $orderId);
            
            $order = Order::with('orderItems')->findOrFail($orderId);
            \Log::info('Found order: #' . $order->order_number . ' in status: ' . $order->status);
            
            // Only auto-cancel orders that are in 'ready' status
            if ($order->status !== 'ready') {
                \Log::warning('Cannot auto-cancel order #' . $order->order_number . ' - not in ready status (current status: ' . $order->status . ')');
                return response()->json([
                    'success' => false,
                    'message' => 'Order is not in ready status'
                ]);
            }
            
            DB::beginTransaction();
            
            // Update the order status to 'cancelled'
            $updated = DB::table('orders')
                ->where('order_id', $order->order_id)
                ->update([
                    'status' => 'cancelled'
                ]);
                
            \Log::info('Updated order status to cancelled, result: ' . ($updated ? 'success' : 'failed'));
            
            // Return items to inventory
            foreach ($order->orderItems as $item) {
                // Get the menu item
                $menuItem = DB::table('menuitems')
                    ->where('item_id', $item->item_id)
                    ->first();
                
                if ($menuItem) {
                    // Increase the available stock
                    $stockUpdated = DB::table('menuitems')
                        ->where('item_id', $item->item_id)
                        ->increment('available_stock', $item->quantity);
                        
                    \Log::info('Returned ' . $item->quantity . ' of item #' . $item->item_id . ' to inventory, result: ' . ($stockUpdated ? 'success' : 'failed'));
                }
            }
            
            // Add notification for the user
            $notificationId = Str::uuid()->toString();
            $notificationInserted = DB::table('notifications')->insert([
                'id' => $notificationId,
                'type' => 'App\\Notifications\\OrderCancelled',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $order->student_id,
                'data' => json_encode([
                    'order_id' => $order->order_id,
                    'order_number' => $order->order_number,
                    'message' => 'Your order has been automatically cancelled because it was not picked up within 60 seconds after being ready.',
                    'type' => 'order_cancelled'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            \Log::info('Added notification for user #' . $order->student_id . ', result: ' . ($notificationInserted ? 'success' : 'failed'));
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Order auto-cancelled successfully',
                'order_number' => $order->order_number
            ]);
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log detailed error
            \Log::error('Order auto-cancellation failed: ' . $e->getMessage(), [
                'order_id' => $request->input('order_id'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to auto-cancel order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check remaining time before auto-cancellation for a specific order
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkRemainingTime(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            
            $order = Order::findOrFail($orderId);
            
            // Only calculate for orders in 'ready' status
            if ($order->status !== 'ready') {
                return response()->json([
                    'success' => true,
                    'should_cancel' => false,
                    'remaining_seconds' => 0,
                    'message' => 'Order is not in ready status'
                ]);
            }
            
            // Calculate seconds remaining
            $readySince = $order->ready_since ? \Carbon\Carbon::parse($order->ready_since) : now();
            $now = now();
            $elapsedSeconds = $now->timestamp - $readySince->timestamp;
            $remainingSeconds = max(0, 60 - $elapsedSeconds);
            
            $shouldCancel = $remainingSeconds <= 0;
            
            // Log for debugging
            \Log::info("Order #{$order->order_number} time check: ready since {$readySince}, {$elapsedSeconds}s elapsed, {$remainingSeconds}s remaining, should cancel: " . ($shouldCancel ? 'yes' : 'no'));
            
            return response()->json([
                'success' => true,
                'should_cancel' => $shouldCancel,
                'remaining_seconds' => $remainingSeconds,
                'elapsed_seconds' => $elapsedSeconds,
                'order_number' => $order->order_number
            ]);
        } catch (\Exception $e) {
            \Log::error('Error checking remaining time: ' . $e->getMessage(), [
                'order_id' => $request->input('order_id'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check remaining time: ' . $e->getMessage()
            ], 500);
        }
    }
} 
