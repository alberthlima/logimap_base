<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('client')->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('items_summary', 'like', "%{$search}%")
                  ->orWhere('delivery_address', 'like', "%{$search}%")
                  ->orWhere('delivery_driver', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('business_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

        if ($request->boolean('all')) {
            return response()->json([
                'orders' => $query->get(),
            ]);
        }

        $perPage = $request->input('per_page', 15);
        $orders = $query->paginate($perPage);

        return response()->json([
            'orders' => $orders->items(),
            'total' => $orders->total(),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:30',
            'delivery_address' => 'nullable|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'city' => 'required|string',
            'district' => 'required|string',
            'items_summary' => 'nullable|string',
            'delivery_driver' => 'nullable|string',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        if (empty($data['code'])) {
            $lastId = Order::max('id') ?? 1000;
            $data['code'] = 'PED-' . ($lastId + 1);
        }
        if (empty($data['status'])) {
            $data['status'] = 'REGISTRADO';
        }

        $order = Order::create($data);

        return response()->json([
            'message' => 'Pedido registrado correctamente',
            'order' => $order->load('client'),
        ], 201);
    }

    public function show($id)
    {
        $order = Order::with('client')->findOrFail($id);

        return response()->json([
            'order' => $order,
        ]);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'total_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:REGISTRADO,EN_CAMINO,ENTREGADO,PAGADO',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order->update($request->all());

        return response()->json([
            'message' => 'Pedido actualizado correctamente',
            'order' => $order->load('client'),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:REGISTRADO,EN_CAMINO,ENTREGADO,PAGADO',
            'delivery_driver' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order->status = $request->status;
        if ($request->filled('delivery_driver')) {
            $order->delivery_driver = $request->delivery_driver;
        }
        $order->save();

        return response()->json([
            'message' => 'Estado del pedido actualizado a ' . $order->status,
            'order' => $order->load('client'),
        ]);
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json([
            'message' => 'Pedido eliminado correctamente',
        ]);
    }
}
